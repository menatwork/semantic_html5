<?php

/**
 * Contao Open Source CMS
 *
 * @copyright  MEN AT WORK 2013
 * @package    semantic_html5
 * @license    GNU/LGPL
 * @filesource
 */

// Be silenced
//@error_reporting(0);
//@ini_set("display_errors", 0);

/**
 * Class runonce
 */
class RunonceSh5 extends Backend
{

    private $booDebug = FALSE;

    /**
     * Initialize the object
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Run sementic_html5 update
     */
    public function run()
    {
        if ($this->booDebug)
        {
            echo "<h1>DEBUG semantic_html5 runonce</h1>";
            echo "<h2>LOGS</h2>";
        }

        $arrPidResult = $this->Database
                ->prepare("SELECT pid FROM `tl_content` WHERE type = 'semantic_html5' GROUP BY pid")
                ->execute()
                ->fetchAllAssoc();

        $arrArticleResult = array();

        foreach ($arrPidResult AS $arrPid)
        {
            $objResult = $this->Database
                    ->prepare("SELECT id, pid, type, sorting, sh5_pid, sh5_type, sh5_tag FROM `tl_content` WHERE type = 'semantic_html5' AND pid = ? ORDER BY sorting ASC")
                    ->execute($arrPid['pid']);

            while ($objResult->next())
            {
                $arrArticleResult[$arrPid['pid']][] = $objResult->row();
            }
        }

        $arrSets = array();

        foreach ($arrArticleResult AS $pid => $arrContentElems)
        {
            $arrRemainContentElems = $arrContentElems;
            $arrNoEndTag           = array();
            $arrLastID             = count($arrContentElems);

            for ($i = 0; $i <= $arrLastID; $i++)
            {
                if (!key_exists($i, $arrContentElems))
                {
                    continue;
                }

                if ($arrContentElems[$i]['sh5_tag'] == 'start')
                {
                    $intCountToEnd = 0;
                    foreach ($arrRemainContentElems AS $key => $arrContentElem)
                    {
                        if ($arrContentElem == $arrContentElems[$i])
                        {
                            continue;
                        }

                        if ($arrContentElem['sh5_tag'] == 'start')
                        {
                            $intCountToEnd += 1;
                            continue;
                        }
                        else
                        {
                            if ($intCountToEnd == 0)
                            {
                                $arrSets[$pid][$arrContentElems[$i][id]]['sh5_pid'] = $arrContentElems[$i][id];
                                unset($arrRemainContentElems[$i]);

                                $arrSets[$pid][$arrContentElem['id']]['sh5_pid'] = $arrContentElems[$i][id];
                                unset($arrRemainContentElems[$key]);
                                break;
                            }
                            else
                            {
                                $intCountToEnd -= 1;
                            }
                        }
                    }

                    if (is_array($arrRemainContentElems[$i]))
                    {
                        $arrNoEndTag[] = $arrRemainContentElems[$i];
                        unset($arrRemainContentElems[$i]);
                    }
                }
            }

            $arrErrorLogs = array_merge($arrRemainContentElems, $arrNoEndTag);

            if (count($arrErrorLogs) > 0)
            {
                foreach ($arrErrorLogs AS $key => $arrContentElem)
                {
                    if (!$this->booDebug)
                    {
                        $this->log(
                                sprintf(
                                        $GLOBALS['TL_LANG']['SH5']['runonce_update_error_text'], $arrContentElem['id'], $arrContentElem['pid']
                                ), __CLASS__ . '::' . __FUNCTION__, $GLOBALS['TL_LANG']['SH5']['runonce_update_error_action']
                        );
                    }
                    else
                    {
                        var_dump(
                                sprintf(
                                        $GLOBALS['TL_LANG']['SH5']['runonce_update_error_text'], $arrContentElem['id'], $arrContentElem['pid']
                                )
                        );
                    }
                }
            }
        }

        if ($this->booDebug)
        {
            echo "<h2>UPDATE QUERYS</h2>";
        }

        foreach ($arrSets AS $intPid => $arrSet)
        {
            foreach ($arrSet AS $intId => $intSh5Pid)
            {
                if (!$this->booDebug)
                {
                    $this->Database
                            ->prepare("UPDATE tl_content %s WHERE id = ?")
                            ->set($intSh5Pid)
                            ->executeUncached($intId);
                }
                else
                {
                    var_dump("UPDATE tl_content SET sh5_pid = " . $intSh5Pid['sh5_pid'] . " WHERE id = " . $intId);
                }
            }
        }

        if ($this->booDebug)
        {
            $this->debug($arrArticleResult, $arrSets);
        }
    }

    public function debug($arrArticleResult, $arrSets)
    {
        echo "<h2>ARRAYS</h2>";
        foreach ($arrArticleResult AS $pid => $arrContentElem)
        {
            echo '<h3>' . $pid . '</h3><table width="100%"><tr><td>';
            var_dump($arrContentElem);
            echo '</td><td>';
            var_dump($arrSets[$pid]);
            echo '</td></tr></table>';
            echo '========================================================================================================================';
        }
        exit();
    }

}

// Run once
$objRunonceJob = new RunonceSh5();
$objRunonceJob->run();
?>