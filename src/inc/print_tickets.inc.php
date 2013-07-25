<?php
/*******************************************************************************
*  Title: Help Desk Software HESK
*  Version: 2.3 from 15th September 2011
*  Author: Klemen Stirn
*  Website: http://www.hesk.com
********************************************************************************
*  COPYRIGHT AND TRADEMARK NOTICE
*  Copyright 2005-2011 Klemen Stirn. All Rights Reserved.
*  HESK is a registered trademark of Klemen Stirn.

*  The HESK may be used and modified free of charge by anyone
*  AS LONG AS COPYRIGHT NOTICES AND ALL THE COMMENTS REMAIN INTACT.
*  By using this code you agree to indemnify Klemen Stirn from any
*  liability that might arise from it's use.

*  Selling the code for this program, in part or full, without prior
*  written consent is expressly forbidden.

*  Using this code, in part or full, to create derivate work,
*  new scripts or products is expressly forbidden. Obtain permission
*  before redistributing this software over the Internet or in
*  any other medium. In all cases copyright and header must remain intact.
*  This Copyright is in full effect in any country that has International
*  Trade Agreements with the United States of America or
*  with the European Union.

*  Removing any of the copyright notices without purchasing a license
*  is expressly forbidden. To remove HESK copyright notice you must purchase
*  a license for this script. For more information on how to obtain
*  a license please visit the page below:
*  https://www.hesk.com/buy.php
*******************************************************************************/

/* Check if this is a valid include */
if (!defined('IN_SCRIPT')) {die('Invalid attempt');} 

$sql = "SELECT `t1`.* , `t2`.`name` AS `repliername`
FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` AS `t1` LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."users` AS `t2` ON `t1`.`replierid` = `t2`.`id`
WHERE ";

/* Some default settings */
$archive = array(1=>0,2=>0);
$s_my = array(1=>1,2=>1);
$s_ot = array(1=>1,2=>1);
$s_un = array(1=>1,2=>1);

/* Archived */
if (!empty($_GET['archive']))
{
    $archive[1]=1;
    $sql .= '`archive`=\'1\' AND ';
}

/* Assignment */
$fid = 1;
require(HESK_PATH . 'inc/assignment_search.inc.php');

/* Allowed categories */
$sql .= hesk_myCategories();

/* Get all the SQL sorting preferences */

/* --> TICKET STATUS */
$possible_status = array(
0 => 'NEW',
1 => 'WAITING REPLY',
2 => 'REPLIED',
3 => 'RESOLVED (CLOSED)',
4 => 'IN PROGRESS',
5 => 'ON HOLD',
);

$status = $possible_status;

foreach ($status as $k => $v)
{
	if (empty($_GET['s'.$k]))
    {
    	unset($status[$k]);
    }
}

/* How many statuses are we pulling out of the database? */
$tmp = count($status);

/* Create the SQL based on the number of statuses we need */
if ($tmp == 0 || ($tmp == 5 && !isset($status[3])) )
{
	/* Default selection (show all except RESOLVED) */
	$sql .= ' AND `status`!=\'3\' ';
    $status = $possible_status;
    unset($status[3]);
}
elseif ($tmp == 6)
{
	/* Showing tickets with any status, no need to modify the SQL code */
}
else
{
	/* A custom selection of statuses */
	$sql .= ' AND `status` IN (\'' . implode('\',\'', array_keys($status)) . '\') ';
}

/* --> TICKET PRIORITY */
$possible_priority = array(
0 => 'CRITICAL',
1 => 'HIGH',
2 => 'MEDIUM',
3 => 'LOW',
);

$priority = $possible_priority;

foreach ($priority as $k => $v)
{
	if (empty($_GET['p'.$k]))
    {
    	unset($priority[$k]);
    }
}

/* How many priorities are we pulling out of the database? */
$tmp = count($priority);

/* Create the SQL based on the number of priorities we need */
if ($tmp == 0 || $tmp == 4)
{
	/* Nothing or all selected, no need to modify the SQL code */
    $priority = $possible_priority;
}
else
{
	/* A custom selection of priorities */
	$sql .= ' AND `priority` IN (\'' . implode('\',\'', array_keys($priority)) . '\') ';
}

/* --> TICKET CATEGORY */
$category = (isset($_GET['category'])) ? hesk_isNumber($_GET['category']) : 0;
if ($category)
{
    $sql .= ' AND `category`=\''.hesk_dbEscape($category).'\' ';
}

/* Prepare variables used in search and forms */
require_once(HESK_PATH . 'inc/prepare_ticket_search.inc.php');

/* List tickets? */
if (!isset($_SESSION['hide']['ticket_list']))
{
	$href = 'show_tickets.php';
	require_once(HESK_PATH . 'inc/ticket_list.inc.php');
}
?>
