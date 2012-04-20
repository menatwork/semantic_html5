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
 * Table tl_content 
 */
 
// Palettes
if (tl_content_sh5::checkForTag())
{
    $GLOBALS['TL_DCA']['tl_content']['palettes']['semantic_html5'] = '{type_legend},type,headline;{html5_legend},sh5_type;{protected_legend:hide},protected;{expert_legend:hide},guests,invisible,cssID,space';
}
else
{
    $GLOBALS['TL_DCA']['tl_content']['palettes']['semantic_html5'] = '{type_legend}';
}

// Callbacks
$GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = array('tl_content_sh5', 'onsubmitCallback');
$GLOBALS['TL_DCA']['tl_content']['config']['ondelete_callback'][] = array('tl_content_sh5', 'ondeleteCallback');

// Fields
$GLOBALS['TL_DCA']['tl_content']['fields']['sh5_type'] = array
    (
    'label'             => &$GLOBALS['TL_LANG']['tl_content']['sh5_type'],
    'inputType'         => 'select',
    'options_callback'  => array('tl_content_sh5', 'optionsCallbackType'),
    'eval'              => array('submitOnChange' => true, 'mandatory' => true, 'includeBlankOption' => true)
);

/**
 * Class tl_content_semantic_html5
 */
class tl_content_sh5 extends tl_content
{

    /**
     * Check the current element and return false if is semantic_html5 endtag
     * 
     * @return boolean 
     */
    public static function checkForTag()
    {
        $objInput    = Input::getInstance();
        $objDatabase = Database::getInstance();

        $intId  = $objInput->get('id');
        $strAct = $objInput->get('act');

        if ($strAct == 'edit')
        {
            // Get current element
            $objElem = $objDatabase
                    ->prepare("SELECT * FROM tl_content WHERE id = ?")
                    ->limit(1)
                    ->execute($intId);

            if ($objElem->sh5_tag == 'end')
            {
                return false;
            }
            else
            {
                return true;
            }
        }

        if ($strAct == 'editAll')
        {
            return false;
        }

        return false;
    }

    /**
     * Merge the default-tags and the customer-tags array together and return them
     * 
     * @return array
     */
    public function optionsCallbackType()
    {
        $arrCustomerTags = array();
        if (strlen($GLOBALS['TL_CONFIG']['sh5_customer_tags']))
        {
            $arrCustomerTags = trimsplit(',', $GLOBALS['TL_CONFIG']['sh5_customer_tags']);

            foreach ($arrCustomerTags AS $k => $v)
            {
                $arrCustomerTags[$k] = $v;
            }
        }

        return array_merge(array_keys($GLOBALS['TL_HTML5']), $arrCustomerTags);
    }

    /**
     * Insert tl_content semantic_html5_end element after the current if not exists and update if exists
     * 
     * @param DataContainer $dc 
     */
    public function onsubmitCallback(DataContainer $dc)
    {
        // Get current record
        $objElement = $dc->activeRecord;

        // Chack if we have a semantic_html5 start element
        if ($objElement->type == 'semantic_html5' && $objElement->sh5_tag == 'start')
        {
            // Check if we have allready a semantic_html end tag
            $objElementEnd = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE sh5_pid=? AND type='semantic_html5' AND sh5_tag='end'")
                    ->limit(1)
                    ->execute($objElement->id);

            // If we have no end tag, create one
            if ($objElementEnd->numRows == 0)
            {
                // Build a new end element
                $arrNewElementEnd            = $objElement->fetchAllAssoc();
                $arrNewElementEnd            = $arrNewElementEnd[0];
                $arrNewElementEnd['sh5_pid'] = $objElement->id;
                $arrNewElementEnd['sh5_tag'] = 'end';
                $arrNewElementEnd['sorting'] += 1;
                
                // Support GlobalContentelements extension if installed
				if(in_array('GlobalContentelements',$this->Config->getActiveModules()))
				{
					$arrNewElementEnd['do'] = $this->Input->get('do');
				}
				
                unset($arrNewElementEnd['id']);

                // Insert end tag
                $this->Database
                        ->prepare("INSERT INTO tl_content %s")
                        ->set($arrNewElementEnd)
                        ->executeUncached();
            }
            // Else update endtag
            else
            {
                // Update endTag with sh5_type
                $this->Database
                        ->prepare("UPDATE tl_content %s WHERE sh5_pid=?")
                        ->set(array('sh5_type' => $objElement->sh5_type))
                        ->executeUncached($objElement->id);
            }
        }
        // Check if the current element has a semantic_html5 end tag and delete it.
        else
        {
            $objElementsEnd = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE sh5_pid=? AND type='semantic_html5' AND sh5_tag='end'")
                    ->execute($objElement->id);

            if ($objElementsEnd->numRows != 0)
            {
                while ($objElementsEnd->next())
                {
                    $this->insertUndo("DELETE FROM tl_content WHERE id=" . $objElementsEnd->id, "SELECT * FROM tl_content WHERE id = " . $objElementsEnd->id, "tl_content");

                    $this->Database
                            ->prepare("DELETE FROM tl_content WHERE id = ?")
                            ->execute($objElementsEnd->id);
                }
            }
        }
    }

    /**
     * Delets the next tl_content semantic_html5_end element 
     * 
     * @param DataContainer $dc 
     */
    public function ondeleteCallback(DataContainer $dc)
    {
        // Get current record
        $objElement = $dc->activeRecord;

        // Check if we have a semantic_html5 start or end element
        if ($objElement->type == 'semantic_html5' && $objElement->sh5_tag == 'start')
        {
            $objEndElement = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE sh5_pid=?  AND type='semantic_html5' AND sh5_tag='end'")
                    ->execute($objElement->id);

            // Check if we have a end element
            if ($objEndElement->numRows != 0)
            {
                $this->insertUndo("DELETE FROM tl_content WHERE sh5_pid=" . $objElement->id, "SELECT * FROM tl_content WHERE sh5_pid = " . $objElement->id, "tl_content");

                $this->Database
                        ->prepare("DELETE FROM tl_content WHERE sh5_pid = ?")
                        ->execute($objElement->id);
            }
        }
        else if ($objElement->type == 'semantic_html5' && $objElement->sh5_tag == 'end')
        {
            $objStartElement = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE id=? AND type='semantic_html5' AND sh5_tag='start'")
                    ->executeUncached($objElement->sh5_pid);

            // Check if we have a start element
            if ($objStartElement->numRows != 0)
            {
                $this->insertUndo("DELETE FROM tl_content WHERE id=" . $objElement->sh5_pid, "SELECT * FROM tl_content WHERE id = " . $objElement->sh5_pid, "tl_content");

                $this->Database
                        ->prepare("DELETE FROM tl_content WHERE id = ?")
                        ->execute($objElement->sh5_pid);
            }
        }
    }

    protected function insertUndo($strSourceSQL, $strSaveSQL, $strTable)
    {
        // Load row
        $arrResult = $this->Database
                ->prepare($strSaveSQL)
                ->executeUncached()
                ->fetchAllAssoc();

        // Check if we have a result
        if (count($arrResult) == 0)
        {
            return;
        }

        // Save information in array
        $arrSave = array();
        foreach ($arrResult as $value)
        {
            $arrSave[$strTable][] = $value;
        }
        
        $strPrefix = '<span style="color:#b3b3b3; padding-right:3px;">(semantic_html5)</span>';

        // Write into undo
        $this->Database
                ->prepare("INSERT INTO tl_undo (pid, tstamp, fromTable, query, affectedRows, data) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute($this->User->id, time(), $strTable, $strPrefix . $strSourceSQL, count($arrSave[$strTable]), serialize($arrSave));
    }

}

?>