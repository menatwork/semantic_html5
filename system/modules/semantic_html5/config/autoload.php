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
 * Register the classes
 */
ClassLoader::addClasses(array
(
    'SemanticHTML5'       => 'system/modules/semantic_html5/SemanticHTML5.php',
    'SemanticHTML5Helper' => 'system/modules/semantic_html5/SemanticHTML5Helper.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
    'ce_semantic_html5' => 'system/modules/semantic_html5/templates',
));
