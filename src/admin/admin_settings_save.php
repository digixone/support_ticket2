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

hesk_session_start();
hesk_dbConnect();
hesk_isLoggedIn();

/* Check permissions for this feature */
hesk_checkPermission('can_man_settings');

/* A security check */
hesk_token_check($_POST['token']);

$set=array();

/*** GENERAL ***/

/* --> General settings */
$set['site_title']		= hesk_input($_POST['s_site_title'],$hesklang['err_sname']);
//$set['site_title']		= str_replace('\\&quot;','&quot;',$set['site_title']);
//$set['site_url']		= hesk_input($_POST['s_site_url'],$hesklang['err_surl']);
$set['support_mail']	= hesk_validateEmail($_POST['s_support_mail'],$hesklang['err_supmail']);
$set['webmaster_mail']	= hesk_validateEmail($_POST['s_webmaster_mail'],$hesklang['err_wmmail']);
$set['noreply_mail']	= hesk_validateEmail($_POST['s_noreply_mail'],$hesklang['err_nomail']);

/* --> Language settings */
$set['can_sel_lang']	= empty($_POST['s_can_sel_lang']) ? 0 : 1;
$set['languages'] 		= hesk_getLanguagesArray();
$lang					= explode('|',hesk_input($_POST['s_language']));
if (isset($lang[1]) && in_array($lang[1],hesk_getLanguagesArray(1) ))
{
	$set['language'] = $lang[1];
}
else
{
	hesk_error($hesklang['err_lang']);
}

/* --> Database settings */
$set['db_host'] = hesk_input($_POST['s_db_host'],$hesklang['err_dbhost']);
$set['db_name'] = hesk_input($_POST['s_db_name'],$hesklang['err_dbname']);
$set['db_user'] = hesk_input($_POST['s_db_user'],$hesklang['err_dbuser']);
$set['db_pass'] = hesk_input($_POST['s_db_pass']);
$set['db_pfix'] = hesk_input($_POST['s_db_pfix']);

/* ----> check DB connection */
$set_link = @mysql_connect($set['db_host'],$set['db_user'],$set['db_pass']) or hesk_error($hesklang['err_dbconn']);
if (!(@mysql_select_db($set['db_name'],$set_link))) {hesk_error($hesklang['err_dbsele']);}
/* ----> check that tables exist */
$tables = array(
	$set['db_pfix'] . 'attachments',
	$set['db_pfix'] . 'categories',
	$set['db_pfix'] . 'kb_articles',
	$set['db_pfix'] . 'kb_attachments',
	$set['db_pfix'] . 'kb_categories',
	$set['db_pfix'] . 'logins',
	$set['db_pfix'] . 'mail',
	$set['db_pfix'] . 'notes',
	$set['db_pfix'] . 'replies',
	$set['db_pfix'] . 'std_replies',
	$set['db_pfix'] . 'tickets',
	$set['db_pfix'] . 'users',
);
$sql = 'SHOW TABLES FROM `'.hesk_dbEscape($hesk_settings['db_name']).'`';
$res = hesk_dbQuery($sql);
while ($row = hesk_dbFetchRow($res))
{
	foreach($tables as $k => $v)
	{
		if ($v == $row[0])
	    {
	    	unset($tables[$k]);
            break;
	    }
	}
}
if (count($tables) > 0)
{
	hesk_error(sprintf($hesklang['err_dpi'],$set['db_name'],$set['db_pfix']).'<br /><br />'.$hesklang['err_dpi2'].' '.implode(', ',$tables));
}
mysql_close($set_link);

/*** HELP DESK ***/

/* --> Helpdesk settings */
$set['hesk_title']		= hesk_input($_POST['s_hesk_title'],$hesklang['err_htitle']);
$set['hesk_title']		= str_replace('\\&quot;','&quot;',$set['hesk_title']);
$set['hesk_url']		= hesk_input($_POST['s_hesk_url'],$hesklang['err_hurl']);
$set['server_path']		= hesk_input($_POST['s_server_path'],$hesklang['err_spath']);
$set['max_listings']	= empty($_POST['s_max_listings']) ? 10 : intval($_POST['s_max_listings']);
$set['max_listings']	= hesk_checkMinMax($set['max_listings'],1,999,10);
$set['print_font_size']	= empty($_POST['s_print_font_size']) ? 10 : intval($_POST['s_print_font_size']);
$set['print_font_size']	= hesk_checkMinMax($set['print_font_size'],1,99,12);
$set['autoclose']		= ! isset($_POST['s_autoclose']) ? 7 : intval($_POST['s_autoclose']);
$set['autoclose']		= hesk_checkMinMax($set['autoclose'],0,999,7);

/* --> Features */
$set['autologin']		= empty($_POST['s_autologin']) ? 0 : 1;
$set['autoassign']		= empty($_POST['s_autoassign']) ? 0 : 1;
$set['custopen']		= empty($_POST['s_custopen']) ? 0 : 1;
$set['rating']			= empty($_POST['s_rating']) ? 0 : 1;
$set['cust_urgency']	= empty($_POST['s_cust_urgency']) ? 0 : 1;
$set['sequential']		= empty($_POST['s_sequential']) ? 0 : 1;
$set['confirm_email']	= empty($_POST['s_confirm_email']) ? 0 : 1;
$set['list_users']		= empty($_POST['s_list_users']) ? 0 : 1;
$set['email_piping']	= empty($_POST['s_email_piping']) ? 0 : 1;
$set['debug_mode']		= empty($_POST['s_debug_mode']) ? 0 : 1;

/* --> Security */

$set['secimg_use']		= empty($_POST['s_secimg_use']) ? 0 : ($_POST['s_secimg_use'] == 2 ? 2 : 1);
$set['secimg_sum']		= '';
for ($i=1;$i<=10;$i++)
{
    $set['secimg_sum'] .= substr('AEUYBDGHJLMNPQRSTVWXZ123456789', rand(0,29), 1);
}
$set['question_use']	= empty($_POST['s_question_use']) ? 0 : 1;
$set['question_ask']	= hesk_getHTML($_POST['s_question_ask']) or hesk_error($hesklang['err_qask']);
$set['question_ans']	= hesk_input($_POST['s_question_ans'],$hesklang['err_qans']);
$set['attempt_limit']	= empty($_POST['s_attempt_limit']) ? 5 : intval($_POST['s_attempt_limit']);
$set['attempt_limit']	= hesk_checkMinMax($set['attempt_limit'],0,99,5);
$set['attempt_limit']	= $set['attempt_limit']+1;
$set['attempt_banmin']	= empty($_POST['s_attempt_banmin']) ? 60 : intval($_POST['s_attempt_banmin']);
$set['attempt_banmin']	= hesk_checkMinMax($set['attempt_banmin'],5,99999,60);
$set['email_view_ticket'] = empty($_POST['s_email_view_ticket']) ? 0 : 1;

/* --> Attachments */
$set['attachments']['use'] = empty($_POST['s_attach_use']) ? 0 : 1;
if ($set['attachments']['use'])
{
    $set['attachments']['max_number']=intval($_POST['s_max_number']) ? intval($_POST['s_max_number']) : 2;
    $set['attachments']['max_size']=intval($_POST['s_max_size']) ? intval($_POST['s_max_size']) : 512;
    $set['attachments']['allowed_types']=hesk_input($_POST['s_allowed_types']);
    if (empty($set['attachments']['allowed_types']))
    {
        $set['attachments']['allowed_types']=array('.gif','.jpg','.zip','.rar','.csv','.doc','.txt','.pdf');
    }
    else
    {
        $set['attachments']['allowed_types']=explode(',',str_replace(' ','',$set['attachments']['allowed_types']));
    }
}
else
{
    $set['attachments']['max_number']=2;
    $set['attachments']['max_size']=512;
    $set['attachments']['allowed_types']=array('.gif','.jpg','.zip','.rar','.csv','.doc','.txt','.pdf');
}

/*** KNOWLEDGEBASE ***/

/* --> Knowledgebase settings */
$set['kb_enable']			= empty($_POST['s_kb_enable']) ? 0 : 1;
$set['kb_wysiwyg']			= empty($_POST['s_kb_wysiwyg']) ? 0 : 1;
$set['kb_search']			= empty($_POST['s_kb_search']) ? 0 : ($_POST['s_kb_search'] == 2 ? 2 : 1);
$set['kb_recommendanswers']	= empty($_POST['s_kb_recommendanswers']) ? 0 : 1;
$set['kb_rating']			= empty($_POST['s_kb_rating']) ? 0 : 1;
$set['kb_search_limit']		= empty($_POST['s_kb_search_limit']) ? 10 : intval($_POST['s_kb_search_limit']);
$set['kb_search_limit']		= hesk_checkMinMax($set['kb_search_limit'],1,99,10);
$set['kb_substrart']		= empty($_POST['s_kb_substrart']) ? 200 : intval($_POST['s_kb_substrart']);
$set['kb_substrart']		= hesk_checkMinMax($set['kb_substrart'],20,9999,200);
$set['kb_cols']				= empty($_POST['s_kb_cols']) ? 2 : intval($_POST['s_kb_cols']);
$set['kb_cols']				= hesk_checkMinMax($set['kb_cols'],1,5,2);
$set['kb_numshow']			= isset($_POST['s_kb_numshow']) ? intval($_POST['s_kb_numshow']) : 0; // Popular articles on subcat listing
$set['kb_popart']			= isset($_POST['s_kb_popart']) ? intval($_POST['s_kb_popart']) : 0; // Popular articles on main category page
$set['kb_latest']			= isset($_POST['s_kb_latest']) ? intval($_POST['s_kb_latest']) : 0; // Latest articles on main category page
$set['kb_index_popart']		= isset($_POST['s_kb_index_popart']) ? intval($_POST['s_kb_index_popart']) : 0;
$set['kb_index_latest']		= isset($_POST['s_kb_index_latest']) ? intval($_POST['s_kb_index_latest']) : 0;


/*** MISC ***/

/* --> Email sending */
$set['smtp']			= empty($_POST['s_smtp']) ? 0 : 1;

/* ----> Validate SMTP settings */
if ($set['smtp'])
{
	$set['smtp_host_name']	= isset($_POST['s_smtp_host_name']) ? hesk_input($_POST['s_smtp_host_name']) : 'localhost';
	$set['stmp_host_port']	= isset($_POST['s_stmp_host_port']) ? intval($_POST['s_stmp_host_port']) : 25;
	$set['stmp_timeout']	= isset($_POST['s_stmp_timeout']) ? intval($_POST['s_stmp_timeout']) : 10;
	$set['stmp_user']		= isset($_POST['s_stmp_user']) ? hesk_input($_POST['s_stmp_user']) : '';
	$set['stmp_password']	= isset($_POST['s_stmp_password']) ? hesk_input($_POST['s_stmp_password']) : '';

	require(HESK_PATH . 'inc/mail/smtp.php');

	$smtp = new smtp_class;

	$smtp->host_name	= $set['smtp_host_name'];
	$smtp->host_port	= $set['stmp_host_port'];
	$smtp->timeout		= $set['stmp_timeout'];
	$smtp->user			= $set['stmp_user'];
	$smtp->password		= $set['stmp_password'];
	$smtp->debug = 1;

    ob_start();

	if (strlen($set['stmp_user']) || strlen($set['stmp_password']))
	{
		require(HESK_PATH . 'inc/mail/sasl/sasl.php');
	}

	if ($smtp->Connect())
	{
		// SMTP connect successful
	    $smtp->Disconnect;
	}
	else
	{
		// SMTP not working, disable it
		$set['smtp'] = 0;
        $smtp_delivery_log = ob_get_contents();
	}

	ob_end_clean();

} // END if $set['smtp']
else
{
	$set['smtp_host_name']	= isset($_POST['tmp_smtp_host_name']) ? hesk_input($_POST['tmp_smtp_host_name']) : 'localhost';
	$set['stmp_host_port']	= isset($_POST['tmp_stmp_host_port']) ? intval($_POST['tmp_stmp_host_port']) : 25;
	$set['stmp_timeout']	= isset($_POST['tmp_stmp_timeout']) ? intval($_POST['tmp_stmp_timeout']) : 10;
	$set['stmp_user']		= isset($_POST['tmp_stmp_user']) ? hesk_input($_POST['tmp_stmp_user']) : '';
	$set['stmp_password']	= isset($_POST['tmp_stmp_password']) ? hesk_input($_POST['tmp_stmp_password']) : '';
}

/* --> Date & Time */
$set['diff_hours']		= isset($_POST['s_diff_hours']) ? floatval($_POST['s_diff_hours']) : 0;
$set['diff_minutes']	= isset($_POST['s_diff_minutes']) ? floatval($_POST['s_diff_minutes']) : 0;
$set['daylight']		= empty($_POST['s_daylight']) ? 0 : 1;
$set['timeformat']		= hesk_input($_POST['s_timeformat']) or $set['timeformat'] = 'Y-m-d H:i:s';

/* --> Other */
$set['alink']			= empty($_POST['s_alink']) ? 0 : 1;
$set['show_rate']		= empty($_POST['s_show_rate']) ? 0 : 1;
$set['submit_notice']	= empty($_POST['s_submit_notice']) ? 0 : 1;
$set['online']			= empty($_POST['s_online']) ? 0 : 1;
$set['online_min']		= empty($_POST['s_online_min']) ? 10 : intval($_POST['s_online_min']);
$set['online_min']		= hesk_checkMinMax($set['online_min'],1,999,10);
$set['multi_eml']		= empty($_POST['s_multi_eml']) ? 0 : 1;

/*** CUSTOM FIELDS ***/

for ($i=1;$i<=20;$i++)
{
	$this_field='custom' . $i;
	$set['custom_fields'][$this_field]['use'] = ! empty($_POST['s_custom'.$i.'_use']) ? 1 : 0;

	if ($set['custom_fields'][$this_field]['use'])
	{
		$set['custom_fields'][$this_field]['place']		= empty($_POST['s_custom'.$i.'_place']) ? 0 : 1;
		$set['custom_fields'][$this_field]['type']		= isset($_POST['s_custom'.$i.'_type']) ? htmlspecialchars($_POST['s_custom'.$i.'_type']) : 'text';
		$set['custom_fields'][$this_field]['req']		= !empty($_POST['s_custom'.$i.'_req']) ? 1 : 0;
		$set['custom_fields'][$this_field]['name']		= hesk_input($_POST['s_custom'.$i.'_name'],$hesklang['err_custname']);
		$set['custom_fields'][$this_field]['maxlen']	= hesk_isNumber($_POST['s_custom'.$i.'_maxlen']) ? $_POST['s_custom'.$i.'_maxlen'] : 255;
        $set['custom_fields'][$this_field]['value']		= hesk_input($_POST['s_custom'.$i.'_val']);

        if (!in_array($set['custom_fields'][$this_field]['type'],array('text','textarea','select','radio','checkbox')))
        {
        	$set['custom_fields'][$this_field]['type'] = 'text';
        }
	}
	else
	{
		$set['custom_fields'][$this_field] = array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field '.$i ,'maxlen'=>255,'value'=>'');
	}
}


/*** Prepare settings file and save it ***/

$settings_file_content='<?php
/* Settings file for HESK ' . $hesk_settings['hesk_version'] . ' */

/*** GENERAL ***/

/* --> General settings */
$hesk_settings[\'site_title\']=\'' . $set['site_title'] . '\';
$hesk_settings[\'site_url\']=\'' . $set['site_url'] . '\';
$hesk_settings[\'support_mail\']=\'' . $set['support_mail'] . '\';
$hesk_settings[\'webmaster_mail\']=\'' . $set['webmaster_mail'] . '\';
$hesk_settings[\'noreply_mail\']=\'' . $set['noreply_mail'] . '\';

/* --> Language settings */
$hesk_settings[\'can_sel_lang\']=' . $set['can_sel_lang'] . ';
$hesk_settings[\'language\']=\'' . $set['language'] . '\';
$hesk_settings[\'languages\']=array(
'.$set['languages'].');

/* --> Database settings */
$hesk_settings[\'db_host\']=\'' . $set['db_host'] . '\';
$hesk_settings[\'db_name\']=\'' . $set['db_name'] . '\';
$hesk_settings[\'db_user\']=\'' . $set['db_user'] . '\';
$hesk_settings[\'db_pass\']=\'' . $set['db_pass'] . '\';
$hesk_settings[\'db_pfix\']=\'' . $set['db_pfix'] . '\';


/*** HELP DESK ***/

/* --> Help desk settings */
$hesk_settings[\'hesk_title\']=\'' . $set['hesk_title'] . '\';
$hesk_settings[\'hesk_url\']=\'' . $set['hesk_url'] . '\';
$hesk_settings[\'server_path\']=\'' . $set['server_path'] . '\';
$hesk_settings[\'max_listings\']=' . $set['max_listings'] . ';
$hesk_settings[\'print_font_size\']=' . $set['print_font_size'] . ';
$hesk_settings[\'autoclose\']=' . $set['autoclose'] . ';

/* --> Features */
$hesk_settings[\'autologin\']=' . $set['autologin'] . ';
$hesk_settings[\'autoassign\']=' . $set['autoassign'] . ';
$hesk_settings[\'custopen\']=' . $set['custopen'] . ';
$hesk_settings[\'rating\']=' . $set['rating'] . ';
$hesk_settings[\'cust_urgency\']=' . $set['cust_urgency'] . ';
$hesk_settings[\'sequential\']=' . $set['sequential'] . ';
$hesk_settings[\'confirm_email\']=' . $set['confirm_email'] . ';
$hesk_settings[\'list_users\']=' . $set['list_users'] . ';
$hesk_settings[\'email_piping\']=' . $set['email_piping'] . ';
$hesk_settings[\'debug_mode\']=' . $set['debug_mode'] . ';

/* --> Security */
$hesk_settings[\'secimg_use\']=' . $set['secimg_use'] . ';
$hesk_settings[\'secimg_sum\']=\'' . $set['secimg_sum'] . '\';
$hesk_settings[\'question_use\']=' . $set['question_use'] . ';
$hesk_settings[\'question_ask\']=\'' . $set['question_ask'] . '\';
$hesk_settings[\'question_ans\']=\'' . $set['question_ans'] . '\';
$hesk_settings[\'attempt_limit\']=' . $set['attempt_limit'] . ';
$hesk_settings[\'attempt_banmin\']=' . $set['attempt_banmin'] . ';
$hesk_settings[\'email_view_ticket\']=' . $set['email_view_ticket'] . ';

/* --> Attachments */
$hesk_settings[\'attachments\']=array (
    \'use\' =>  ' . $set['attachments']['use'] . ',
    \'max_number\'  =>  ' . $set['attachments']['max_number'] . ',
    \'max_size\'    =>  ' . $set['attachments']['max_size'] . ', // kb
    \'allowed_types\'   =>  array(\'' . implode('\',\'',$set['attachments']['allowed_types']) . '\')
);


/*** KNOWLEDGEBASE ***/

/* --> Knowledgebase settings */
$hesk_settings[\'kb_enable\']=' . $set['kb_enable'] . ';
$hesk_settings[\'kb_wysiwyg\']=' . $set['kb_wysiwyg'] . ';
$hesk_settings[\'kb_search\']=' . $set['kb_search'] . ';
$hesk_settings[\'kb_search_limit\']=' . $set['kb_search_limit'] . ';
$hesk_settings[\'kb_recommendanswers\']=' . $set['kb_recommendanswers'] . ';
$hesk_settings[\'kb_rating\']=' . $set['kb_rating'] . ';
$hesk_settings[\'kb_substrart\']=' . $set['kb_substrart'] . ';
$hesk_settings[\'kb_cols\']=' . $set['kb_cols'] . ';
$hesk_settings[\'kb_numshow\']=' . $set['kb_numshow'] . ';
$hesk_settings[\'kb_popart\']=' . $set['kb_popart'] . ';
$hesk_settings[\'kb_latest\']=' . $set['kb_latest'] . ';
$hesk_settings[\'kb_index_popart\']=' . $set['kb_index_popart'] . ';
$hesk_settings[\'kb_index_latest\']=' . $set['kb_index_latest'] . ';


/*** MISC ***/

/* --> Email sending */
$hesk_settings[\'smtp\']=' . $set['smtp'] . ';
$hesk_settings[\'smtp_host_name\']=\'' . $set['smtp_host_name'] . '\';
$hesk_settings[\'stmp_host_port\']=' . $set['stmp_host_port'] . ';
$hesk_settings[\'stmp_timeout\']=' . $set['stmp_timeout'] . ';
$hesk_settings[\'stmp_user\']=\'' . $set['stmp_user'] . '\';
$hesk_settings[\'stmp_password\']=\'' . $set['stmp_password'] . '\';

/* --> Date & Time */
$hesk_settings[\'diff_hours\']=' . $set['diff_hours'] . ';
$hesk_settings[\'diff_minutes\']=' . $set['diff_minutes'] . ';
$hesk_settings[\'daylight\']=' . $set['daylight'] . ';
$hesk_settings[\'timeformat\']=\'' . $set['timeformat'] . '\';

/* --> Other */
$hesk_settings[\'alink\']=' . $set['alink'] . ';
$hesk_settings[\'show_rate\']=' . $set['show_rate'] . ';
$hesk_settings[\'submit_notice\']=' . $set['submit_notice'] . ';
$hesk_settings[\'online\']=' . $set['online'] . ';
$hesk_settings[\'online_min\']=' . $set['online_min'] . ';
$hesk_settings[\'multi_eml\']=' . $set['multi_eml'] . ';

/*** CUSTOM FIELDS ***/

$hesk_settings[\'custom_fields\']=array (
';

for ($i=1;$i<=20;$i++) {
    $settings_file_content.='\'custom'.$i.'\'=>array(\'use\'=>'.$set['custom_fields']['custom'.$i]['use'].',\'place\'=>'.$set['custom_fields']['custom'.$i]['place'].',\'type\'=>\''.$set['custom_fields']['custom'.$i]['type'].'\',\'req\'=>'.$set['custom_fields']['custom'.$i]['req'].',\'name\'=>\''.$set['custom_fields']['custom'.$i]['name'].'\',\'maxlen\'=>'.$set['custom_fields']['custom'.$i]['maxlen'].',\'value\'=>\''.$set['custom_fields']['custom'.$i]['value'].'\')';
    if ($i!=20) {$settings_file_content.=',
';}
}

$settings_file_content.='
);

#############################
#     DO NOT EDIT BELOW     #
#############################
$hesk_settings[\'hesk_version\']=\'' . $hesk_settings['hesk_version'] . '\';
if ($hesk_settings[\'debug_mode\'])
{
    error_reporting(E_ALL ^ E_NOTICE);
}
else
{
    ini_set(\'display_errors\', 0);
    ini_set(\'log_errors\', 1);
}
if (!defined(\'IN_SCRIPT\')) {die(\'Invalid attempt!\');}
?>';

$fp=@fopen(HESK_PATH . 'hesk_settings.inc.php','w') or hesk_error($hesklang['err_openset']);
fputs($fp,$settings_file_content);
fclose($fp);

if (isset($smtp_delivery_log))
{
    hesk_process_messages($hesklang['sns'].'<br /><br /><b>'.$hesklang['sme'].':</b><br />'.ucfirst($smtp->error).'<br /><br /><a href="Javascript:void(0)" onclick="Javascript:hesk_toggleLayerDisplay(\'smtplog\')">'.$hesklang['scl'].'</a><div id="smtplog" style="display:none">&nbsp;<br /><textarea name="log" rows="10" cols="60">'.$smtp_delivery_log.'</textarea></div>','admin_settings.php','NOTICE');
}
else
{
	hesk_process_messages($hesklang['set_were_saved'],'admin_settings.php','SUCCESS');
}
exit();


/** FUNCTIONS **/

function hesk_checkMinMax($myint,$min,$max,$defval)
{
	if ($myint > $max || $myint < $min)
	{
		return $defval;
	}
	return $myint;
} // END hesk_checkMinMax()


function hesk_getLanguagesArray($returnArray=0) {
	global $hesk_settings, $hesklang;

	$dir = HESK_PATH . 'language/';
	$path = opendir($dir);
	$valid_emails = array('category_moved','forgot_ticket_id','new_reply_by_customer','new_reply_by_staff','new_ticket','new_ticket_staff');
    $code = '';
    $langArray = array();

    /* Test all folders inside the language folder */
	while (false !== ($subdir = readdir($path)))
	{
		if ($subdir == "." || $subdir == "..")
	    {
	    	continue;
	    }

		if (filetype($dir . $subdir) == 'dir')
		{
        	$add   = 1;
	    	$langu = $dir . $subdir . '/text.php';
	        $email = $dir . $subdir . '/emails';

			/* Check the text.php */
	        if (file_exists($langu))
	        {
	        	$tmp = file_get_contents($langu);
	            $err = '';
	        	if (!preg_match('/\$hesklang\[\'LANGUAGE\'\]\=\'(.*)\'\;/',$tmp,$l))
	            {
	                $add = 0;
	            }
	            elseif (!preg_match('/\$hesklang\[\'ENCODING\'\]\=\'(.*)\'\;/',$tmp))
	            {
	            	$add = 0;
	            }
	        }
	        else
	        {
                $add   = 0;
	        }

            /* Check emails folder */
	        if (file_exists($email) && filetype($email) == 'dir')
	        {
	            foreach ($valid_emails as $eml)
	            {
	            	if (!file_exists($email.'/'.$eml.'.txt'))
	                {
	                	$add = 0;
	                }
	            }
	        }
	        else
	        {
	        	$add = 0;
	        }

            /* Add an option for the <select> if needed */
            if ($add)
            {
				$code .= "'".$l[1]."' => array('folder'=>'".$subdir."'),\n";
                $langArray[] = $l[1];
            }
		}
	}

	closedir($path);

    if ($returnArray)
    {
		return $langArray;
    }
    else
    {
    	return $code;
    }
} // END hesk_getLanguagesArray()
?>
