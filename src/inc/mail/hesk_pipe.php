#!/usr/bin/php -q
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
define('HESK_PATH','../../');

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/database.inc.php');
require(HESK_PATH . 'inc/email_functions.inc.php');

/* Is this feature enabled? */
if (!$hesk_settings['email_piping'])
{
	die($hesklang['epd']);
}

/* Include email parsing functions */
require(HESK_PATH . 'inc/mail/rfc822_addresses.php');
require(HESK_PATH . 'inc/mail/mime_parser.php');
require(HESK_PATH . 'inc/mail/email_parser.php');

/* parse the incoming email */
$results = parser();

/* Variables */
$tmpvar['name']	    = hesk_input($results['from'][0]['name']) or $tmpvar['name'] = $hesklang['unknown'];
$tmpvar['email']	= hesk_validateEmail($results['from'][0]['address'],'ERR',0);
$tmpvar['category'] = 1;
$tmpvar['priority'] = 3;
$tmpvar['subject']  = hesk_input($results['subject']) or $tmpvar['subject'] = '['.$hesklang['unknown'].']';
$tmpvar['message']  = hesk_input($results['message']);
$_SERVER['REMOTE_ADDR'] = $hesklang['unknown'];
$IS_REPLY = 0;
$trackingID = '';

/* Any important info missing? */
if (!$tmpvar['email'] || !$tmpvar['message'])
{
	return NULL;
}

/* Process the message */
if (!empty($results['encoding']))
{
	if (strtolower($results['encoding']) != strtolower($hesklang['ENCODING']))
    {
		$tmpvar['message']=mb_convert_encoding($tmpvar['message'],$hesklang['ENCODING'],$results['encoding']);
    }
}
$tmpvar['message']=hesk_makeURL($tmpvar['message']);
$tmpvar['message']=nl2br($tmpvar['message']);

/* Process the subject */
if (!empty($results['subject_encoding']))
{
	if (strtolower($results['subject_encoding']) != strtolower($hesklang['ENCODING']))
    {
		$tmpvar['subject']=mb_convert_encoding($tmpvar['subject'],$hesklang['ENCODING'],$results['subject_encoding']);
    }
}

/* Connect to the database */
hesk_dbConnect();

/* Generate tracking ID */
$trackingID = hesk_createID();

/* Process attachments */
$myattachments='';
$num = 0;
if ($hesk_settings['attachments']['use'] && isset($results['attachments'][0]))
{
    foreach ($results['attachments'] as $k => $v)
    {
    	/* Check number of attachments, delete any over max number */
        if ($num == $hesk_settings['attachments']['max_number'])
        {
            break;
        }

        /* Check file extension */
        $myatt['real_name'] = $v['orig_name'];
		$ext = strtolower(strrchr($myatt['real_name'], "."));
		if (!in_array($ext,$hesk_settings['attachments']['allowed_types']))
		{
			continue;
		}

        /* Check file size */
        $myatt['size'] = $v['size'];
		if ($myatt['size'] > ($hesk_settings['attachments']['max_size']*1024))
		{
			continue;
		}

		/* Generate a random file name */
		$myatt['real_name'] = str_replace(array('/','\\','#',',',' '), array('','','','','_'),$myatt['real_name']);
		$useChars='AEUYBDGHJLMNPQRSTVWXZ123456789';
		$tmp = $useChars{mt_rand(0,29)};
		for($j=1;$j<10;$j++)
		{
		    $tmp .= $useChars{mt_rand(0,29)};
		}
	    $myatt['saved_name'] = substr($trackingID . '_' . md5($tmp . $myatt['real_name']), 0, 200) . $ext;

        /* Rename the temporary file */
        rename($v['stored_name'],HESK_PATH.'attachments/'.$myatt['saved_name']);

        /* Insert into database */
        $sql = "INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` (`ticket_id`,`saved_name`,`real_name`,`size`) VALUES ('".hesk_dbEscape($trackingID)."', '".hesk_dbEscape($myatt['saved_name'])."', '".hesk_dbEscape($myatt['real_name'])."', '".hesk_dbEscape($myatt['size'])."')";
        $result = hesk_dbQuery($sql);
        $myattachments .= hesk_dbInsertID() . '#' . $myatt['real_name'] .',';

        $num++;
    }
}

/* Delete the temporary files */
deleteAll($results['tempdir']);

/* Auto assign tickets if aplicable */
$history = sprintf($hesklang['thist11'],hesk_date());
$tmpvar['owner'] = 0;

$autoassign_owner = hesk_autoAssignTicket($tmpvar['category']);
if ($autoassign_owner)
{
	$tmpvar['owner'] = $autoassign_owner['id'];
    $history .= sprintf($hesklang['thist10'],hesk_date(),$autoassign_owner['name'].' ('.$autoassign_owner['user'].')');
}

/* Insert the ticket into the database */
foreach ($hesk_settings['custom_fields'] as $k=>$v)
{
	$tmpvar[$k] = '';
}

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
'".hesk_dbEscape($tmpvar['owner'])."',
'".hesk_dbEscape($myattachments)."',
'".hesk_dbEscape($history)."',
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

$result = hesk_dbQuery($sql);

/* Ticket array for later use in e-mails */
$ticket = array(
	'name' 		=> hesk_msgToPlain($tmpvar['name'],1),
	'subject' 	=> hesk_msgToPlain($tmpvar['subject'],1),
	'trackid' 	=> $trackingID,
	'category' 	=> $tmpvar['category'],
	'priority' 	=> $tmpvar['priority'],
    'lastreplier' => hesk_msgToPlain($tmpvar['name'],1),
	'message' 	=> hesk_msgToPlain($tmpvar['message'],1),
    'owner'		=> 0,
);

foreach ($hesk_settings['custom_fields'] as $k => $v)
{
	$ticket[$k] = '';
}

/* Format e-mail message for customer */
$msg = hesk_getEmailMessage('new_ticket',$ticket);

/* Send e-mail */
hesk_mail($tmpvar['email'],$hesklang['ticket_received'],$msg);

/*** Need to notify any staff? ***/
/* --> From autoassign? */
if ($tmpvar['owner'] && $autoassign_owner['notify_assigned'])
{
	/* Format e-mail message for staff */
	$msg = hesk_getEmailMessage('ticket_assigned_to_you',$ticket,1);

	/* Send e-mail to staff */
	hesk_mail($autoassign_owner['email'],$hesklang['ticket_assigned_to_you'],$msg);
}

/* --> No autoassign, find and notify appropriate staff */
elseif ( ! $tmpvar['owner'] )
{
	$admins=array();
	$sql = "SELECT `email`,`isadmin`,`categories` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` WHERE `notify_new_unassigned`='1'";
	$res = hesk_dbQuery($sql);
	while ($myuser=hesk_dbFetchAssoc($res))
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
} // END not autoassign

return NULL;
?>
