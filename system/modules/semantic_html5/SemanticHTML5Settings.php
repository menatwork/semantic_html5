<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2013
 * @package    semantic_html5
 * @license    GNU/LGPL
 * @filesource
 */

/**
 * Class SemanticHTML5Settings
 */
class SemanticHTML5Settings extends tl_settings
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
        if (strlen($varValue))
        {
            $arrCustomerTags = explode(',', $varValue);
            foreach ($arrCustomerTags AS $k => $v)
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
        if (strlen($varValue))
        {
            $arrTags = explode(',', $varValue);
            foreach ($arrTags AS $k => $v)
            {
                if (!in_array(trim($v), $GLOBALS['TL_HTML5']))
                {
                    $arrCustomerTags[] = trim($v);
                }
            }
        }

        return implode(', ', $arrCustomerTags);
    }

}

?>