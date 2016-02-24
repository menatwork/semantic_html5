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
 * Generall Helper Class for Backend realted functions
 */
class Helper
{

    private static $rotatingColor = .2;

    /**
     * Rotate the color and return a new color
     * @return String the hex string of the new color
     */
    public static function rotateColor()
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

    /**
     * Create a new end tag or update the existing one.
     * @param \Datacontainer $dc
     */
    public static function createOrUpdateEndTag($data, $table){

        //select all corresponding end/start tags
        $type = ($data['type'] == 'sHtml5Start') ? 'sHtml5End' : 'sHtml5Start';
        $endTags = \Database::getInstance()
                ->prepare('SELECT * FROM ' . $table . ' WHERE sh5_pid = ? and type = ?')
                ->execute($data['id'], $type);

        //create a new tag if none was found
        if ($endTags->numRows == 0){
            //change the type and create
            $data['type'] = $type;
            self::createTag($data, $table);
        }else{
            $blnDelete = false;
            //update or delete the existing one
            while($endTags->next()) {
                //update the first element, delete the rest
                if (!$blnDelete) {
                    \Database::getInstance()
                        ->prepare("UPDATE " . $table . " %s WHERE id = ?")
                        ->set(array('sh5_pid' => $data['id']))
                        ->execute($endTags->id);
                    $blnDelete = true;
                } else {
                    self::deleteTag($endTags->row(), $table);
                }
            }
        }
    }


    /**
     * Create tag
     */
    private static function createTag($data, $table)
    {

        if ($data['type'] == 'sHtml5Start'){
            $data['sh5_pid'] = 0;
            $data['sorting'] = $data['sorting'] - 1;
        }else{
            $data['sh5_pid'] = $data['id'];
            $data['sorting'] = $data['sorting'] + 1;
        }

        $data['tstamp']  = time();
        unset($data['id']);

        // Insert the tag
        $result = \Database::getInstance()
                ->prepare("INSERT INTO " . $table . " %s")
                ->set($data)
                ->execute();

        return $result->insertId;
    }

    /**
     * Create tag
     */
    private static function deleteTag($data, $table)
    {
        //make sure not to delete the wrong tags
        if (!in_array($data['type'], array('sHtml5Start', 'sHtml5End'))) {
            //ToDo: Log a waring here!
            return;
        }

        \Database::getInstance()
                ->prepare('DELETE FROM ' . $table . ' WHERE id = ?')
                ->execute($data['id']);
    }
}
