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
    'load_callback' => array(array('SemanticHTML5Settings', 'loadCallbackTags')),
    'save_callback' => array(array('SemanticHTML5Settings', 'saveCallbackTags')),
    'eval'          => array
    (
        'preserveTags' => true,
        'tl_class'     => 'long'
    )
);

?>