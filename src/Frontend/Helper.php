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

namespace SemanticHTML5\Frontend;

/**
 * Generall Helper Class for Backend realted functions
 */
class Helper
{

    private $blacklistedAttributes = array(
        'tl_content' => array('id', 'class')
    );

    /**
     * Converts an array of additional attributs to a string. Blacklisted attributes will be filtered
     * 
     * @param array $attributes The additional attribues
     * @param string $table the db atble of the element
     * @return string The attributes as a string
     */
    public function convertAttributesToString($attributes, $table = 'tl_contentS') {
        
        $attrbuteString = '';
        
        foreach ($attributes as $attribute) {

            //skip empty or blacklisted attributes
            if ($attribute['property'] == '' || in_array($attribute['property'], $this->blacklistedAttributes[$table])) {
                continue;
            }

            $attrbuteString .= ' ' . $attribute['property'] . ((!empty($attribute['value'])) ? '="' . specialchars($attribute['value']) . '"' : '');
        }

        return $attrbuteString;
    } 
}