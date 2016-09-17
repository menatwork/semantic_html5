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
 * Callbacks
 */
$GLOBALS['TL_DCA']['tl_page']['config']['oncopy_callback'][] = array('SemanticHTML5\Backend\Callbacks', 'oncopyPageCallback');