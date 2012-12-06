<?php

if (!defined('TL_ROOT'))
    die('You cannot access this file directly!');

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
$GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'] = array('tl_content_sh5', 'addCteType');

/**
 * Palettes
 */
if (tl_content_sh5::checkForTag())
{
    $GLOBALS['TL_DCA']['tl_content']['palettes']['semantic_html5'] = '{type_legend},type,headline;{html5_legend},sh5_type;{protected_legend:hide},protected;{expert_legend:hide},guests,invisible,cssID,space';
}
else
{
    $GLOBALS['TL_DCA']['tl_content']['palettes']['semantic_html5'] = '{type_legend}';
}

/**
 * Callbacks
 */
$GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = array('tl_content_sh5', 'onsubmitCallback');
$GLOBALS['TL_DCA']['tl_content']['config']['ondelete_callback'][] = array('tl_content_sh5', 'ondeleteCallback');
$GLOBALS['TL_DCA']['tl_content']['config']['oncopy_callback'][] = array('SemanticHTML5Helper', 'onContentCopyCallback');

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['sh5_type'] = array
    (
    'label'            => &$GLOBALS['TL_LANG']['tl_content']['sh5_type'],
    'inputType'        => 'select',
    'options_callback' => array('tl_content_sh5', 'optionsCallbackType'),
    'eval' => array('submitOnChange'     => true, 'mandatory'          => true, 'includeBlankOption' => true)
);

$GLOBALS['TL_DCA']['tl_content']['fields']['sh5_pid'] = array
    (
    'inputType' => 'text'
);

$GLOBALS['TL_DCA']['tl_content']['fields']['sh5_tag'] = array
    (
    'inputType' => 'text'
);

/**
 * Class tl_content_semantic_html5
 */
class tl_content_sh5 extends tl_content
{

    protected static $arrContentElements = null;

    /**
     * Add the type of content element
     * @param array
     * @return string
     */
    public function addCteType($arrRow)
    {
        // Build level for all elements
        if (self::$arrContentElements == null)
        {
            $arrSh5Stack = array();
            self::$arrContentElements = array();

            $arrResult = $this->Database
                    ->prepare('SELECT * FROM tl_content WHERE pid=? ORDER BY sorting')
                    ->execute($this->Input->get('id'))
                    ->fetchAllAssoc();

            foreach ($arrResult as $value)
            {
                // Check for sh5 start and end tags
                if ($value['type'] == 'semantic_html5' && $value['sh5_tag'] == 'start')
                {
                    $arrSh5Stack[$value['id']] = true;
                }

                // Add level setting
                if (count($arrSh5Stack) != 0)
                {
                    self::$arrContentElements[$value['id']] = count($arrSh5Stack);
                }

                if ($value['type'] == 'semantic_html5' && $value['sh5_tag'] == 'end')
                {
                    unset($arrSh5Stack[$value['sh5_pid']]);
                }
            }
        }

        $strReturn = '';

        // Add rendering settings
        if (count(self::$arrContentElements) != 0 && array_key_exists($arrRow['id'], self::$arrContentElements))
        {
            $intLevel = self::$arrContentElements[$arrRow['id']];

            if ($arrRow['type'] == 'semantic_html5')
            {
                for ($i = 0; $i < $intLevel; $i++)
                {
                    if ($i == 0)
                    {
                        $strReturn .= '<div class="sh5-tag sh5-level-' . $i . '">';
                    }
                    else
                    {
                        $strReturn .= '<div class="sh5-tag sh5-level-' . $i . '" style="margin-left:20px;">';
                    }
                }

                $strReturn .= parent::addCteType($arrRow);

                for ($i = 0; $i < $intLevel; $i++)
                {
                    $strReturn .= '</div>';
                }
            }
            else
            {
                for ($i = 0; $i < $intLevel + 1; $i++)
                {
                    if ($i == 0)
                    {
                        $strReturn .= '<div class="sh5-content sh5-level-' . $i . '">';
                    }
                    else
                    {
                        $strReturn .= '<div class="sh5-content sh5-level-' . $i . '" style="margin-left:20px;">';
                    }
                }

                $strReturn .= parent::addCteType($arrRow);

                for ($i = 0; $i < $intLevel + 1; $i++)
                {
                    $strReturn .= '</div>';
                }
            }
        }
        else
        {
            $strReturn = parent::addCteType($arrRow);
        }

        return $strReturn;
    }

    /**
     * Check the current element and return false if is semantic_html5 endtag
     * 
     * @return boolean 
     */
    public static function checkForTag()
    {
        $objInput = Input::getInstance();

        if ($objInput->get('act') == 'edit' || $objInput->get('act') == 'editAll')
        {
            // Get current element
            $objElem = Database::getInstance()
                    ->prepare("SELECT * FROM tl_content WHERE id = ?")
                    ->limit(1)
                    ->execute($objInput->get('id'));

            if ($objElem->sh5_tag == 'start')
            {
                return TRUE;
            }
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

        // Check if we have a semantic_html5 start element
        if ($objElement->type == 'semantic_html5' && $objElement->sh5_tag == 'start')
        {
            if ($objElement->id != $objElement->sh5_pid)
            {
                $this->Database
                        ->prepare("UPDATE tl_content %s WHERE id = ?")
                        ->set(array('sh5_pid' => $objElement->id))
                        ->executeUncached($objElement->id);
            }

            // Check if we have allready a semantic_html end tag
            $objElementEnd = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE sh5_pid = ? AND type = 'semantic_html5' AND sh5_tag = 'end'")
                    ->limit(1)
                    ->execute($objElement->id);

            // If we have no end tag, create one
            if ($objElementEnd->numRows == 0)
            {
                SemanticHTML5Helper::getInstance()->createEndTag($objElement);
            }
            // Else update endtag
            else
            {
                // Update endTag with sh5_type
                $this->Database
                        ->prepare("UPDATE tl_content %s WHERE sh5_pid = ?")
                        ->set(array('sh5_type' => $objElement->sh5_type))
                        ->executeUncached($objElement->id);
            }
        }
        // Check if the current element has a semantic_html5 end tag and delete it.
        else
        {
            $objElementsEnd = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE sh5_pid = ? AND type = 'semantic_html5' AND sh5_tag = 'end'")
                    ->execute($objElement->id);

            if ($objElementsEnd->numRows != 0)
            {
                while ($objElementsEnd->next())
                {
                    $this->insertUndo("DELETE FROM tl_content WHERE id = " . $objElementsEnd->id, "SELECT * FROM tl_content WHERE id = " . $objElementsEnd->id, "tl_content");

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
                    ->prepare("SELECT * FROM tl_content WHERE type = 'semantic_html5' AND sh5_tag = 'end' AND pid = ? AND sh5_pid = ?")
                    ->limit(1)
                    ->executeUncached($objElement->pid, $objElement->id);

            // Check if we have an end element
            if ($objEndElement->numRows != 0)
            {
                $this->insertUndo("DELETE FROM tl_content WHERE id = " . $objEndElement->id, "SELECT * FROM tl_content WHERE id IN(" . $objEndElement->id . ", " . $objElement->id . ")", "tl_content");

                $this->Database
                        ->prepare("DELETE FROM tl_content WHERE id = ?")
                        ->execute($objEndElement->id);
            }
        }
        else if ($objElement->type == 'semantic_html5' && $objElement->sh5_tag == 'end')
        {
            $objStartElement = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE id = ? AND type = 'semantic_html5' AND sh5_tag = 'start' AND pid = ? AND sh5_pid = ?")
                    ->limit(1)
                    ->executeUncached($objElement->sh5_pid, $objElement->pid, $objElement->sh5_pid);

            // Check if we have a start element
            if ($objStartElement->numRows != 0)
            {
                $this->insertUndo("DELETE FROM tl_content WHERE id = " . $objStartElement->id, "SELECT * FROM tl_content WHERE id IN(" . $objStartElement->id . ", " . $objElement->id . ")", "tl_content");

                $this->Database
                        ->prepare("DELETE FROM tl_content WHERE id = ?")
                        ->execute($objStartElement->id);
            }
        }
    }

    /**
     * Insert additional delete from appendant tag to contao undo table
     * 
     * @param type $strSourceSQL
     * @param type $strSaveSQL
     * @param type $strTable
     * @return type 
     */
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