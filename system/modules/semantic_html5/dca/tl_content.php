<?php if (!defined('TL_ROOT')) 
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
        $objInput = Input::getInstance();
        $objDatabase = Database::getInstance();

        $intId = $objInput->get('id');
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
                return FALSE;
            }
        }
        return TRUE;
    }
    
    /**
     * Merge the default-tags and the customer-tags array together and return them
     * 
     * @return array
     */
    public function optionsCallbackType()
    {
        $arrCustomerTags = array();
        if(strlen($GLOBALS['TL_CONFIG']['sh5_customer_tags']))
        {
            $arrCustomerTags = explode(',', $GLOBALS['TL_CONFIG']['sh5_customer_tags']);
            foreach($arrCustomerTags AS $k => $v)
            {
                $arrCustomerTags[$k] = trim($v);
            }
        }
        
        return array_merge(array_keys($GLOBALS['TL_HTML5']), $arrCustomerTags);
    }

    /**
     * Insert tl_content semantic_html5_end element after the current if not
     * exists and update if exists
     * 
     * @param DataContainer $dc 
     */
    public function onsubmitCallback(DataContainer $dc)
    {        
        // Get current element
        $objElemStart = $this->Database
                ->prepare("SELECT * FROM tl_content WHERE id = ?")
                ->limit(1)
                ->execute($dc->id);

        if ($objElemStart->type == 'semantic_html5' && $objElemStart->sh5_tag == 'start')
        {
            $objElemEnd = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE sh5_pid = ?")
                    ->limit(1)
                    ->execute($dc->id);

            // Create Set array for insert and update end tag in database
            $arrSet = $objElemStart->reset()->fetchAssoc();
			$arrSet['sh5_pid'] = $dc->id;
            $arrSet['sh5_tag'] = 'end';			
            unset($arrSet['id']);

            if ($objElemEnd->sh5_tag == 'end')
            {
                unset($arrSet['sorting']);
                $arrSet['sh5_type'] = $this->Input->post('sh5_type');

                // Update end tag
                $this->Database
                        ->prepare("UPDATE tl_content %s WHERE id = ?")
                        ->set($arrSet)
                        ->execute($objElemEnd->id);
            }
            else
            {
                $arrSet['sorting'] += 1;

                // Insert end tag
                $this->Database
                        ->prepare("INSERT INTO tl_content %s")
                        ->set($arrSet)
                        ->execute();
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
        $objElem = $this->Database
                ->prepare("SELECT * FROM tl_content WHERE id = ?")
                ->limit(1)
                ->execute($dc->id);

        if ($objElem->type == 'semantic_html5' && $objElem->sh5_tag == 'start')
        {
			$this->Database
					->prepare("DELETE FROM tl_content WHERE sh5_pid = ?")
					->execute($dc->id);			
        }
        else
        {
			$objElemEnd = $this->Database
                ->prepare("SELECT * FROM tl_content WHERE id = ?")
                ->limit(1)
                ->execute($dc->id);
		
            $this->Database
					->prepare("DELETE FROM tl_content WHERE id = ?")
					->execute($objElemEnd->sh5_pid);
        }
    }

}

?>