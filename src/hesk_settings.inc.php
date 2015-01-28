<?php
/* Settings file for HESK 2.3 */

/*** GENERAL ***/

/* --> General settings */
$hesk_settings['site_url']='http://www.digix1.net';
$hesk_settings['support_mail']='support@yourdomain.com';
$hesk_settings['webmaster_mail']='jd1pinoy@gmx.ph';
$hesk_settings['noreply_mail']='noreply@yourdomain.com';

/* --> Language settings */
$hesk_settings['can_sel_lang']=0;
$hesk_settings['language']='English';
$hesk_settings['languages']=array(
'English' => array('folder'=>'en'),
);

/* --> Database settings */
$hesk_settings['db_host']='localhost';
$hesk_settings['db_name']='zadmin_';
$hesk_settings['db_user']='Insert your created Username';
$hesk_settings['db_pass']='Insert your created Password';
$hesk_settings['db_pfix']='hesk_';


/*** HELP DESK ***/

/* --> Help desk settings */
$hesk_settings['hesk_title']='ZpanelX Help Desk';
$hesk_settings['hesk_url']='http://domains.digix1.net';
$hesk_settings['server_path']='/home/mysite/public_html/helpdesk';
$hesk_settings['max_listings']=20;
$hesk_settings['print_font_size']=12;
$hesk_settings['autoclose']=7;

/* --> Features */
$hesk_settings['autologin']=1;
$hesk_settings['autoassign']=1;
$hesk_settings['custopen']=1;
$hesk_settings['rating']=1;
$hesk_settings['cust_urgency']=1;
$hesk_settings['sequential']=1;
$hesk_settings['confirm_email']=0;
$hesk_settings['list_users']=0;
$hesk_settings['email_piping']=0;
$hesk_settings['debug_mode']=0;

/* --> Security */
$hesk_settings['secimg_use']=1;
$hesk_settings['secimg_sum']='6DT29B3DY1';
$hesk_settings['question_use']=0;
$hesk_settings['question_ask']='Which of these is an animal: rocket, car, cow';
$hesk_settings['question_ans']='cow';
$hesk_settings['attempt_limit']=6;
$hesk_settings['attempt_banmin']=60;
$hesk_settings['email_view_ticket']=0;

/* --> Attachments */
$hesk_settings['attachments']=array (
    'use' =>  1,
    'max_number'  =>  2,
    'max_size'    =>  1024, // kb
    'allowed_types'   =>  array('.gif','.jpg','.png','.zip','.rar','.csv','.doc','.docx','.xls','.xlsx','.txt','.pdf')
);


/*** KNOWLEDGEBASE ***/

/* --> Knowledgebase settings */
$hesk_settings['kb_enable']=1;
$hesk_settings['kb_wysiwyg']=1;
$hesk_settings['kb_search']=2;
$hesk_settings['kb_search_limit']=10;
$hesk_settings['kb_recommendanswers']=1;
$hesk_settings['kb_rating']=1;
$hesk_settings['kb_substrart']=200;
$hesk_settings['kb_cols']=2;
$hesk_settings['kb_numshow']=3;
$hesk_settings['kb_popart']=6;
$hesk_settings['kb_latest']=6;
$hesk_settings['kb_index_popart']=3;
$hesk_settings['kb_index_latest']=3;


/*** MISC ***/

/* --> Email sending */
$hesk_settings['smtp']=0;
$hesk_settings['smtp_host_name']='localhost';
$hesk_settings['stmp_host_port']=25;
$hesk_settings['stmp_timeout']=10;
$hesk_settings['stmp_user']='';
$hesk_settings['stmp_password']='';

/* --> Date & Time */
$hesk_settings['diff_hours']=0;
$hesk_settings['diff_minutes']=0;
$hesk_settings['daylight']=1;
$hesk_settings['timeformat']='Y-m-d H:i:s';

/* --> Other */
$hesk_settings['alink']=1;
$hesk_settings['show_rate']=1;
$hesk_settings['submit_notice']=0;
$hesk_settings['online']=1;
$hesk_settings['online_min']=10;
$hesk_settings['multi_eml']=0;


/*** CUSTOM FIELDS ***/

$hesk_settings['custom_fields']=array (
'custom1'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 1','maxlen'=>255,'value'=>''),
'custom2'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 2','maxlen'=>255,'value'=>''),
'custom3'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 3','maxlen'=>255,'value'=>''),
'custom4'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 4','maxlen'=>255,'value'=>''),
'custom5'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 5','maxlen'=>255,'value'=>''),
'custom6'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 6','maxlen'=>255,'value'=>''),
'custom7'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 7','maxlen'=>255,'value'=>''),
'custom8'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 8','maxlen'=>255,'value'=>''),
'custom9'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 9','maxlen'=>255,'value'=>''),
'custom10'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 10','maxlen'=>255,'value'=>''),
'custom11'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 11','maxlen'=>255,'value'=>''),
'custom12'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 12','maxlen'=>255,'value'=>''),
'custom13'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 13','maxlen'=>255,'value'=>''),
'custom14'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 14','maxlen'=>255,'value'=>''),
'custom15'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 15','maxlen'=>255,'value'=>''),
'custom16'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 16','maxlen'=>255,'value'=>''),
'custom17'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 17','maxlen'=>255,'value'=>''),
'custom18'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 18','maxlen'=>255,'value'=>''),
'custom19'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 19','maxlen'=>255,'value'=>''),
'custom20'=>array('use'=>0,'place'=>0,'type'=>'text','req'=>0,'name'=>'Custom field 20','maxlen'=>255,'value'=>'')
);

#############################
#     DO NOT EDIT BELOW     #
#############################
$hesk_settings['hesk_version']='2.3';
if ($hesk_settings['debug_mode'])
{
    error_reporting(E_ALL ^ E_NOTICE);
}
else
{
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
if (!defined('IN_SCRIPT')) {die('Invalid attempt!');}
?>
