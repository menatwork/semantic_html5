<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  MEN AT WORK 2012
 * @package    semantic_html5
 * @license    GNU/GPL 2
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
            $arrNoEndTag = array();
            $arrLastID = count($arrContentElems);

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