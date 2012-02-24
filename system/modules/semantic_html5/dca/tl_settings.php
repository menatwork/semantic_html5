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
 * @license    GNU/LGPL
 * @filesource
 */

/**
 * Palettes 
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = str_replace('{security_legend:hide}', '{security_legend:hide},sh5_customer_tags', $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']);

/**
 * Fields 
 */
$GLOBALS['TL_DCA']['tl_settings']['fields']['sh5_customer_tags'] = array
(
        'label'         => &$GLOBALS['TL_LANG']['tl_settings']['sh5_customer_tags'],
        'inputType'     => 'text',
        'load_callback' => array(array('tl_settings_sh5', 'loadCallbackTags')),
        'save_callback' => array(array('tl_settings_sh5', 'saveCallbackTags')),
        'eval'          => array('preserveTags'=>true, 'tl_class'=>'long')
);

/**
 * Class tl_settings_sh5
 */
class tl_settings_sh5 extends tl_settings
{

    /**
     * Initialize the object
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Merge the default tags with the customer tags and load them in settings
     * 
     * @param mixed $varValue
     * @param DataContainer $dc
     * @return string 
     */
    public function loadCallbackTags($varValue, DataContainer $dc)
    {
        $arrCustomerTags = array();
        if(strlen($varValue))
        {
            $arrCustomerTags = explode(',', $varValue);
            foreach($arrCustomerTags AS $k => $v)
            {
                $arrCustomerTags[$k] = trim($v);
            }
        }
        
        return implode(', ', array_merge(array_keys($GLOBALS['TL_HTML5']), $arrCustomerTags));
    }
    
    /**
     * Remove the default tags from given string and save customer only
     * 
     * @param type $varValue
     * @param DataContainer $dc
     * @return type 
     */
    public function saveCallbackTags($varValue, DataContainer $dc)
    {
        $arrCustomerTags = array();
        if(strlen($varValue))
        {
            $arrTags = explode(',', $varValue);
            foreach($arrTags AS $k => $v)
            {
                if(!in_array(trim($v), $GLOBALS['TL_HTML5']))
                {
                    $arrCustomerTags[] = trim($v);
                }
            }
        }
        
        return implode(', ', $arrCustomerTags);
    }
    
}

?>