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

/* Get includes */
if ($hesk_settings['smtp'])
{
	require(HESK_PATH . 'inc/mail/smtp.php');
	if (strlen($hesk_settings['stmp_user']) || strlen($hesk_settings['stmp_password']))
	{
		require(HESK_PATH . 'inc/mail/sasl/sasl.php');
	}
}

function hesk_mail($to,$subject,$message) {
	global $hesk_settings, $hesklang;

    /* Use PHP's mail function */
	if ( ! $hesk_settings['smtp'])
    {
    	/* Set additional headers */
		$headers = "From: $hesk_settings[noreply_mail]\n";
		$headers.= "Reply-To: $hesk_settings[noreply_mail]\n";
		$headers.= "Return-Path: $hesk_settings[webmaster_mail]\n";
		$headers.= "Content-Type: text/plain; charset=".$hesklang['ENCODING'];

		/* Send using PHP mail() function */
        ob_start();
		mail($to,$subject,$message,$headers);
        $tmp = trim(ob_get_contents());
        ob_end_clean();

        return (strlen($tmp)) ? $tmp : true;
    }

    /* Use a SMTP server directly instead */
	$smtp = new smtp_class;
	$smtp->host_name	= $hesk_settings['smtp_host_name'];
	$smtp->host_port	= $hesk_settings['stmp_host_port'];
	$smtp->timeout		= $hesk_settings['stmp_timeout'];
	$smtp->user			= $hesk_settings['stmp_user'];
	$smtp->password		= $hesk_settings['stmp_password'];

    /* Send the e-mail using SMTP */
    $to_arr = explode(',',$to);
	if($smtp->SendMessage($hesk_settings['noreply_mail'],$to_arr,array(
				"From: $hesk_settings[noreply_mail]",
				"To: $to",
                "Reply-To: $hesk_settings[noreply_mail]",
                "Return-Path: $hesk_settings[webmaster_mail]",
				"Subject: " . $subject,
				"Date: ".strftime("%a, %d %b %Y %H:%M:%S %Z"),
                "Content-Type: text/plain; charset=".$hesklang['ENCODING']
			), $message))
    {
    	return true;
    }
	else
    {
    	$error = $hesklang['cnsm'].' '.$to;

		if ($hesk_settings['debug_mode'])
        {
        	$error .= "\n".$hesklang['error'].": ".$smtp->error."\n";
        }

		return $error;
    }

} // END hesk_mail


function hesk_getEmailMessage($eml_file, $ticket, $is_admin=0, $is_ticket=1, $just_message=0) {
	global $hesk_settings, $hesklang;

    $valid_emails = array('category_moved','forgot_ticket_id','new_reply_by_customer','new_reply_by_staff','new_ticket','new_ticket_staff','ticket_assigned_to_you','new_pm');

    if (!in_array($eml_file,$valid_emails))
    {
    	hesk_error($hesklang['inve']);
    }

    $eml_file = 'language/' . $hesk_settings['languages'][$hesk_settings['language']]['folder'] . '/emails/' . $eml_file . '.txt';

    if (file_exists(HESK_PATH . $eml_file))
    {
		$msg = file_get_contents(HESK_PATH . $eml_file);
    }
    else
    {
    	hesk_error($hesklang['emfm'].': '.$eml_file);
    }

    /* Return just the message without any processing? */
    if ($just_message)
    {
    	return $msg;
    }

    /* If it's not a ticket-related mail (like "a new PM") just process quickly */
    if (!$is_ticket)
    {
		$trackingURL = $hesk_settings['hesk_url'] . '/admin/mail.php?a=read&id=' . intval($ticket['id']);

		$msg = str_replace('%%NAME%%',		stripslashes($ticket['name'])	,$msg);
		$msg = str_replace('%%SUBJECT%%',	stripslashes($ticket['subject']),$msg);
		$msg = str_replace('%%TRACK_URL%%',	$trackingURL					,$msg);
		$msg = str_replace('%%SITE_TITLE%%',$hesk_settings['site_title']	,$msg);
		$msg = str_replace('%%SITE_URL%%',	$hesk_settings['site_url']		,$msg);

		return $msg;
    }

    /* Generate the ticket URLs */
    $trackingURL = $hesk_settings['hesk_url'];
	$trackingURL.= $is_admin ? '/admin/admin_ticket.php' : '/ticket.php';
    $trackingURL.= '?track='.$ticket['trackid'].'&Refresh='.rand(10000,99999);

 	/* Set category title */
	$categories = hesk_getCategoriesArray();
	$ticket['category'] = $categories[$ticket['category']];

	/* Set priority title */
	switch ($ticket['priority'])
	{
		case 0:
			$ticket['priority'] = $hesklang['critical'];
			break;
		case 1:
			$ticket['priority'] = $hesklang['high'];
			break;
		case 2:
			$ticket['priority'] = $hesklang['medium'];
			break;
		default:
			$ticket['priority'] = $hesklang['low'];
	}

    /* Get owner name */
    $ticket['owner'] = hesk_getOwnerName($ticket['owner']);

	/* Replace all special tags */
	$msg = str_replace('%%NAME%%',		stripslashes($ticket['name'])	,$msg);
	$msg = str_replace('%%SUBJECT%%',	stripslashes($ticket['subject']),$msg);
	$msg = str_replace('%%TRACK_ID%%',	$ticket['trackid']				,$msg);
	$msg = str_replace('%%TRACK_URL%%',	$trackingURL					,$msg);
	$msg = str_replace('%%SITE_TITLE%%',$hesk_settings['site_title']	,$msg);
	$msg = str_replace('%%SITE_URL%%',	$hesk_settings['site_url']		,$msg);
	$msg = str_replace('%%CATEGORY%%',	$ticket['category']				,$msg);
	$msg = str_replace('%%PRIORITY%%',	$ticket['priority']				,$msg);
    $msg = str_replace('%%OWNER%%',		$ticket['owner']				,$msg);

	/* All custom fields */
	foreach ($hesk_settings['custom_fields'] as $k=>$v)
	{
		if ($v['use'])
		{
        	if ($v['type'] == 'checkbox')
            {
            	$ticket[$k] = str_replace("<br />","\n",$ticket[$k]);
            }

			$msg = str_replace('%%'.strtoupper($k).'%%',stripslashes($ticket[$k]),$msg);
		}
        else
        {
        	$msg = str_replace('%%'.strtoupper($k).'%%','',$msg);
        }
	}

	/* Message at the end */
	$msg = str_replace('%%MESSAGE%%',$ticket['message'],$msg);

    return $msg;

} // END hesk_getEmailMessage

?>
