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

if (\Database::getInstance()->fieldExists('sh5_tag', 'tl_content')) {
    \Database::getInstance()->execute("UPDATE `tl_content` set type = 'sHtml5Start' WHERE type = 'semantic_html5' AND sh5_tag = 'start'");
    \Database::getInstance()->execute("UPDATE `tl_content` set type = 'sHtml5End' WHERE type = 'semantic_html5' AND sh5_tag = 'end'");
}
