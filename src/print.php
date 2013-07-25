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

define('IN_SCRIPT',1);
define('HESK_PATH','./');

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/database.inc.php');

hesk_session_start();
hesk_dbConnect();

$trackingID = strtoupper(hesk_input($_GET['track'],$hesklang['trackID_not_found']));

/* Get ticket info */
$sql = "SELECT `t1`.* , `t2`.name AS `repliername`
FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` AS `t1` LEFT JOIN `".hesk_dbEscape($hesk_settings['db_pfix'])."users` AS `t2` ON `t1`.`replierid` = `t2`.`id`
WHERE `trackid`='".hesk_dbEscape($trackingID)."' LIMIT 1";
$res = hesk_dbQuery($sql);
if (hesk_dbNumRows($res) != 1)
{
	hesk_error($hesklang['ticket_not_found']);
}
$ticket = hesk_dbFetchAssoc($res);

/* Get category name and ID */
$sql = "SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `id`=".hesk_dbEscape($ticket['category'])." LIMIT 1";
$res = hesk_dbQuery($sql);

/* If this category has been deleted use the default category with ID 1 */
if (hesk_dbNumRows($res) != 1)
{
	$sql = "SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` WHERE `id`=1 LIMIT 1";
	$res = hesk_dbQuery($sql);
}
$category = hesk_dbFetchAssoc($res);

/* Get replies */
$sql = "SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`='".hesk_dbEscape($ticket['id'])."' ORDER BY `id` ASC";
$res  = hesk_dbQuery($sql);
$replies = hesk_dbNumRows($res);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title><?php echo $hesk_settings['hesk_title']; ?></title>
<meta content="text/html; charset=<?php echo $hesklang['ENCODING']; ?>">
<style type="text/css">
body,p,td
{
    color : black;
    font-family : Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size : <?php echo $hesk_settings['print_font_size']; ?>px;
}
</style>
</head>
<body onload="window.print()">

<?php
/* Ticket status */
switch ($ticket['status'])
{
	case 0:
		$ticket['status']=$hesklang['open'];
		break;
	case 1:
		$ticket['status']=$hesklang['wait_staff_reply'];
		break;
	case 2:
		$ticket['status']=$hesklang['wait_cust_reply'];
		break;
	case 4:
		$ticket['status']=$hesklang['in_progress'];
		break;
	case 5:
		$ticket['status']=$hesklang['on_hold'];
		break;
	default:
		$ticket['status']=$hesklang['closed'];
}

/* Ticket priority */
switch ($ticket['priority'])
{
	case 0:
		$ticket['priority']='<b>'.$hesklang['critical'].'</b>';
		break;
	case 1:
		$ticket['priority']='<b>'.$hesklang['high'].'</b>';
		break;
	case 2:
		$ticket['priority']=$hesklang['medium'];
		break;
	default:
		$ticket['priority']=$hesklang['low'];
}

/* Set last replier name */
if ($ticket['lastreplier'])
{
	if (empty($ticket['repliername']))
	{
		$ticket['repliername'] = $hesklang['staff'];
	}
}
else
{
	$ticket['repliername'] = $ticket['name'];
}

/* Other variables that need processing */
$ticket['dt'] = hesk_date($ticket['dt']);
$ticket['lastchange'] = hesk_date($ticket['lastchange']);
$random=mt_rand(10000,99999);

/* Print the ticket */
echo "

<p>$hesklang[subject]: <b>$ticket[subject]</b><br />

$hesklang[trackID]: $trackingID<br />
$hesklang[ticket_status]: $ticket[status]<br />
$hesklang[created_on]: $ticket[dt]<br />
$hesklang[last_update]: $ticket[lastchange]<br />
$hesklang[last_replier]: $ticket[repliername]<br />
$hesklang[category]: $category[name]<br />
$hesklang[replies]: $replies<br />
$hesklang[priority]: $ticket[priority]

<hr />

$hesklang[date]: $ticket[dt]<br />
$hesklang[name]: $ticket[name]<br />
$hesklang[email]: $ticket[email]<br />
$hesklang[ip]: $ticket[ip]<br />

";

/* custom fields before message */
foreach ($hesk_settings['custom_fields'] as $k=>$v)
{
	if ($v['use'] && $v['place']==0)
    {
    	echo $v['name'].': '.$ticket[$k].'<br />';
    }
}

echo "<b>$hesklang[message]:</b><br />$ticket[message]";

/* custom fields after message */
$br = 1;
foreach ($hesk_settings['custom_fields'] as $k=>$v)
{
	if ($v['use'] && $v['place'])
    {
    	if ($br)
        {
        	echo '<br /><br />';
            $br = 0;
        }
    	echo $v['name'].': '.$ticket[$k].'<br />';
    }
}

echo '<hr />';

while ($reply = hesk_dbFetchAssoc($res))
{
	$reply['dt'] = hesk_date($reply['dt']);
	echo "
	$hesklang[date]: $reply[dt]<br />
	$hesklang[name]: $reply[name]<br />
	<b>$hesklang[message]:</b><br />
	$reply[message]

	<hr />

	";
}

echo $hesklang['end_ticket'];
?>
</p>

</body>
</html>
