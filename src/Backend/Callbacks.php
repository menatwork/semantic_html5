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

namespace SemanticHTML5\Backend;

/**
 * Generall class to handle all backend callbacks
 */
class Callbacks
{
    private static $rotatingColor = .2;

    private static $elementColors = [];

    /**
     * Callback function to add the JS for colorization the the markup
     * 
     * @param type $objRow
     * @param type $strBuffer
     * @param type $objElement
     * @return String
     */
    public static function addColorizeJs($objRow, $strBuffer, $objElement)
    {
        // if the element is no type of semantic html5 or the element ist not 
        // renderen in the backend, do nothing
        if (TL_MODE == 'BE' && ($objRow->type == 'sHtml5Start' || $objRow->type == 'sHtml5End')) {
            //get the color of the parent start-tag or rotate the color
            if ($objRow->type == 'sHtml5End') {
                $color = static::$elementColors[$objRow->sh5_pid];
            } else {
                $color = self::rotateColor();
                static::$elementColors[$objRow->id] = $color;
            }

            $template = new \BackendTemplate('be_semantic_html5_colorizejs');
            $template->id = $objRow->id;
            $template->color = $color;

            $strBuffer .= $template->parse();
        }
        return ($strBuffer);
    }
    


    /**
     * Rotate the color and return a new color
     * @return String the hex string of the new color
     */
    private static function rotateColor()
    {
        $color = self::HSVtoRGB(static::$rotatingColor, 1, .8);

        static::$rotatingColor += .7;

        if (static::$rotatingColor > 1) {
            static::$rotatingColor -= 1;
        }

        return $color;
    }

    /**
     * @see http://stackoverflow.com/a/3597447
     */
    private static function HSVtoRGB($hue, $saturation, $value)
    {
        //1
        $hue *= 6;
        //2
        $I = floor($hue);
        $F = $hue - $I;
        //3
        $M = $value * (1 - $saturation);
        $N = $value * (1 - $saturation * $F);
        $K = $value * (1 - $saturation * (1 - $F));
        //4
        switch ($I) {
            case 0:
                list($red, $green, $blue) = array($value, $K, $M);
                break;
            case 1:
                list($red, $green, $blue) = array($N, $value, $M);
                break;
            case 2:
                list($red, $green, $blue) = array($M, $value, $K);
                break;
            case 3:
                list($red, $green, $blue) = array($M, $N, $value);
                break;
            case 4:
                list($red, $green, $blue) = array($K, $M, $value);
                break;
            case 5:
            case 6: //for when $H=1 is given
                list($red, $green, $blue) = array($value, $M, $N);
                break;
        }
        return sprintf('#%02x%02x%02x', $red * 255, $green * 255, $blue * 255);
    }

}
