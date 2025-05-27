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

use Contao\BackendTemplate;
use Contao\DataContainer;
use Contao\System;

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
     * Array tempData
     */
    private static $tempData = [];

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
     * @param DataContainer $dc
     */
    public static function onsubmitCallback(DataContainer $dc)
    {
        //if this is not a html5 element, do nothing
        if (in_array($dc->activeRecord->type, array('sHtml5Start', 'sHtml5End'))) {

            $item = $dc->activeRecord;
            $util = new TagUtils($dc->table);

            //correct the sh5_pid if needed
            if ($item->type == 'sHtml5Start' &&
             $item->id != $item->sh5_pid) {
                $item = $util->updateTag($item->id, array('sh5_pid' => $item->id));
            }

            //create or update the corresponding html5 tag
            $util->createOrUpdateCorresppondingTag($item);
        }
    }

    /**
     * Deletes the corresponding html5 tag
     *
     * @param DataContainer $dc
     * @param int $id
     */
    public static function ondeleteCallback(DataContainer $dc, $id)
    {
        //if this is not a html5 element, do nothing
        if (in_array($dc->activeRecord->type, array('sHtml5Start', 'sHtml5End'))) {
            $util = new TagUtils($dc->table);
            $util->deleteCorrespondingTag($dc->activeRecord);
        }
    }

    /**
     * This methods corrects the hml5-elements after using the copy function of
     * the tl_page table
     *
     * @param type $id
     * @param DataContainer $dc
     */
    public static function oncopyPageCallback($id, DataContainer $dc)
    {

        $pages = array($id);

        //fetch the child pages, if needed
        if (\Input::get('childs')) {
            $pages = array_merge($pages, \Database::getInstance()->getChildRecords($id, 'tl_page'));

        }

        //fetch all html5 start elemnts and update them the end elements will be corrected automatically
        $elements = \Database::getInstance()
                ->prepare(
                        sprintf(
                                'SELECT * FROM tl_content '
                                . 'WHERE type = "sHtml5Start" '
                                . 'AND pid IN '
                                . '(SELECT id FROM tl_article WHERE pid in (%s))',
                                implode(',', array_fill(0, count($pages), '?')))
                        )->execute($pages);

        //return if no elements were found
        if ($elements->numRows == 0) {
            return;
        }

        $util = new TagUtils('tl_content');

        while ($elements->next()) {
            $util->createOrUpdateCorresppondingTag($elements, true);
        }
    }

    /**
     * This methods corrects the hml5-elements after using the copy function of
     * the tl_article table
     *
     * @param type $id
     * @param DataContainer $dc
     */
    public static function oncopyArticleCallback($id, DataContainer $dc)
    {

        //fetch all html5 start elemnts and update them the end elements will be corrected automatically
        $elements = \Database::getInstance()
                ->prepare('SELECT * FROM tl_content WHERE type = "sHtml5Start" AND pid = ?')
                ->execute($id);

        //return if no elements were found
        if ($elements->numRows == 0) {
            return;
        }

        $util = new TagUtils('tl_content');

        while ($elements->next()) {
            $util->createOrUpdateCorresppondingTag($elements, true);
        }
    }

    /**
     * This methods corrects the hml5-elements after using the copy function of
     * the tl_content table
     *
     * @param int $id The id of the new element
     * @param DataContainer $dc The datad container
     */
    public static function oncopyContentCallback($id, DataContainer $dc)
    {

        //only handle copyAll cases. If only a single element was copied the
        //onsubmit callback will handle the correction
        if (\Input::get('act') == 'copyAll') {

            $util = new TagUtils($dc->table);
            $newElement = $util->getTag($id);

            //if the new element was found and is a type of html5 element
            if ($newElement !== null) {

                //save the old sh5_pid
                $oldPid = $newElement->sh5_pid;

                if ($newElement->type === 'sHtml5Start') {
                    //update the sh5_pid
                    $newElement = $util->updateTag($id, array('sh5_pid' => $id));

                    //create an end tag, just in case it was not copied
                    $correspondingId = $util->createOrUpdateCorresppondingTag($newElement);

                    //Save the new id if available
                    if ($correspondingId !== null) {
                        self::$tempData[$oldPid]['end'] = $correspondingId;
                    }

                    //also save the new start tag
                    self::$tempData[$oldPid]['start'] = $newElement->id;

                } else {
                    //if an end tag was allready created delete it
                    if (self::$tempData[$oldPid]['end'] !== null) {
                        $util->deleteTag(self::$tempData[$oldPid]['end']);
                    }

                    //get the new sh5_pid
                    $newPid = (self::$tempData[$oldPid]['start']) ? self::$tempData[$oldPid]['start'] : $id;

                    //update the new element and the corresponding tag
                    $newElement = $util->updateTag($id, array('sh5_pid' => $newPid));
                    $util->createOrUpdateCorresppondingTag($newElement);
                }
            }
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
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request) && ($objRow->type == 'sHtml5Start' || $objRow->type == 'sHtml5End')) {
            //get the color of the parent start-tag or rotate the color
            if ($objRow->type == 'sHtml5End') {
                $color = self::$elementColors[$objRow->sh5_pid] ?? null;
            } else {
                $color = Helper::rotateColor();
                self::$elementColors[$objRow->id] = $color;
            }

            $template = new BackendTemplate('be_semantic_html5_colorizejs');
            $template->id = $objRow->id;
            $template->color = $color;

            $strBuffer .= $template->parse();
        }
        return ($strBuffer);
    }


    /**
     * Returns all valid html5 tag for the given datacontainer
     *
     * @param DataContainer $dc
     * @return array The array with the valid html5 tags
     */
    public function getHtml5Tags(DataContainer $dc) {

        return $GLOBALS['TL_HTML5']['tags'][$dc->table];
    }
}
