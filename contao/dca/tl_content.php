<?php

/**
 * Contao Open Source CMS
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @package    semantic_html5
 * @copyright  MEN AT WORK 2016
 * @author     David Maack <david.maack@arcor.de>
 * @license    LGPL-3.0+
 */

/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['sHtml5Start'] = '{type_legend},type,headline;{html5_legend},sh5_type,sh5_additional;{protected_legend:hide},protected;{expert_legend:hide},guests,invisible,cssID,space';


/**
 * Callbacks
 */
$GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = array('SemanticHTML5\Backend\Callbacks', 'onsubmitCallback');
$GLOBALS['TL_DCA']['tl_content']['config']['ondelete_callback'][] = array('SemanticHTML5\Backend\Callbacks', 'ondeleteCallback');
$GLOBALS['TL_DCA']['tl_content']['config']['oncopy_callback'][] = array('SemanticHTML5\Backend\Callbacks', 'oncopyContentCallback');

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['sh5_type'] = array(
    'label'             => &$GLOBALS['TL_LANG']['tl_content']['sh5_type'],
    'inputType'         => 'select',
    'options'           => array('div', 'section'),
    'eval'              => array(
        'submitOnChange'     => true,
        'mandatory'          => true,
        'includeBlankOption' => true
    ),
    'sql'               => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['sh5_additional'] = array(
    'label'             => &$GLOBALS['TL_LANG']['tl_content']['sh5_additional'],
    'exclude'           => true,
    'inputType'         => 'multiColumnWizard',
    'sql'               => "blob NULL",
    'eval' => array(
        'tl_class'      => 'clr',
        'columnFields' => array(
            'property' => array(
                'label'             => &$GLOBALS['TL_LANG']['tl_content']['sh5_additional']['property'],
                'inputType'         => 'text',
                'eval' => array(
                    'style'         => 'width:290px',
                    'nospace'       => true,
                    'rgxp'          => 'alnum'
                )
            ),
            'value' => array(
                'label'             => &$GLOBALS['TL_LANG']['tl_content']['sh5_additional']['value'],
                'inputType'         => 'text',
                'eval' => array(
                    'style'         => 'width:290px'
                )
            ),
        ),
    ),
);

$GLOBALS['TL_DCA']['tl_content']['fields']['sh5_pid'] = array(
    'inputType'         => 'text',
    'sql'               => "int(10) unsigned NOT NULL default '0'"
);