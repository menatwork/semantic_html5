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
 *
 * Config
 */
$GLOBALS['TL_HTML5']['tags'] = array(
    'tl_content' => array('article', 'aside', 'button', 'div', 'footer', 'header', 'section')
);

$GLOBALS['TL_HTML5']['copyFields'] = array(
    'tl_content' => array('protected', 'groups', 'guests', 'start', 'stop')
);

/**
 * Content Element
 */
$GLOBALS['TL_CTE']['html5']['sHtml5Start'] = 'SemanticHTML5\Elements\Start';
$GLOBALS['TL_CTE']['html5']['sHtml5End'] = 'SemanticHTML5\Elements\End';

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['getContentElement'][] = array('SemanticHTML5\Backend\Callbacks', 'addColorizeJs');

/**
 * Wrapper
 */
$GLOBALS['TL_WRAPPERS']['start'][] = 'sHtml5Start';
$GLOBALS['TL_WRAPPERS']['stop'][] = 'sHtml5End';
