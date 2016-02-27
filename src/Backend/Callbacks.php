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
    /**
     * Object instance (Singleton)
     */
    protected static $objInstance;

    /**
     * Array element colors
     */
    private static $elementColors = [];

    /**
     * Prevent direct instantiation (Singleton)
     */
    protected function __construct() {}

    /**
     * Prevent cloning of the object (Singleton)
     */
    final public function __clone() {}

    /**
     * Return the object instance (Singleton)
     */
    public static function getInstance()
    {
        if (static::$objInstance === null) {
            static::$objInstance = new static();
        }
        return static::$objInstance;
    }

    /**
     * Adds or updates the corresponding star or end tag
     * @param \DataContainer $dc
     */
    public static function onsubmitCallback(\DataContainer $dc)
    {
        //if this is not a html5 element, do nothing
        if (in_array($dc->activeRecord->type, array('sHtml5Start', 'sHtml5End'))) {

            //correct the sh5_pid if needed
             if ($dc->activeRecord->type == 'sHtml5Start' && 
                 $dc->activeRecord->id != $dc->activeRecord->sh5_pid) {
                 \Database::getInstance()
                    ->prepare('UPDATE ' . $dc->table . ' %s WHERE id = ?')
                    ->set(array('sh5_pid' => $dc->activeRecord->id))
                    ->execute($dc->activeRecord->id);
             }

            //crea or update the corresponding html5 tag
            $util = new TagUtils($dc->table);
            $util->createOrupdateCorresppondingTag($dc->activeRecord);
        }
    }

    /**
     * Deletes the corresponding html5 tag
     * 
     * @param \DataContainer $dc
     * @param int $id
     */
    public static function ondeleteCallback(\DataContainer $dc, $id)
    {
        //if this is not a html5 element, do nothing
        if (in_array($dc->activeRecord->type, array('sHtml5Start', 'sHtml5End'))) {
            $util = new TagUtils($dc->table);
            $util->deleteCorrespondingTag($dc->activeRecord);
        }
    }

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
                $color = Helper::rotateColor();
                static::$elementColors[$objRow->id] = $color;
            }

            $template = new \BackendTemplate('be_semantic_html5_colorizejs');
            $template->id = $objRow->id;
            $template->color = $color;

            $strBuffer .= $template->parse();
        }
        return ($strBuffer);
    }
}
