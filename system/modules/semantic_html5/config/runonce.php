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
@error_reporting(0);
@ini_set("display_errors", 0);

/**
 * Class runonce
 */
class RunonceSh5 extends Backend
{

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
                    $strErrorText   = "Could not find a matching semantic_html5 tag for the element (ID: %s) from the article (ID: %s).";
                    $strErrorAction = "Update semantic_html5 tags.";

                    $this->log(
                            sprintf(
                                    $strErrorText, $arrContentElem['id'], $arrContentElem['pid']
                            ), __CLASS__ . '::' . __FUNCTION__, $strErrorAction
                    );
                }
            }
        }

        foreach ($arrSets AS $intPid => $arrSet)
        {
            foreach ($arrSet AS $intId => $intSh5Pid)
            {
                $this->Database
                        ->prepare("UPDATE tl_content %s WHERE id = ?")
                        ->set($intSh5Pid)
                        ->executeUncached($intId);
            }
        }
    }

}

// Run once
$objRunonceJob = new RunonceSh5();
$objRunonceJob->run();
?>