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
 * Table tl_content
 */
$GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'] = array('SemanticHTML5Content', 'addCteType');

/**
 * Palettes
 */
if (SemanticHTML5Content::checkForTag())
{
    $GLOBALS['TL_DCA']['tl_content']['palettes']['semantic_html5'] = '{type_legend},type,headline;{html5_legend},sh5_type,sh5_additional;{protected_legend:hide},protected;{expert_legend:hide},guests,invisible,cssID,space';
}
else
{
    $GLOBALS['TL_DCA']['tl_content']['palettes']['semantic_html5'] = '{type_legend}';
}

/**
 * Callbacks
 */
$GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = array('SemanticHTML5Content', 'onsubmitCallback');
$GLOBALS['TL_DCA']['tl_content']['config']['ondelete_callback'][] = array('SemanticHTML5Content', 'ondeleteCallback');
$GLOBALS['TL_DCA']['tl_content']['config']['oncopy_callback'][]   = array('SemanticHTML5Helper', 'onContentCopyCallback');

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_content']['fields']['sh5_type'] = array
(
    'label'             => &$GLOBALS['TL_LANG']['tl_content']['sh5_type'],
    'inputType'         => 'select',
    'options_callback'  => array('SemanticHTML5Content', 'optionsCallbackType'),
    'eval'              => array
    (
        'submitOnChange'     => true,
        'mandatory'          => true,
        'includeBlankOption' => true
    )
);

$GLOBALS['TL_DCA']['tl_content']['fields']['sh5_additional'] = array
(
    'label'             => &$GLOBALS['TL_LANG']['tl_content']['sh5_additional'],
    'exclude'           => true,
    'inputType'         => 'multiColumnWizard',
    'eval' => array
    (
        'tl_class'      => 'clr',
        'columnFields' => array
        (
            'property' => array
            (
                'label'             => &$GLOBALS['TL_LANG']['tl_content']['sh5_additional']['property'],
                'inputType'         => 'text',
                'eval' => array
                (
                    'style'         => 'width:290px',
                    'nospace'       => true,
                    'rgxp'          => 'alnum'
                )
            ),
            'value' => array
            (
                'label'             => &$GLOBALS['TL_LANG']['tl_content']['sh5_additional']['value'],
                'inputType'         => 'text',
                'eval' => array
                (
                    'style'         => 'width:290px',
                    'nospace'       => true,
                    'rgxp'          => 'alnum'
                )
            ),
        ),
    ),
);

$GLOBALS['TL_DCA']['tl_content']['fields']['sh5_pid'] = array
(
    'inputType'         => 'text'
);

$GLOBALS['TL_DCA']['tl_content']['fields']['sh5_tag'] = array
(
    'inputType'         => 'text'
);

if(in_array('parallaxImagePicker', Config::getInstance()->getActiveModules()) && tl_content_parallaxIP::isActive()) {
    /**
     * Parallax palette
     */
    foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $strKey => $arrRow)
    {
        if ($strKey == '__selector__') continue;
        $arrPalettes = explode(";", $arrRow);
        array_insert($arrPalettes, count($arrPalettes) - 2, array('{prx_image_picker_legend},prx_image_picker'));
        $GLOBALS['TL_DCA']['tl_content']['palettes'][$strKey] = implode(";", $arrPalettes);
    }
}

?>