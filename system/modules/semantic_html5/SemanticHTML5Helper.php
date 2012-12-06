<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  MEN AT WORK 2012
 * @package    semantic_html5
 * @license    GNU/GPL 2
 * @filesource
 */

/**
 * Class SemanticHTML5Helper
 */
class SemanticHTML5Helper extends Backend
{

    /**
     * Current object instance (Singleton)
     * @var SemanticHTML5Helper
     */
    protected static $objInstance = NULL;

    /**
     * Prevent constructing the object (Singleton)
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Prevent cloning of the object (Singleton)
     */
    final private function __clone(){}

    /**
     * Get instanz of the object (Singelton) 
     *
     * @return SemanticHTML5Helper 
     */
    public static function getInstance()
    {
        if (self::$objInstance == NULL)
        {
            self::$objInstance = new SemanticHTML5Helper();
        }
        return self::$objInstance;
    }

    /**
     * Create end tag for given start tag
     * 
     * @param Database_Result $objStartTag
     * @return type 
     */
    public function createEndTag($objStartTag)
    {
        $arrEndTag = $objStartTag->row();
        unset($arrEndTag['id']);

        $arrEndTag['sh5_pid'] = $objStartTag->id;
        $arrEndTag['tstamp'] = time();
        $arrEndTag['sh5_tag'] = 'end';
        $arrEndTag['sorting'] = $objStartTag->sorting + 1;

        // Support GlobalContentelements extension if installed
        if (in_array('GlobalContentelements', $this->Config->getActiveModules()))
        {
            $arrEndTag['do'] = $this->Input->get('do');
        }

        // Insert end tag
        $objResult = $this->Database
                ->prepare("INSERT INTO tl_content %s")
                ->set($arrEndTag)
                ->execute();

        return $objResult->insertId;
    }

    /**
     * Create start tag for given end tag and update it
     * 
     * @param Database_Result $objEndTag
     */
    public function createStartTag($objEndTag)
    {
        $arrStartTag = $objEndTag->row();
        unset($arrStartTag['id']);
        unset($arrStartTag['sh5_pid']);

        $arrStartTag['sh5_tag'] = 'start';
        $arrStartTag['sorting'] = $objEndTag->sorting - 1;

        // Support GlobalContentelements extension if installed
        if (in_array('GlobalContentelements', $this->Config->getActiveModules()))
        {
            $arrStartTag['do'] = $this->Input->get('do');
        }

        // Insert start tag
        $objResult = $this->Database
                ->prepare("INSERT INTO tl_content %s")
                ->set($arrStartTag)
                ->execute();

        $intId = $objResult->insertId;

        // Update start tag
        $this->Database
                ->prepare("UPDATE tl_content %s WHERE id = ?")
                ->set(array('sh5_pid' => $intId))
                ->executeUncached($intId);

        // Update end tag
        $this->Database
                ->prepare("UPDATE tl_content %s WHERE id = ?")
                ->set(array('sh5_pid' => $intId))
                ->executeUncached($objEndTag->id);
    }

    /**
     * Function for global tl_page oncopy callback
     * $GLOBALS['TL_DCA']['tl_page']['config']['oncopy_callback']
     * 
     * @param integer $intId
     * @param DataContainer $dc 
     */
    public function onPageCopyCallback($intId, DataContainer $dc)
    {
        if (!$this->Input->get('childs'))
        {
            $objArticle = $this->Database
                    ->prepare("SELECT id FROM tl_article WHERE pid = ?")
                    ->execute($intId);

            if ($objArticle->numRows > 0)
            {
                while ($objArticle->next())
                {
                    $this->updateContentElem($objArticle->id);
                }
            }
        }
        else if ($this->Input->get('childs') == 1)
        {
            $arrPages = $this->getChildRecords($intId, 'tl_page');

            foreach ($arrPages as $intId)
            {
                $objArticle = $this->Database
                        ->prepare("SELECT id FROM tl_article WHERE pid=?")
                        ->execute($intId);

                if ($objArticle->numRows > 0)
                {
                    while ($objArticle->next())
                    {
                        $this->updateContentElem($objArticle->id);
                    }
                }
            }
        }
    }

    /**
     * Function for global tl_article oncopy callback
     * $GLOBALS['TL_DCA']['tl_article']['config']['oncopy_callback']
     * 
     * @param integer $intId
     * @param DataContainer $dc 
     */    
    public function onArticleCopyCallback($intId, DataContainer $dc)
    {
        $this->updateContentElem($intId);
    }

    /**
     * Function for global tl_contant oncopy callback
     * $GLOBALS['TL_DCA']['tl_content']['config']['oncopy_callback']
     * 
     * @param integer $intId
     * @param DataContainer $dc 
     */
    public function onContentCopyCallback($intId, DataContainer $dc)
    {
        if ($this->Input->get('act') == 'copyAll')
        {                
            $objActiveRecord = $this->Database->prepare("SELECT * FROM tl_content WHERE id = ?")->executeUncached($intId);

            if ($objActiveRecord->type == 'semantic_html5')
            {
                if ($objActiveRecord->sh5_tag == 'start')
                {
                    $intPid = $objActiveRecord->sh5_pid;
                    
                    $arrSession = deserialize($this->Session->get('semantic_html5'));
                    
                    if(!is_array($arrSession))
                    {
                        $arrSession = array();
                    }
                    
                    $this->Database->prepare("UPDATE tl_content %s WHERE id = ?")
                            ->set(array('sh5_pid' => $objActiveRecord->id))
                            ->execute($intId);

                    $objContent = $this->Database
                            ->prepare("Select * FROM tl_content WHERE id = ?")
                            ->execute($intId);

                    // Create placeholder if no end tag was copied
                    $intlastId = $this->createEndTag($objContent);

                    $arrSession[$intPid] = array(
                        'sh5_pid' => $objActiveRecord->id,
                        'id' => $intlastId
                    );
                    
                    $this->Session->set('semantic_html5', serialize($arrSession));
                }
                else if ($objActiveRecord->sh5_tag == 'end')
                {
                    $arrSession = deserialize($this->Session->get('semantic_html5'));

                    if(!is_array($arrSession))
                    {
                        $arrSession = array();
                    }
                    
                    if (array_key_exists($objActiveRecord->sh5_pid, $arrSession))
                    {
                        $arrSet = $arrSession[$objActiveRecord->sh5_pid];
                        
                        // Delete placeholder end tag
                        $this->Database
                                ->prepare("DELETE FROM tl_content WHERE id = ?")
                                ->execute($arrSet['id']);

                        // Update end tag
                        $this->Database
                                ->prepare("UPDATE tl_content %s WHERE id = ?")
                                ->set(array('sh5_pid' => $arrSet['sh5_pid']))
                                ->execute($intId);
                        
                        unset($arrSession[$objActiveRecord->sh5_pid]);
                    }
                    else
                    {
                        $this->createStartTag($objActiveRecord);
                    }
                    
                    $this->Session->set('semantic_html5', serialize($arrSession));
                }
            }
        }
    }

    /**
     * Repair copied module specific content elements
     * 
     * @param integer $intId 
     */
    protected function updateContentElem($intId)
    {
        $objContents = $this->Database
                ->prepare("SELECT id, pid, type, sh5_pid, sh5_type, sh5_tag FROM tl_content WHERE pid = ? AND type = 'semantic_html5' GROUP BY sorting")
                ->execute($intId);

        if ($objContents->numRows == 0)
        {
            return;
        }

        $arrResult = $objContents->fetchAllAssoc();

        $arrSets = array();

        foreach ($arrResult AS $arrContentElem)
        {
            if ($arrContentElem['type'] == 'semantic_html5')
            {
                if ($arrContentElem['sh5_tag'] == 'start' && $arrContentElem['sh5_pid'] != $arrContentElem['id'])
                {
                    foreach ($arrResult as $k => $v)
                    {
                        if ($v['sh5_tag'] == 'end' && $v['sh5_pid'] == $arrContentElem['sh5_pid'])
                        {
                            $arrSets[$arrContentElem['id']] = array('sh5_pid' => $arrContentElem['id']);
                            $arrSets[$v['id']] = array('sh5_pid' => $arrContentElem['id']);

                            unset($arrResult[$k]);
                        }
                    }
                }

                if ($arrContentElem['sh5_tag'] == 'end')
                {
                    foreach ($arrResult as $k => $v)
                    {
                        if ($v['sh5_tag'] == 'start' && $v['sh5_pid'] == $arrContentElem['sh5_pid'] && $v['id'] != $v['sh5_pid'])
                        {
                            $arrSets[$arrContentElem['id']] = array('sh5_pid' => $v['id']);
                            $arrSets[$v['id']] = array('sh5_pid' => $v['id']);

                            unset($arrResult[$k]);
                        }
                    }
                }
            }
        }

        if (count($arrSets) > 0)
        {
            foreach ($arrSets As $intId => $arrSet)
                $this->Database
                        ->prepare("UPDATE tl_content %s WHERE id = ?")
                        ->set($arrSet)
                        ->execute($intId);
        }
    }
    
    /**
     * Return sh5 type clipboard title
     * HOOK: $GLOBALS['TL_HOOKS']['clipboardContentTitle']
     * 
     * @param ClipboardHelper $objClipboardHelper
     * @param string $strHeadline
     * @param DB_Mysql_Result $objContent
     * @param boolean $booClGroup
     * @return mixed
     */
    public function clipboardContentTitle(ClipboardHelper $objClipboardHelper, $strHeadline, DB_Mysql_Result $objContent, $booClGroup)
    {        
        if($objContent->type == 'semantic_html5')
        {            
            return ((!$booClGroup) ? ucfirst($objContent->sh5_tag) : '') . ' ' . strtoupper($objContent->sh5_type);
        }
        
        return NULL;
    }
    
    /**
     * 
     * HOOK: $GLOBALS['TL_HOOKS']['clipboardCopy']
     * 
     * @param integer $intId
     * @param datacontainer $dc
     * @param array $objDb
     * @param boolean $isGrouped
     */
    public function clipboardCopy($intId, datacontainer $dc, $isGrouped)
    {
        if(!$isGrouped)
        {
            $objActiveRecord = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE id = ?")
                    ->executeUncached($intId);
            
            if ($objActiveRecord->type == 'semantic_html5')
            {
                if ($objActiveRecord->sh5_tag == 'start')
                {
                    $this->Database->prepare("UPDATE tl_content %s WHERE id = ?")
                            ->set(array('sh5_pid' => $objActiveRecord->id))
                            ->execute($intId);

                    $objContent = $this->Database
                            ->prepare("Select * FROM tl_content WHERE id = ?")
                            ->execute($intId);

                    // Create placeholder if no end tag was copied
                    $this->createEndTag($objContent);
                }
                else
                {
                    $this->createStartTag($objActiveRecord);
                }
            }
        }
    }
    
    /**
     * 
     * HOOK: $GLOBALS['TL_HOOKS']['clipboardCopyAll']
     * 
     * @param array $arrIds
     */
    public function clipboardCopyAll($arrIds)
    {
        foreach(array_keys(array_flip($arrIds)) as $intId)
        {
            $this->updateContentElem($intId);
        }
    }

}

?>