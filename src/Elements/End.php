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

namespace SemanticHTML5\Elements;

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\System;

class End extends ContentElement
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'ce_semantic_html5_end';

    /**
     * Generate the content element
     */
    protected function compile()
    {
        //render BE-Template
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request))
        {
            $this->Template = new BackendTemplate('be_wildcard');
            $this->Template->wildcard = "&lt;/" . $this->sh5_type . "&gt;";
            return $this->Template->parse();
        }
    }

}
