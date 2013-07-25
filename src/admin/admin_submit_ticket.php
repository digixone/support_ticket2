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
define('HESK_PATH','../');

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/database.inc.php');
require(HESK_PATH . 'inc/email_functions.inc.php');

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

$hesk_error_buffer = array();

$tmpvar['name']	    = hesk_input($_POST['name']) or $hesk_error_buffer['name']=$hesklang['enter_your_name'];
$tmpvar['email']	= hesk_validateEmail($_POST['email'],'ERR',0) or $hesk_error_buffer['email']=$hesklang['enter_valid_email'];
$tmpvar['category'] = hesk_input($_POST['category']) or $hesk_error_buffer['category']=$hesklang['sel_app_cat'];
$tmpvar['priority'] = intval($_POST['priority']);

if ($tmpvar['priority'] < 0 || $tmpvar['priority'] > 3)
{
    $hesk_error_buffer['priority']=$hesklang['sel_app_priority'];
}

$tmpvar['subject']  = hesk_input($_POST['subject']) or $hesk_error_buffer['subject']=$hesklang['enter_ticket_subject'];
$tmpvar['message']  = hesk_input($_POST['message']) or $hesk_error_buffer['message']=$hesklang['enter_message'];

/* Custom fields */
foreach ($hesk_settings['custom_fields'] as $k=>$v)
{
	if ($v['use'] && isset($_POST[$k]))
    {
       	if (is_array($_POST[$k]))
           {
			$tmpvar[$k]='';
			foreach ($_POST[$k] as $myCB)
			{
				$tmpvar[$k].=hesk_input($myCB).'<br />';
			}
			$tmpvar[$k]=substr($tmpvar[$k],0,-6);
           }
           else
           {
    		$tmpvar[$k]=hesk_makeURL(nl2br(hesk_input($_POST[$k])));
           }
	}
    else
    {
    	$tmpvar[$k] = '';
    }
}

/* Generate tracking ID */
$trackingID = hesk_createID();

/* Log who submitted ticket */
$history = sprintf($hesklang['thist7'],hesk_date(),$_SESSION['name'].' ('.$_SESSION['user'].')');

/* Owner */
$owner = 0;
if (hesk_checkPermission('can_assign_others',0))
{
	$owner = intval($_REQUEST['owner']);

	/* If ID is -1 the ticket will be unassigned */
	if ($owner == -1)
	{
		$owner = 0;
	}
    /* Automatically assign owner? */
    elseif ($owner == -2 && $hesk_settings['autoassign'] == 1)
    {
		$autoassign_owner = hesk_autoAssignTicket($tmpvar['category']);
		if ($autoassign_owner)
		{
			$owner = $autoassign_owner['id'];
			$history .= sprintf($hesklang['thist10'],hesk_date(),$autoassign_owner['name'].' ('.$autoassign_owner['user'].')');
		}
        else
        {
        	$owner = 0;
        }
    }
    /* Check for invalid owner values */
	elseif ($owner < 1)
	{
	    $owner = 0;
	}
    else
    {
	    /* Has the new owner access to the selected category? */
		$sql = "SELECT `name`,`isadmin`,`categories` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` WHERE `id`=".hesk_dbEscape($owner)." LIMIT 1";
		$res = hesk_dbQuery($sql);
	    if (hesk_dbNumRows($res) == 1)
	    {
	    	$row = hesk_dbFetchAssoc($res);
	        if (!$row['isadmin'])
	        {
				$row['categories']=explode(',',$row['categories']);
				if (!in_array($tmpvar['category'],$row['categories']))
				{
                	$_SESSION['isnotice'][] = 'category';
					$hesk_error_buffer['owner']=$hesklang['onasc'];
				}
	        }
	    }
	    else
	    {
        	$_SESSION['isnotice'][] = 'category';
	    	$hesk_error_buffer['owner']=$hesklang['onasc'];
	    }
    }
}
elseif (hesk_checkPermission('can_assign_self',0) && hesk_okCategory($tmpvar['category'],0) && !empty($_POST['assing_to_self']))
{
	$owner = intval($_SESSION['id']);
}

/* Notify customer of the ticket? */
$notify = !empty($_POST['notify']) ? 1 : 0;

/* Show ticket after submission? */
$show = !empty($_POST['show']) ? 1 : 0;

/* Attachments */
if ($hesk_settings['attachments']['use'])
{
    require_once(HESK_PATH . 'inc/attachments.inc.php');
    $attachments = array();
    for ($i=1;$i<=$hesk_settings['attachments']['max_number'];$i++)
    {
        $att = hesk_uploadFile($i);
        if ($att !== false && !empty($att))
        {
            $attachments[$i] = $att;
        }
    }
}
$myattachments='';

/* If we have any errors lets store info in session to avoid re-typing everything */
if (count($hesk_error_buffer)!=0)
{
	$_SESSION['iserror'] = array_keys($hesk_error_buffer);

    $_SESSION['as_name']     = $_POST['name'];
    $_SESSION['as_email']    = $_POST['email'];
    $_SESSION['as_category'] = $_POST['category'];
    $_SESSION['as_priority'] = $_POST['priority'];
    $_SESSION['as_subject']  = $_POST['subject'];
    $_SESSION['as_message']  = $_POST['message'];
    $_SESSION['as_owner']    = $owner;
    $_SESSION['as_notify']   = $notify;
    $_SESSION['as_show']     = $show;

	foreach ($hesk_settings['custom_fields'] as $k=>$v)
	{
		if ($v['use'])
		{
			$_SESSION["as_$k"] = isset($_POST[$k]) ? $_POST[$k]: '';
		}
	}

    $tmp = '';
    foreach ($hesk_error_buffer as $error)
    {
        $tmp .= "<li>$error</li>\n";
    }
    $hesk_error_buffer = $tmp;

    $hesk_error_buffer = $hesklang['pcer'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
    hesk_process_messages($hesk_error_buffer,'new_ticket.php');
}

if ($hesk_settings['attachments']['use'] && !empty($attachments))
{
    foreach ($attachments as $myatt)
    {
        $sql = "INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` (`ticket_id`,`saved_name`,`real_name`,`size`) VALUES ('".hesk_dbEscape($trackingID)."', '".hesk_dbEscape($myatt['saved_name'])."', '".hesk_dbEscape($myatt['real_name'])."', '".hesk_dbEscape($myatt['size'])."')";
        $result = hesk_dbQuery($sql);
        $myattachments .= hesk_dbInsertID() . '#' . $myatt['real_name'] .',';
    }
}

$tmpvar['message']=hesk_makeURL($tmpvar['message']);
$tmpvar['message']=nl2br($tmpvar['message']);

$sql = "
INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` (
`trackid`,`name`,`email`,`category`,`priority`,`subject`,`message`,`dt`,`lastchange`,`ip`,`status`,`owner`,`attachments`,`history`,`custom1`,`custom2`,`custom3`,`custom4`,`custom5`,`custom6`,`custom7`,`custom8`,`custom9`,`custom10`,`custom11`,`custom12`,`custom13`,`custom14`,`custom15`,`custom16`,`custom17`,`custom18`,`custom19`,`custom20`
)
VALUES (
'".hesk_dbEscape($trackingID)."',
'".hesk_dbEscape($tmpvar['name'])."',
'".hesk_dbEscape($tmpvar['email'])."',
'".hesk_dbEscape($tmpvar['category'])."',
'".hesk_dbEscape($tmpvar['priority'])."',
'".hesk_dbEscape($tmpvar['subject'])."',
'".hesk_dbEscape($tmpvar['message'])."',
NOW(),
NOW(),
'".hesk_dbEscape($_SERVER['REMOTE_ADDR'])."',
'0',
".hesk_dbEscape($owner).",
'".hesk_dbEscape($myattachments)."',
CONCAT(`history`,'".hesk_dbEscape($history)."'),
'".hesk_dbEscape($tmpvar['custom1'])."',
'".hesk_dbEscape($tmpvar['custom2'])."',
'".hesk_dbEscape($tmpvar['custom3'])."',
'".hesk_dbEscape($tmpvar['custom4'])."',
'".hesk_dbEscape($tmpvar['custom5'])."',
'".hesk_dbEscape($tmpvar['custom6'])."',
'".hesk_dbEscape($tmpvar['custom7'])."',
'".hesk_dbEscape($tmpvar['custom8'])."',
'".hesk_dbEscape($tmpvar['custom9'])."',
'".hesk_dbEscape($tmpvar['custom10'])."',
'".hesk_dbEscape($tmpvar['custom11'])."',
'".hesk_dbEscape($tmpvar['custom12'])."',
'".hesk_dbEscape($tmpvar['custom13'])."',
'".hesk_dbEscape($tmpvar['custom14'])."',
'".hesk_dbEscape($tmpvar['custom15'])."',
'".hesk_dbEscape($tmpvar['custom16'])."',
'".hesk_dbEscape($tmpvar['custom17'])."',
'".hesk_dbEscape($tmpvar['custom18'])."',
'".hesk_dbEscape($tmpvar['custom19'])."',
'".hesk_dbEscape($tmpvar['custom20'])."'
)
";

$res = hesk_dbQuery($sql);

/* Ticket array for later use in e-mails */
$ticket = array(
	'name' 		=> hesk_msgToPlain($tmpvar['name'],1),
	'subject' 	=> hesk_msgToPlain($tmpvar['subject'],1),
	'trackid' 	=> $trackingID,
	'category' 	=> $tmpvar['category'],
	'priority' 	=> $tmpvar['priority'],
    'lastreplier' => hesk_msgToPlain($tmpvar['name'],1),
	'message' 	=> hesk_msgToPlain($tmpvar['message'],1),
    'owner'		=> $owner,
);
foreach ($hesk_settings['custom_fields'] as $k => $v)
{
	$ticket[$k] = $v['use'] ? hesk_msgToPlain($tmpvar[$k],1) : '';
}

/* Notify the customer about the ticket? */
if ($notify)
{
	/* Format e-mail message for customer */
	$msg = hesk_getEmailMessage('new_ticket',$ticket);

	/* Send e-mail */
	hesk_mail($tmpvar['email'],$hesklang['ticket_received'],$msg);
}

/* If ticket is assigned to someone notify them? */
if ($owner && $owner != intval($_SESSION['id']))
{
	/* If we don't have info from auto-assign get it from database */
    if ( ! isset($autoassign_owner['email']) )
    {
		$sql = "SELECT `email`,`isadmin`,`categories`,`notify_assigned` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` WHERE `id`=".hesk_dbEscape($ticket['owner']);
		$res = hesk_dbQuery($sql);

		if (hesk_dbNumRows($res))
		{
			$autoassign_owner = hesk_dbFetchAssoc($res);
		}
    }

	/* Notify the staff? */
    if ($autoassign_owner['notify_assigned'])
    {
		/* Format e-mail message */
		$msg = hesk_getEmailMessage('ticket_assigned_to_you',$ticket,1);

		/* Send e-mail to staff */
		hesk_mail($autoassign_owner['email'],$hesklang['ticket_assigned_to_you'],$msg);
    }
}

/* Ticket unassigned, notify everyone that selected to be notified about unassigned tickets */
elseif ( ! $owner)
{
	$admins=array();
	$sql = "SELECT `email`,`isadmin`,`categories` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` WHERE `id`!=".hesk_dbEscape($_SESSION['id'])." AND `notify_new_unassigned`='1'";
	$result = hesk_dbQuery($sql);
	while ($myuser=hesk_dbFetchAssoc($result))
	{
		/* Is this an administrator? */
		if ($myuser['isadmin'])
		{
			$admins[]=$myuser['email'];
			continue;
		}

		/* Not admin, is he allowed this category? */
		$myuser['categories']=explode(',',$myuser['categories']);
		if (in_array($tmpvar['category'],$myuser['categories']))
		{
			$admins[]=$myuser['email'];
			continue;
		}
	}
	if (count($admins)>0)
	{
		/* Format e-mail message for staff */
		$msg = hesk_getEmailMessage('new_ticket_staff',$ticket,1);

		/* Send e-mail to staff */
		$email=implode(',',$admins);
		hesk_mail($email,$hesklang['new_ticket_submitted'],$msg);
	}
}

/* Unset temporary variables */
unset($tmpvar);
hesk_cleanSessionVars('tmpvar');
hesk_cleanSessionVars('as_name');
hesk_cleanSessionVars('as_email');
hesk_cleanSessionVars('as_category');
hesk_cleanSessionVars('as_priority');
hesk_cleanSessionVars('as_subject');
hesk_cleanSessionVars('as_message');
hesk_cleanSessionVars('as_owner');
hesk_cleanSessionVars('as_notify');
hesk_cleanSessionVars('as_show');
foreach ($hesk_settings['custom_fields'] as $k=>$v)
{
	if ($v['use'])
	{
        hesk_cleanSessionVars("as_$k");
	}
}

/* If ticket has been assigned to the person submitting it lets show a message saying so */
if ($owner && $owner == intval($_SESSION['id']))
{
	$hesklang['new_ticket_submitted'] .= '<br />&nbsp;<br />
    <img src="' . HESK_PATH . 'img/error.png" width="16" height="16" border="0" alt="" style="vertical-align:text-bottom" /> <b>' . $hesklang['taasy'] . '</b>';
}

/* Show the ticket or just the success message */
if ($show)
{
	hesk_process_messages($hesklang['new_ticket_submitted'],'admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999),'SUCCESS');
}
else
{
	hesk_process_messages($hesklang['new_ticket_submitted'].'. <a href="admin_ticket.php?track='.$trackingID.'&Refresh='.mt_rand(10000,99999).'">'.$hesklang['view_ticket'].'</a>','new_ticket.php','SUCCESS');
}
?>
