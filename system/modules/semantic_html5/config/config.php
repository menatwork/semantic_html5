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
 * Content Element
 */
$GLOBALS['TL_CTE']['texts']['semantic_html5'] = 'SemanticHTML5';

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['clipboardContentTitle'][] = array('SemanticHTML5Helper', 'clipboardContentTitle');
$GLOBALS['TL_HOOKS']['clipboardCopy'][]         = array('SemanticHTML5Helper', 'clipboardCopy');
$GLOBALS['TL_HOOKS']['clipboardCopyAll'][]      = array('SemanticHTML5Helper', 'clipboardCopyAll');

/**
 * Takes all available tags
 *
 * @global array $GLOBALS['TL_HTML5']
 * @name $TL_HTML5
 */
$GLOBALS['TL_HTML5'] = array(
    'article' => 'article',
    'aside'   => 'aside',
    'footer'  => 'footer',
    'header'  => 'header',
    'hgroup'  => 'hgroup',
    'section' => 'section',
    'div'     => 'div'
);

$GLOBALS['TL_WRAPPERS']['start'][] = 'semantic_html5';