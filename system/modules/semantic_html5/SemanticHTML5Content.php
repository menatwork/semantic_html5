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
 * Class SemanticHTML5Content
 */
class SemanticHTML5Content extends tl_content
{
    static protected $defaultAddCteTypeCallback = array('tl_content', 'addCteType');

    static public function initDataContainer()
    {
        static::$defaultAddCteTypeCallback = $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'];
        $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'] = array('SemanticHTML5Content', 'addCteType');
    }

    private static $rotatingColor = .2;

    private static $elementColors = [];

    protected static $arrContentElements = null;

    /**
     * Add the type of content element
     * @param array
     * @return string
     */
    public function addCteType($arrRow)
    {
        $callback = static::$defaultAddCteTypeCallback;
        $callbackObject = in_array('getInstance', get_class_methods($callback[0]))
            ? $callback[0]::getInstance()
            : new $callback[0]();
        $callbackMethod = static::$defaultAddCteTypeCallback[1];

        // Build level for all elements
        if (self::$arrContentElements == null)
        {
            $arrSh5Stack = array();
            self::$arrContentElements = array();

            // Support GlobalContentelements extension if installed
            $where = array('', null);
            if (in_array('GlobalContentelements', $this->Config->getActiveModules()))
            {
                $where = array
                    (
                    ' AND do=?',
                    $this->Input->get('do')
                );
            }

            $arrResult = $this->Database
                    ->prepare('SELECT * FROM tl_content WHERE pid=?' . $where[0] . ' ORDER BY sorting')
                    ->execute($this->Input->get('id'), $where[1])
                    ->fetchAllAssoc();

            foreach ($arrResult as $value)
            {
                // Check for sh5 start and end tags
                if ($value['type'] == 'semantic_html5' && $value['sh5_tag'] == 'start')
                {
                    $arrSh5Stack[$value['id']] = true;
                }

                // Add level setting
                if (count($arrSh5Stack) != 0)
                {
                    self::$arrContentElements[$value['id']] = count($arrSh5Stack);
                }

                if ($value['type'] == 'semantic_html5' && $value['sh5_tag'] == 'end')
                {
                    unset($arrSh5Stack[$value['sh5_pid']]);
                }
            }
        }

        $strReturn = '';

        // Add rendering settings
        if (count(self::$arrContentElements) != 0 && array_key_exists($arrRow['id'], self::$arrContentElements))
        {
            $intLevel = self::$arrContentElements[$arrRow['id']];

            if ($arrRow['type'] == 'semantic_html5')
            {
                for ($i = 0; $i < $intLevel; $i++)
                {
                    if ($i == 0)
                    {
                        $strReturn .= '<div>';
                    }
                    else
                    {
                        $strReturn .= '<div>';
                    }
                }

                $strReturn .= $callbackObject->$callbackMethod($arrRow);

                if ($arrRow['type'] == 'semantic_html5')
                {
                    $strReturn = $this->colorize($strReturn, $arrRow);
                    $strReturn = str_replace('limit_height', '', $strReturn);
                    $strReturn = str_replace('h64', '', $strReturn);
                }

                for ($i = 0; $i < $intLevel; $i++)
                {
                    $strReturn .= '</div>';
                }
            }
            else
            {
                for ($i = 0; $i < $intLevel + 1; $i++)
                {
                    if ($i == 0)
                    {
                        $strReturn .= '<div>';
                    }
                    else
                    {
                        $strReturn .= '<div>';
                    }
                }

                $strReturn .= $callbackObject->$callbackMethod($arrRow);

                if ($arrRow['type'] == 'semantic_html5')
                {
                    $strReturn = $this->colorize($strReturn, $arrRow);
                    $strReturn = str_replace('limit_height', '', $strReturn);
                    $strReturn = str_replace('h64', '', $strReturn);
                }

                for ($i = 0; $i < $intLevel + 1; $i++)
                {
                    $strReturn .= '</div>';
                }
            }
        }
        else
        {
            $strReturn = $callbackObject->$callbackMethod($arrRow);

            if ($arrRow['type'] == 'semantic_html5')
            {
                $strReturn = $this->colorize($strReturn, $arrRow);
                $strReturn = str_replace('limit_height', '', $strReturn);
                $strReturn = str_replace('h64', '', $strReturn);
            }
        }

        return $strReturn;
    }

    /**
     * Check the current element and return false if is semantic_html5 endtag
     *
     * @return boolean
     */
    public static function checkForTag()
    {
        $objInput = Input::getInstance();

        if ($objInput->get('act') == 'edit' || $objInput->get('act') == 'editAll')
        {
            // Get current element
            $objElem = Database::getInstance()
                    ->prepare("SELECT * FROM tl_content WHERE id = ?")
                    ->limit(1)
                    ->execute($objInput->get('id'));

            if ($objElem->sh5_tag == 'start')
            {
                return TRUE;
            }
        }

        return false;
    }

    /**
     * Merge the default-tags and the customer-tags array together and return them
     *
     * @return array
     */
    public function optionsCallbackType()
    {
        $arrCustomerTags = array();
        if (strlen($GLOBALS['TL_CONFIG']['sh5_customer_tags']))
        {
            $arrCustomerTags = trimsplit(',', $GLOBALS['TL_CONFIG']['sh5_customer_tags']);
        }

        return array_merge(array_keys($GLOBALS['TL_HTML5']), $arrCustomerTags);
    }

    /**
     * Insert tl_content semantic_html5_end element after the current if not exists and update if exists
     *
     * @param DataContainer $dc
     */
    public function onsubmitCallback(DataContainer $dc)
    {
        // Get current record
        $objElement = $dc->activeRecord;

        // Check if we have a semantic_html5 start element
        if ($objElement->type == 'semantic_html5' && $objElement->sh5_tag == 'start')
        {
            if ($objElement->id != $objElement->sh5_pid)
            {
                $this->Database
                        ->prepare("UPDATE tl_content %s WHERE id = ?")
                        ->set(array('sh5_pid' => $objElement->id))
                        ->executeUncached($objElement->id);
            }

            // Check if we have allready a semantic_html end tag
            $objElementEnd = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE sh5_pid = ? AND type = 'semantic_html5' AND sh5_tag = 'end'")
                    ->limit(1)
                    ->execute($objElement->id);

            // If we have no end tag, create one
            if ($objElementEnd->numRows == 0)
            {
                SemanticHTML5Helper::getInstance()->createEndTag($objElement);
            }
            // Else update endtag
            else
            {
                // Update endTag with sh5_type
                $this->Database
                        ->prepare("UPDATE tl_content %s WHERE sh5_pid = ?")
                        ->set(array('sh5_type' => $objElement->sh5_type, 'sh5_additional' => $objElement->sh5_additional))
                        ->executeUncached($objElement->id);
            }
        }
        // Check if the current element has a semantic_html5 end tag and delete it.
        else
        {
            $objElementsEnd = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE sh5_pid = ? AND type = 'semantic_html5' AND sh5_tag = 'end'")
                    ->execute($objElement->id);

            if ($objElementsEnd->numRows != 0)
            {
                while ($objElementsEnd->next())
                {
                    $this->insertUndo("DELETE FROM tl_content WHERE id = " . $objElementsEnd->id, "SELECT * FROM tl_content WHERE id = " . $objElementsEnd->id, "tl_content");

                    $this->Database
                            ->prepare("DELETE FROM tl_content WHERE id = ?")
                            ->execute($objElementsEnd->id);
                }
            }
        }
    }

    /**
     * Delets the next tl_content semantic_html5_end element
     *
     * @param DataContainer $dc
     */
    public function ondeleteCallback(DataContainer $dc)
    {
        // Get current record
        $objElement = $dc->activeRecord;

        // Check if we have a semantic_html5 start or end element
        if ($objElement->type == 'semantic_html5' && $objElement->sh5_tag == 'start')
        {
            $objEndElement = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE type = 'semantic_html5' AND sh5_tag = 'end' AND pid = ? AND sh5_pid = ?")
                    ->limit(1)
                    ->executeUncached($objElement->pid, $objElement->id);

            // Check if we have an end element
            if ($objEndElement->numRows != 0)
            {
                $this->insertUndo("DELETE FROM tl_content WHERE id = " . $objEndElement->id, "SELECT * FROM tl_content WHERE id IN(" . $objEndElement->id . ", " . $objElement->id . ")", "tl_content");

                $this->Database
                        ->prepare("DELETE FROM tl_content WHERE id = ?")
                        ->execute($objEndElement->id);
            }
        }
        else if ($objElement->type == 'semantic_html5' && $objElement->sh5_tag == 'end')
        {
            $objStartElement = $this->Database
                    ->prepare("SELECT * FROM tl_content WHERE id = ? AND type = 'semantic_html5' AND sh5_tag = 'start' AND pid = ? AND sh5_pid = ?")
                    ->limit(1)
                    ->executeUncached($objElement->sh5_pid, $objElement->pid, $objElement->sh5_pid);

            // Check if we have a start element
            if ($objStartElement->numRows != 0)
            {
                $this->insertUndo("DELETE FROM tl_content WHERE id = " . $objStartElement->id, "SELECT * FROM tl_content WHERE id IN(" . $objStartElement->id . ", " . $objElement->id . ")", "tl_content");

                $this->Database
                        ->prepare("DELETE FROM tl_content WHERE id = ?")
                        ->execute($objStartElement->id);
            }
        }
    }

    /**
     * Insert additional delete from appendant tag to contao undo table
     *
     * @param type $strSourceSQL
     * @param type $strSaveSQL
     * @param type $strTable
     * @return type
     */
    protected function insertUndo($strSourceSQL, $strSaveSQL, $strTable)
    {
        // Load row
        $arrResult = $this->Database
                ->prepare($strSaveSQL)
                ->executeUncached()
                ->fetchAllAssoc();

        // Check if we have a result
        if (count($arrResult) == 0)
        {
            return;
        }

        // Save information in array
        $arrSave = array();
        foreach ($arrResult as $value)
        {
            $arrSave[$strTable][] = $value;
        }

        $strPrefix = '<span style="color:#b3b3b3; padding-right:3px;">(semantic_html5)</span>';

        // Write into undo
        $this->Database
                ->prepare("INSERT INTO tl_undo (pid, tstamp, fromTable, query, affectedRows, data) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute($this->User->id, time(), $strTable, $strPrefix . $strSourceSQL, count($arrSave[$strTable]), serialize($arrSave));
    }

    private function colorize($strReturn, $arrRow)
    {
        if ('end' === $arrRow['sh5_tag']) {
            $pid   = $arrRow['sh5_pid'];
            $color = static::$elementColors[$pid];
        } else {
            $id    = $arrRow['id'];
            $color = $this->rotateColor();

            static::$elementColors[$id] = $color;
        }

        $script = <<<'SCRIPT'
<script>
(function(){
    var element = document.getElementById('cte_%s');
    var element = element.parentNode;
    while (element) {
        var classes = element.getAttribute('class');
        if (classes) {
        console.log(element);
        console.log(classes);
        console.log(classes.indexOf('tl_content'));
        }
        if (classes && -1 != classes.indexOf('tl_content')) {
            element.setAttribute(
                'style',
                'border-left: 3px solid %s; padding-left: 3px;'
            );
            return;
        }
        element = element.parentNode;
    }
})();
</script>
SCRIPT;

        $strReturn = str_replace(
            'class="cte_type',
            sprintf('id="cte_%s" class="cte_type', $arrRow['id']),
            $strReturn
        );
        $strReturn .= sprintf(
            $script,
            $arrRow['id'],
            $color
        );

        return $strReturn;
    }

    private function currentColor()
    {
        return $this->HSVtoRGB(static::$rotatingColor, 1, .8);
    }

    private function rotateColor()
    {
        $color = $this->currentColor();

        static::$rotatingColor += .7;

        if (static::$rotatingColor > 1) {
            static::$rotatingColor -= 1;
        }

        return $color;
    }

    /**
     * @see http://stackoverflow.com/a/3597447
     */
    private function HSVtoRGB($hue, $saturation, $value)
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

?>