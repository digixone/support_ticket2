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
if ( ! hesk_checkPermission('can_man_kb',0))
{
	/* This person can't manage the knowledgebase, but can read it */
	header('Location: knowledgebase_private.php');
    exit();
}

/* Is Knowledgebase enabled? */
if ( ! $hesk_settings['kb_enable'])
{
	hesk_error($hesklang['kbdis']);
}

/* This will tell the header to include WYSIWYG editor Javascript */
define('WYSIWYG',1);

/* What should we do? */
$action = isset($_REQUEST['a']) ? hesk_input($_REQUEST['a']) : '';
if     ($action == 'new_article')    {new_article();}
elseif ($action == 'new_category') 	 {new_category();}
elseif ($action == 'add_article')    {add_article();}
elseif ($action == 'add_category')   {add_category();}
elseif ($action == 'manage_cat') 	 {manage_category();}
elseif ($action == 'remove_article') {remove_article();}
elseif ($action == 'edit_article') 	 {edit_article();}
elseif ($action == 'save_article') 	 {save_article();}
elseif ($action == 'order_article')	 {order_article();}
elseif ($action == 'edit_category')	 {edit_category();}
elseif ($action == 'remove_kb_att')	 {remove_kb_att();}
elseif ($action == 'import_article') {import_article();}

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');

/* Print main manage users page */
require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
?>

</td>
</tr>
<tr>
<td>

<?php
/* This will handle error, success and notice messages */
#hesk_handle_messages();

/* Get Knowledgebase structure */
$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` ORDER BY `parent` ASC, `cat_order` ASC';
$result = hesk_dbQuery($sql);
$kb_cat = array();

while ($cat = hesk_dbFetchAssoc($result))
{
	$kb_cat[] = $cat;
}

/* Translate main category "Knowledgebase" if needed */
$kb_cat[0]['name'] = $hesklang['kb_text'];

require(HESK_PATH . 'inc/TreeMenu.php');
$icon         = 'folder.gif';
$expandedIcon = 'folder-expanded.gif';
$menu		  = new HTML_TreeMenu();

$thislevel = array('0');
$nextlevel = array();
$i = 1;
$j = 1;

if (isset($_SESSION['KB_CATEGORY']))
{
	$selected_catid = intval($_SESSION['KB_CATEGORY']);
}
else
{
	$selected_catid = 0;
}

while (count($kb_cat) > 0)
{

    foreach ($kb_cat as $k=>$cat)
    {

    	if (in_array($cat['parent'],$thislevel))
        {

			$up = $cat['parent'];
			$my = $cat['id'];
			$type = $cat['type'] ? '*' : '';
			$selected = ($selected_catid == $my) ? 1 : 0;
            $cls = (isset($_SESSION['newcat']) && $_SESSION['newcat'] == $my) ? ' class="kbCatListON"' : '';

            $text = str_replace('\\','\\\\','<span id="c_'.$my.'"'.$cls.'><a href="manage_knowledgebase.php?a=manage_cat&catid='.$my.'">'.$cat['name'].'</a>').$type.'</span> ('.$cat['articles'].') &nbsp; '                  /* ' */
            		.'<a href="manage_knowledgebase.php?a=add_article&catid='.$my.'" onclick="document.getElementById(\'option'.$j.'\').selected=true;return true;"><img src="../img/add_article.png" width="16" height="16" alt="'.$hesklang['kb_i_art'].'" title="'.$hesklang['kb_i_art'].'" class="optionWhiteNbOFF" onmouseover="this.className=\'optionWhiteNbON\';document.getElementById(\'c_'.$my.'\').className=\'kbCatListON\'" onmouseout="this.className=\'optionWhiteNbOFF\';document.getElementById(\'c_'.$my.'\').className=\'kbCatListOFF\'" /></a>  '
                    .'<a href="manage_knowledgebase.php?a=add_category&parent='.$my.'" onclick="document.getElementById(\'option'.$j.'_2\').selected=true;return true;"><img src="../img/add_category.png" width="16" height="16" alt="'.$hesklang['kb_i_cat'].'" title="'.$hesklang['kb_i_cat'].'" class="optionWhiteNbOFF" onmouseover="this.className=\'optionWhiteNbON\';document.getElementById(\'c_'.$my.'\').className=\'kbCatListON\'" onmouseout="this.className=\'optionWhiteNbOFF\';document.getElementById(\'c_'.$my.'\').className=\'kbCatListOFF\'" /></a>  '
                    .'<a href="manage_knowledgebase.php?a=manage_cat&catid='.$my.'"><img src="../img/manage.png" width="16" height="16" alt="'.$hesklang['kb_p_man'].'" title="'.$hesklang['kb_p_man'].'" class="optionWhiteNbOFF" onmouseover="this.className=\'optionWhiteNbON\';document.getElementById(\'c_'.$my.'\').className=\'kbCatListON\'" onmouseout="this.className=\'optionWhiteNbOFF\';document.getElementById(\'c_'.$my.'\').className=\'kbCatListOFF\'" /></a> ';

            $text_short = $cat['name'].$type.' ('.$cat['articles'].')';

            if (isset($node[$up]))
            {
	            $node[$my] = &$node[$up]->addItem(new HTML_TreeNode(array('hesk_selected' => $selected, 'text' => $text, 'text_short' => $text_short, 'hesk_catid' => $cat['id'], 'hesk_select' => 'option'.$j, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true)));
            }
            else
            {
                $node[$my] = new HTML_TreeNode(array('hesk_selected' => $selected, 'text' => $text, 'text_short' => $text_short, 'hesk_catid' => $cat['id'], 'hesk_select' => 'option'.$j, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true));
            }

	        $nextlevel[] = $cat['id'];
            $j++;
	        unset($kb_cat[$k]);

        }

    }

    $thislevel = $nextlevel;
    $nextlevel = array();

    /* Break after 20 recursions to avoid hang-ups in case of any problems */
    if ($i > 20)
    {
    	break;
    }
    $i++;
}

$menu->addItem($node[1]);

// Create the presentation class
$treeMenu = & ref_new(new HTML_TreeMenu_DHTML($menu, array('images' => '../img', 'defaultClass' => 'treeMenuDefault', 'isDynamic' => true)));
$listBox  = & ref_new(new HTML_TreeMenu_Listbox($menu));

/* Hide new article and new category forms by default */
if (!isset($_SESSION['hide']))
{
	$_SESSION['hide'] = array(
		//'treemenu' => 1,
		'new_article' => 1,
		'new_category' => 1,
	);
}

/* Hide tree menu? */
if (!isset($_SESSION['hide']['treemenu']))
{
	?>
	<h3><?php echo $hesklang['kb']; ?> [<a href="javascript:void(0)" onclick="javascript:alert('<?php echo hesk_makeJsString($hesklang['kb_intro']); ?>')">?</a>]</h3>

	<!-- SUB NAVIGATION -->
	<?php show_subnav(); ?>
	<!-- SUB NAVIGATION -->

	<!-- SHOW THE CATEGORY TREE -->
	<?php show_treeMenu(); ?>
	<!-- SHOW THE CATEGORY TREE -->

	<p>&nbsp;</p>

	<p><img src="../img/link.png" width="16" height="16" border="0" alt="" style="vertical-align:text-bottom" />
	<a href="http://support.mozilla.com/en-US/kb/how-to-write-knowledge-base-articles" rel="nofollow" target="_blank"><?php echo $hesklang['goodkb']; ?></a></p>

	&nbsp;<br />

	<?php
} // END hide treemenu

/* Hide article form? */
if (!isset($_SESSION['hide']['new_article']))
{
	if (isset($_SESSION['new_article']))
    {
		$_SESSION['new_article'] = hesk_stripArray($_SESSION['new_article']);
    }
	?>

	<span class="smaller"><a href="manage_knowledgebase.php" class="smaller"><?php echo $hesklang['kb']; ?></a> &gt; <?php echo $hesklang['new_kb_art']; ?></span>

	<!-- SUB NAVIGATION -->
	<?php show_subnav('newa'); ?>
	<!-- SUB NAVIGATION -->

	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
			<td class="roundcornerstop"></td>
			<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
		</tr>
		<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>

		    <div align="center">
		    <table border="0">
		    <tr>
		    <td>

	        <?php
	        if ($hesk_settings['kb_wysiwyg'])
	        {
		        ?>
				<script type="text/javascript">
				tinyMCE.init({
					mode : "exact",
					elements : "content",
					theme : "advanced",
                    convert_urls : false,

					theme_advanced_buttons1 : "cut,copy,paste,|,undo,redo,|,formatselect,fontselect,fontsizeselect,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull",
					theme_advanced_buttons2 : "sub,sup,|,charmap,|,bullist,numlist,|,outdent,indent,insertdate,inserttime,preview,|,forecolor,backcolor,|,hr,removeformat,visualaid,|,link,unlink,anchor,image,cleanup,code",
					theme_advanced_buttons3 : "",

					theme_advanced_toolbar_location : "top",
					theme_advanced_toolbar_align : "left",
					theme_advanced_statusbar_location : "bottom",
					theme_advanced_resizing : true,
				});
				</script>
		        <?php
	        }
	        ?>

		    <form action="manage_knowledgebase.php" method="post" name="form1" enctype="multipart/form-data">

			<h3 align="center"><a name="new_article"></a><?php echo $hesklang['new_kb_art']; ?></h3>
		    <br />

			<table border="0">
			<tr>
			<td><b><?php echo $hesklang['kb_cat']; ?>:</b></td>
			<td><select name="catid"><?php $listBox->printMenu(); ?></select></td>
			</tr>
			<tr>
			<td valign="top"><b><?php echo $hesklang['kb_type']; ?>:</b></td>
			<td>
			<label><input type="radio" name="type" value="0" <?php if (!isset($_SESSION['new_article']['type']) || (isset($_SESSION['new_article']['type']) && $_SESSION['new_article']['type'] == 0) ) {echo 'checked="checked"';} ?> /> <b><i><?php echo $hesklang['kb_published']; ?></i></b></label><br />
			<?php echo $hesklang['kb_published2']; ?><br />&nbsp;<br />
			<label><input type="radio" name="type" value="1" <?php if (isset($_SESSION['new_article']['type']) && $_SESSION['new_article']['type'] == 1) {echo 'checked="checked"';} ?> /> <b><i><?php echo $hesklang['kb_private']; ?></i></b></label><br />
			<?php echo $hesklang['kb_private2']; ?><br />&nbsp;<br />
			<label><input type="radio" name="type" value="2" <?php if (isset($_SESSION['new_article']['type']) && $_SESSION['new_article']['type'] == 2) {echo 'checked="checked"';} ?> /> <b><i><?php echo $hesklang['kb_draft']; ?></i></b></label><br />
			<?php echo $hesklang['kb_draft2']; ?><br />&nbsp;
			</td>
			</tr>
			<tr>
			<td><b><?php echo $hesklang['kb_subject']; ?>:</b></td>
			<td><input type="text" name="subject" size="70" maxlength="255" <?php if (isset($_SESSION['new_article']['subject'])) {echo 'value="'.$_SESSION['new_article']['subject'].'"';} ?> /></td>
			</tr>
			</table>

	        <?php
	        $displayType = $hesk_settings['kb_wysiwyg'] ? 'none' : 'block';
	        $displayWarn = 'none';
	        ?>

			<p><b><?php echo $hesklang['kb_content']; ?>:</b></p>

	        <span id="contentType" style="display:<?php echo $displayType; ?>">
			<label><input type="radio" name="html" value="0" <?php if (!isset($_SESSION['new_article']['html']) || (isset($_SESSION['new_article']['html']) && $_SESSION['new_article']['html'] == 0) ) {echo 'checked="checked"';} ?> onclick="javascript:document.getElementById('kblinks').style.display = 'none'" /> <?php echo $hesklang['kb_dhtml']; ?></label><br />
			<label><input type="radio" name="html" value="1" <?php $display = 'none'; if (isset($_SESSION['new_article']['html']) && $_SESSION['new_article']['html'] == 1) {echo 'checked="checked"'; $displayWarn = 'block';} ?> onclick="javascript:document.getElementById('kblinks').style.display = 'block'" /> <?php echo $hesklang['kb_ehtml']; ?></label><br />
	        <span id="kblinks" style="display:<?php echo $displayWarn; ?>"><i><?php echo $hesklang['kb_links']; ?></i></span>
	        </span>

			<p><textarea name="content" rows="25" cols="70" id="content"><?php if (isset($_SESSION['new_article']['content'])) {echo $_SESSION['new_article']['content'];} ?></textarea></p>

			<p><b><?php echo $hesklang['attachments']; ?></b><br />
	        <input type="file" name="attachment[1]" size="50" /><br />
	        <input type="file" name="attachment[2]" size="50" /><br />
	        <input type="file" name="attachment[3]" size="50" /><br />
			<?php echo$hesklang['accepted_types']; ?>: <?php echo '*'.implode(', *', $hesk_settings['attachments']['allowed_types']); ?><br />
			<?php echo $hesklang['max_file_size']; ?>: <?php echo $hesk_settings['attachments']['max_size']; ?> Kb
			(<?php echo sprintf("%01.2f",($hesk_settings['attachments']['max_size']/1024)); ?> Mb)
			</p>

			<p align="center"><input type="hidden" name="a" value="new_article" />
	        <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
	        <input type="submit" value="<?php echo $hesklang['kb_save']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
	        | <a href="javascript:history.go(-1)"><?php echo $hesklang['cancel']; ?></a></p>
			</form>

			</td>
			</tr>
			</table>
		    </div>
		</td>
		<td class="roundcornersright">&nbsp;</td>
		</tr>
		<tr>
		<td><img src="../img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornersbottom"></td>
		<td width="7" height="7"><img src="../img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
		</tr>
	</table>

	<?php
} // END hide article

/* Hide new category form? */
if (!isset($_SESSION['hide']['new_category']))
{

	if (isset($_SESSION['new_category']))
    {
		$_SESSION['new_category'] = hesk_stripArray($_SESSION['new_category']);
    }
	?>

	<span class="smaller"><a href="manage_knowledgebase.php" class="smaller"><?php echo $hesklang['kb']; ?></a> &gt; <?php echo $hesklang['kb_cat_new']; ?></span>

	<!-- SUB NAVIGATION -->
	<?php show_subnav('newc'); ?>
	<!-- SUB NAVIGATION -->

	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
			<td class="roundcornerstop"></td>
			<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
		</tr>
		<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>

		    <div align="center">
		    <table border="0">
		    <tr>
		    <td>

			<form action="manage_knowledgebase.php" method="post" name="form2">

			<h3 align="center"><a name="new_category"></a><?php echo $hesklang['kb_cat_new']; ?></h3>
		    <br />

			<table border="0">
			<tr>
			<td><b><?php echo $hesklang['kb_cat_title']; ?>:</b></td>
			<td><input type="text" name="title" size="70" maxlength="255" /></td>
			</tr>
			<tr>
			<td><b><?php echo $hesklang['kb_cat_parent']; ?>:</b></td>
			<td><select name="parent"><?php $listBox->printMenu()?></select></td>
			</tr>
			<tr>
			<td valign="top"><b><?php echo $hesklang['kb_type']; ?>:</b></td>
			<td>
			<label><input type="radio" name="type" value="0" <?php if (!isset($_SESSION['new_category']['type']) || (isset($_SESSION['new_category']['type']) && $_SESSION['new_category']['type'] == 0) ) {echo 'checked="checked"';} ?> /> <b><i><?php echo $hesklang['kb_published']; ?></i></b></label><br />
			<?php echo $hesklang['kb_cat_published']; ?><br />&nbsp;<br />
			<label><input type="radio" name="type" value="1" <?php if (isset($_SESSION['new_category']['type']) && $_SESSION['new_category']['type'] == 1) {echo 'checked="checked"';} ?> /> <b><i><?php echo $hesklang['kb_private']; ?></i></b></label><br />
			<?php echo $hesklang['kb_cat_private']; ?>
			</td>
			</tr>
			</table>

			<p align="center"><input type="hidden" name="a" value="new_category" />
	        <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
	        <input type="submit" value="<?php echo $hesklang['kb_cat_add']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
	        | <a href="manage_knowledgebase.php"><?php echo $hesklang['cancel']; ?></a></p>
			</form>

			</td>
			</tr>
			</table>
		    </div>

		</td>
		<td class="roundcornersright">&nbsp;</td>
		</tr>
		<tr>
		<td><img src="../img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornersbottom"></td>
		<td width="7" height="7"><img src="../img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
		</tr>
	</table>

	<?php

    /* Show the treemenu? */
    if (isset($_SESSION['hide']['cat_treemenu']))
    {
        echo ' &nbsp; ';
        show_treeMenu();
    }

} // END hide new category form

/* Clean unneeded session variables */
hesk_cleanSessionVars(array('hide','new_article','new_category','KB_CATEGORY','manage_cat','edit_article','newcat'));
?>

<p>&nbsp;</p>

<?php
require_once(HESK_PATH . 'inc/footer.inc.php');
exit();


/*** START FUNCTIONS ***/


function import_article() {
	global $hesk_settings, $hesklang, $listBox;

	$_SESSION['hide'] = array(
		'treemenu' => 1,
		//'new_article' => 1,
		'new_category' => 1,
	);

    $_SESSION['KB_CATEGORY'] = 1;

    /* Get ticket ID */
	$trackingID = isset($_REQUEST['track']) ? hesk_cleanID($_REQUEST['track']) : '';
	if (empty($trackingID))
	{
		return false;
	}

	/* Get ticket info */
	$sql = "SELECT `id`,`category`,`subject`,`message`,`owner` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` WHERE `trackid`='".hesk_dbEscape($trackingID)."' LIMIT 1";
	$res = hesk_dbQuery($sql);
	if (hesk_dbNumRows($res) != 1)
	{
		return false;
	}
	$ticket = hesk_dbFetchAssoc($res);

	/* Permission to view this ticket? */
	if ($ticket['owner'] && $ticket['owner'] != $_SESSION['id'] && ! hesk_checkPermission('can_view_ass_others',0))
	{
		return false;
	}

	if ( ! $ticket['owner'] && ! hesk_checkPermission('can_view_unassigned',0))
	{
		return false;
	}

	/* Is this user allowed to view tickets inside this category? */
	if ( ! hesk_okCategory($ticket['category'],0))
    {
    	return false;
    }

    /* Set article contents */
    if ($hesk_settings['kb_wysiwyg'])
    {
    	/* With WYSIWYG editor */
		$_SESSION['new_article'] = array(
		'html' => 1,
		'subject' => $ticket['subject'],
		'content' => htmlspecialchars($ticket['message']),
		);
    }
    else
    {
    	/* Without WYSIWYG editor */
		$_SESSION['new_article'] = array(
		'html' => 0,
		'subject' => $ticket['subject'],
		'content' => hesk_msgToPlain($ticket['message']),
		);
    }

	/* Get messages from replies to the ticket */
	$sql = "SELECT `message` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` WHERE `replyto`='".hesk_dbEscape($ticket['id'])."' ORDER BY `id` ASC";
	$res = hesk_dbQuery($sql);

    while ($reply=hesk_dbFetchAssoc($res))
    {
    	if ($hesk_settings['kb_wysiwyg'])
        {
			$_SESSION['new_article']['content'] .= "<br /><br />" . htmlspecialchars($reply['message']);
        }
        else
        {
	        $_SESSION['new_article']['content'] .= "\n\n" . hesk_msgToPlain($reply['message']);
        }
    }

    hesk_process_messages($hesklang['import'],'NOREDIRECT','NOTICE');

} // END add_article()


function add_article() {
	global $hesk_settings, $hesklang;

	$_SESSION['hide'] = array(
		'treemenu' => 1,
		//'new_article' => 1,
		'new_category' => 1,
	);

    $catid = isset($_GET['catid']) ? hesk_isNumber($_GET['catid']) : 1;
    $_SESSION['KB_CATEGORY'] = $catid;
} // END add_article()


function add_category() {
	global $hesk_settings, $hesklang;

	$_SESSION['hide'] = array(
		'treemenu' => 1,
		'new_article' => 1,
		//'new_category' => 1,
        'cat_treemenu' => 1,
	);

    $parent = isset($_GET['parent']) ? hesk_isNumber($_GET['parent']) : 1;
    $_SESSION['KB_CATEGORY'] = $parent;
} // END add_category()


function remove_kb_att() {
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check($_GET['token']);

	$att_id  = hesk_isNumber($_GET['kb_att'],$hesklang['inv_att_id']);
    $id		 = hesk_isNumber($_GET['id']) or $id = 1;

	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_attachments` WHERE `att_id`='.hesk_dbEscape($att_id);
	$res = hesk_dbQuery($sql);
    $att = hesk_dbFetchAssoc($res);
    unlink('../attachments/'.$att['saved_name']);
	$sql = 'DELETE FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_attachments` WHERE `att_id`='.hesk_dbEscape($att_id);
	hesk_dbQuery($sql);

	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` WHERE `id`='.hesk_dbEscape($id);
	$res = hesk_dbQuery($sql);
    $art = hesk_dbFetchAssoc($res);
    $art['attachments'] = str_replace($att_id.'#'.$att['real_name'].',','',$art['attachments']);
	$sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` SET `attachments`=\''.hesk_dbEscape($art['attachments']).'\' WHERE `id`='.hesk_dbEscape($id).' LIMIT 1';
	hesk_dbQuery($sql);

    hesk_process_messages($hesklang['kb_att_rem'],'manage_knowledgebase.php?a=edit_article&id='.$id,'SUCCESS');
} // END remove_kb_att()


function edit_category() {
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check($_POST['token']);

	$_SESSION['hide'] = array(
		'article_list' => 1,
	);

    $hesk_error_buffer = array();

	$catid  = hesk_isNumber($_POST['catid'],$hesklang['kb_cat_inv']);
    $title  = hesk_input($_POST['title']) or $hesk_error_buffer[] = $hesklang['kb_cat_e_title'];
    $parent = hesk_isNumber($_POST['parent']) or $parent = 1;
    $type   = ($_POST['type']) ? 1 : 0;

    /* Category can't be it's own parent */
    if ($parent == $catid)
    {
		$hesk_error_buffer[] = $hesklang['kb_spar'];
    }

    /* Any errors? */
    if (count($hesk_error_buffer))
    {
		$_SESSION['manage_cat'] = array(
		'type' => $type,
		'parent' => $parent,
		'title' => $title,
		);

		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

    	$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
    	hesk_process_messages($hesk_error_buffer,'./manage_knowledgebase.php?a=manage_cat&catid='.$catid);
    }

    /* Delete category or just update it? */
    if (isset($_POST['dodelete']) && $_POST['dodelete']=='Y')
    {
    	/* Delete articles or move to parent category? */
    	if ($_POST['movearticles'] == 'N')
        {
        	$sql = 'DELETE FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` WHERE `catid`='.hesk_dbEscape($catid);
            hesk_dbQuery($sql);
        }
        else
        {
        	$sql = 'SELECT `id` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` WHERE `catid`='.hesk_dbEscape($catid).' AND `type`=\'0\'';
            $res = hesk_dbQuery($sql);
            $num = hesk_dbNumRows($res);

        	$sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` SET `catid`='.hesk_dbEscape($parent).' WHERE `catid`='.hesk_dbEscape($catid);
            hesk_dbQuery($sql);

        	$sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` SET `articles`=`articles`+'.hesk_dbEscape($num).' WHERE `id`='.hesk_dbEscape($parent).' LIMIT 1';
            hesk_dbQuery($sql);
        }

        /* Delete the category */
        $sql = 'DELETE FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` WHERE `id`='.hesk_dbEscape($catid).' LIMIT 1';
        hesk_dbQuery($sql);

		$_SESSION['hide'] = array(
			//'treemenu' => 1,
			'new_article' => 1,
			'new_category' => 1,
		);

        hesk_process_messages($hesklang['kb_cat_dlt'],'./manage_knowledgebase.php','SUCCESS');
    }

	$sql = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `name`='".hesk_dbEscape($title)."',`parent`=".hesk_dbEscape($parent).",`type`='".hesk_dbEscape($type)."' WHERE `id`=".hesk_dbEscape($catid)." LIMIT 1";
	$res = hesk_dbQuery($sql);

    unset($_SESSION['hide']);

    hesk_process_messages($hesklang['your_cat_mod'],'./manage_knowledgebase.php?a=manage_cat&catid='.$catid,'SUCCESS');
} // END edit_category()


function save_article() {
	global $hesk_settings, $hesklang, $hesk_error_buffer;

	/* A security check */
	hesk_token_check($_POST['token']);

    $hesk_error_buffer = array();

    $id    = hesk_isNumber($_POST['id'],$hesklang['kb_art_id']);
	$catid = hesk_isNumber($_POST['catid']) or $catid = 1;
    $type  = intval($_POST['type']);
    $type  = ($type < 0 || $type > 2) ? 0 : $type;
    $html  = $hesk_settings['kb_wysiwyg'] ? 1 : ($_POST['html'] ? 1 : 0);
    $now   = hesk_date();
    $old_catid = hesk_isNumber($_POST['old_catid']);
    $old_type  = intval($_POST['old_type']);
    $old_type  = ($old_type < 0 || $old_type > 2) ? 0 : $old_type;

    $subject = hesk_input($_POST['subject']) or $hesk_error_buffer[] = $hesklang['kb_e_subj'];

    if ($html)
    {
	    if (empty($_POST['content']))
	    {
	    	$hesk_error_buffer[] = $hesklang['kb_e_cont'];
	    }
        
	    $content = hesk_getHTML($_POST['content']);
    }
	else
    {
    	$content = hesk_input($_POST['content']) or $hesk_error_buffer[] = $hesklang['kb_e_cont'];
	    $content = nl2br($content);
	    $content = hesk_makeURL($content);
    }

    $extra_sql = '';
    if (isset($_POST['resetviews']) && $_POST['resetviews']=='Y')
    {
    	$extra_sql .= ',`views`=0 ';
    }
    if (isset($_POST['resetvotes']) && $_POST['resetvotes']=='Y')
    {
    	$extra_sql .= ',`votes`=0, `rating`=0 ';
    }

    /* Article attachments */
	define('KB',1);
    require_once(HESK_PATH . 'inc/attachments.inc.php');
    $attachments = array();
    for ($i=1;$i<=3;$i++)
    {
        $att = hesk_uploadFile($i);
        if ( ! empty($att))
        {
            $attachments[$i] = $att;
        }
    }
	$myattachments='';

    /* Any errors? */
    if (count($hesk_error_buffer))
    {
		$_SESSION['edit_article'] = array(
		'type' => $type,
		'html' => $html,
		'subject' => $subject,
		'content' => hesk_input($_POST['content']),
        'catid' => $catid,
        'resetviews' => (isset($_POST['resetviews']) ? 'Y' : 0),
        'resetvotes' => (isset($_POST['resetvotes']) ? 'Y' : 0),
		);

		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

    	$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
    	hesk_process_messages($hesk_error_buffer,'./manage_knowledgebase.php?a=edit_article&id='.$id);
    }

	/* Add to database */
	if (!empty($attachments))
	{
	    foreach ($attachments as $myatt)
	    {
	        $sql = "INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` (`saved_name`,`real_name`,`size`) VALUES ('".hesk_dbEscape($myatt['saved_name'])."', '".hesk_dbEscape($myatt['real_name'])."', '".hesk_dbEscape($myatt['size'])."')";
	        $result = hesk_dbQuery($sql);
	        $myattachments .= hesk_dbInsertID() . '#' . $myatt['real_name'] .',';
	    }

        $extra_sql .= ", `attachments` = CONCAT(`attachments`, '".$myattachments."') ";
	}

    /* Update article in the database */
    $revision = sprintf($hesklang['revision2'],$now,$_SESSION['name'].' ('.$_SESSION['user'].')');
	$sql = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET
    `catid`=".hesk_dbEscape($catid).",
    `subject`='".hesk_dbEscape($subject)."',
    `content`='".hesk_dbEscape($content)."' $extra_sql ,
    `type`='".hesk_dbEscape($type)."',
    `html`='".hesk_dbEscape($html)."',
    `history`=CONCAT(`history`,'".hesk_dbEscape($revision)."')
    WHERE `id`=".hesk_dbEscape($id)." LIMIT 1";
    $result = hesk_dbQuery($sql);

    $_SESSION['artord'] = $id;

	/*** Update proper category article count ***/
    /* --> Article is of same type as before, update just if category changed */
    if ($type == $old_type)
    {
    	if ($type == 0 && ($catid != $old_catid))
        {
		    $sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` SET `articles`=`articles`+1 WHERE `id`='.hesk_dbEscape($catid);
			$result = hesk_dbQuery($sql);

		    $sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` SET `articles`=`articles`-1 WHERE `id`='.hesk_dbEscape($old_catid);
			$result = hesk_dbQuery($sql);
        }
    }
	/* --> Article type changed */
    else
    {
	    /* --> Article isn't published anymore, update old category */
    	if ($old_type == 0)
        {
		    $sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` SET `articles`=`articles`-1 WHERE `id`='.hesk_dbEscape($old_catid);
            $result = hesk_dbQuery($sql);
        }
        /* --> Article is now published, update category count */
        if ($type == 0)
        {
		    $sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` SET `articles`=`articles`+1 WHERE `id`='.hesk_dbEscape($catid);
            $result = hesk_dbQuery($sql);
        }
    }

    hesk_process_messages($hesklang['your_kb_mod'],'./manage_knowledgebase.php?a=manage_cat&catid='.$catid,'SUCCESS');
} // END save_article()


function edit_article() {
	global $hesk_settings, $hesklang, $listBox;

    $hesk_error_buffer = array();

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if (!$id)
    {
        hesk_process_messages($hesklang['kb_art_id'],'./manage_knowledgebase.php');
    }

    /* Get article details */
	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` WHERE `id`='.hesk_dbEscape($id).' LIMIT 1';
	$result = hesk_dbQuery($sql);
    if (hesk_dbNumRows($result) != 1)
    {
        hesk_process_messages($hesklang['kb_art_id'],'./manage_knowledgebase.php');
    }
    $article = hesk_dbFetchAssoc($result);

    if ($hesk_settings['kb_wysiwyg'] || $article['html'])
    {
		$article['content'] = htmlspecialchars($article['content']);
    }
    else
    {
    	$article['content'] = hesk_msgToPlain($article['content']);
    }

    $catid = $article['catid'];

    if (isset($_SESSION['edit_article']))
    {
    	$_SESSION['edit_article'] = hesk_stripArray($_SESSION['edit_article']);
		$article['type'] = $_SESSION['edit_article']['type'];
        $article['html'] = $_SESSION['edit_article']['html'];
        $article['subject'] = $_SESSION['edit_article']['subject'];
        $article['content'] = $_SESSION['edit_article']['content'];
        $article['catid'] = $_SESSION['edit_article']['catid'];
    }

    /* Get categories */
	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` ORDER BY `parent` ASC, `cat_order` ASC';
	$result = hesk_dbQuery($sql);
	$kb_cat = array();

	while ($cat = hesk_dbFetchAssoc($result))
	{
		$kb_cat[] = $cat;
        if ($cat['id'] == $article['catid'])
        {
        	$this_cat = $cat;
            $this_cat['parent'] = $article['catid'];
        }
	}

	/* Translate main category "Knowledgebase" if needed */
	$kb_cat[0]['name'] = $hesklang['kb_text'];

	require(HESK_PATH . 'inc/TreeMenu.php');
	$icon         = HESK_PATH . 'img/folder.gif';
	$expandedIcon = HESK_PATH . 'img/folder-expanded.gif';
    $menu		  = new HTML_TreeMenu();

	$thislevel = array('0');
	$nextlevel = array();
	$i = 1;
	$j = 1;

	while (count($kb_cat) > 0)
	{

	    foreach ($kb_cat as $k=>$cat)
	    {

	    	if (in_array($cat['parent'],$thislevel))
	        {

	        	$up = $cat['parent'];
	            $my = $cat['id'];
	            $type = $cat['type'] ? '*' : '';

	            $text_short = $cat['name'].$type.' ('.$cat['articles'].')';

	            if (isset($node[$up]))
	            {
		            $node[$my] = &$node[$up]->addItem(new HTML_TreeNode(array('hesk_parent' => $this_cat['parent'], 'text' => 'Text', 'text_short' => $text_short, 'hesk_catid' => $cat['id'], 'hesk_select' => 'option'.$j, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true)));
	            }
	            else
	            {
	                $node[$my] = new HTML_TreeNode(array('hesk_parent' => $this_cat['parent'], 'text' => 'Text',  'text_short' => $text_short, 'hesk_catid' => $cat['id'], 'hesk_select' => 'option'.$j, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true));
	            }

		        $nextlevel[] = $cat['id'];
	            $j++;
		        unset($kb_cat[$k]);

	        }

	    }

	    $thislevel = $nextlevel;
	    $nextlevel = array();

	    /* Break after 20 recursions to avoid hang-ups in case of any problems */

	    if ($i > 20)
	    {
	    	break;
	    }
	    $i++;
	}

	$menu->addItem($node[1]);

	// Create the presentation class
	$listBox  = & ref_new(new HTML_TreeMenu_Listbox($menu));

	/* Print header */
	require_once(HESK_PATH . 'inc/header.inc.php');

	/* Print main manage users page */
	require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
	?>

	</td>
	</tr>
	<tr>
	<td>

	<span class="smaller"><a href="manage_knowledgebase.php" class="smaller"><?php echo $hesklang['kb']; ?></a> &gt;
    <a href="manage_knowledgebase.php?a=manage_cat&amp;catid=<?php echo $catid; ?>" class="smaller"><?php echo $hesklang['kb_cat_man']; ?></a> &gt; <?php echo $hesklang['kb_art_edit']; ?></span>
    <br />&nbsp;

	<?php
	/* This will handle error, success and notice messages */
	hesk_handle_messages();
	?>

	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornerstop"></td>
		<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
	</tr>
	<tr>
	<td class="roundcornersleft">&nbsp;</td>
	<td>

	    <div align="center">
	    <table border="0">
	    <tr>
	    <td>

		<h3 align="center"><?php echo $hesklang['kb_art_edit']; ?></h3>
        <br />

        <?php
        if ($hesk_settings['kb_wysiwyg'])
        {
	        ?>
			<script type="text/javascript">
			tinyMCE.init({
				mode : "exact",
				elements : "content",
				theme : "advanced",
                convert_urls : false,

				theme_advanced_buttons1 : "cut,copy,paste,|,undo,redo,|,formatselect,fontselect,fontsizeselect,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull",
				theme_advanced_buttons2 : "sub,sup,|,charmap,|,bullist,numlist,|,outdent,indent,insertdate,inserttime,preview,|,forecolor,backcolor,|,hr,removeformat,visualaid,|,link,unlink,anchor,image,cleanup,code",
				theme_advanced_buttons3 : "",

				theme_advanced_toolbar_location : "top",
				theme_advanced_toolbar_align : "left",
				theme_advanced_statusbar_location : "bottom",
				theme_advanced_resizing : true,
			});
			</script>
	        <?php
        }
        ?>

		<form action="manage_knowledgebase.php" method="post" name="form1" enctype="multipart/form-data">

		<table border="0">
		<tr>
		<td><b><?php echo $hesklang['kb_cat']; ?>:</b></td>
		<td><select name="catid"><?php $listBox->printMenu()?></select></td>
		</tr>
		<tr>
		<td valign="top"><b><?php echo $hesklang['kb_type']; ?>:</b></td>
		<td>
		<label><input type="radio" name="type" value="0" <?php if ($article['type']==0) {echo 'checked="checked"';} ?> /> <b><i><?php echo $hesklang['kb_published']; ?></i></b></label><br />
		<?php echo $hesklang['kb_published2']; ?><br />&nbsp;<br />
		<label><input type="radio" name="type" value="1" <?php if ($article['type']==1) {echo 'checked="checked"';} ?> /> <b><i><?php echo $hesklang['kb_private']; ?></i></b></label><br />
		<?php echo $hesklang['kb_private2']; ?><br />&nbsp;<br />
		<label><input type="radio" name="type" value="2" <?php if ($article['type']==2) {echo 'checked="checked"';} ?> /> <b><i><?php echo $hesklang['kb_draft']; ?></i></b></label><br />
		<?php echo $hesklang['kb_draft2']; ?><br />&nbsp;
		</td>
		</tr>
		<tr>
		<td><b><?php echo $hesklang['kb_subject']; ?>:</b></td>
		<td><input type="text" name="subject" size="70" maxlength="255" value="<?php echo $article['subject']; ?>" /></td>
		</tr>
		<tr>
		<td valign="top"><b><?php echo $hesklang['opt']; ?>:</b></td>
		<td>
		<label><input type="checkbox" name="resetviews" value="Y" <?php if (isset($_SESSION['edit_article']['resetviews']) && $_SESSION['edit_article']['resetviews'] == 'Y') {echo 'checked="checked"';} ?> /> <i><?php echo $hesklang['rv']; ?></i></label><br />
	    <label><input type="checkbox" name="resetvotes" value="Y" <?php if (isset($_SESSION['edit_article']['resetvotes']) && $_SESSION['edit_article']['resetvotes'] == 'Y') {echo 'checked="checked"';} ?> /> <i><?php echo $hesklang['rr']; ?></i></label>
		</td>
		</tr>
		</table>

        <?php
        $displayType = $hesk_settings['kb_wysiwyg'] ? 'none' : 'block';
        $displayWarn = $article['html'] ? 'block' : 'none';
        ?>

		<p><b><?php echo $hesklang['kb_content']; ?>:</b></p>

        <span id="contentType" style="display:<?php echo $displayType; ?>">
        <label><input type="radio" name="html" value="0" <?php if (!$article['html']) {echo 'checked="checked"';} ?> onclick="javascript:document.getElementById('kblinks').style.display = 'none'" /> <?php echo $hesklang['kb_dhtml']; ?></label><br />
        <label><input type="radio" name="html" value="1" <?php if ($article['html']) {echo 'checked="checked"';} ?> onclick="javascript:document.getElementById('kblinks').style.display = 'block'" /> <?php echo $hesklang['kb_ehtml']; ?></label>
        <span id="kblinks" style="display:<?php echo $displayWarn; ?>"><i><?php echo $hesklang['kb_links']; ?></i></span>
        </span>

		<p><textarea name="content" rows="25" cols="70" id="content"><?php echo $article['content']; ?></textarea></p>

		<p><b><?php echo $hesklang['attachments']; ?>:</b><br />
        <?php
	    if ( ! empty($article['attachments']))
	    {
			$att=explode(',',substr($article['attachments'], 0, -1));
			foreach ($att as $myatt)
	        {
				list($att_id, $att_name) = explode('#', $myatt);
				echo '[<a href="manage_knowledgebase.php?a=remove_kb_att&amp;id='.$id.'&amp;kb_att='.$att_id.'&amp;token='.hesk_token_echo(0).'" onclick="return hesk_confirmExecute(\''.$hesklang['delatt'].'\');">'.$hesklang['remove'].'</a>] <img src="../img/clip.png" width="16" height="16" alt="'.$att_name.'" style="align:text-bottom" /> <a href="../download_attachment.php?kb_att='.$att_id.'" rel="nofollow">'.$att_name.'</a><br />';
			}
			echo '<br />';
	    }
        ?>

        <input type="file" name="attachment[1]" size="50" /><br />
        <input type="file" name="attachment[2]" size="50" /><br />
        <input type="file" name="attachment[3]" size="50" /><br />
		<?php echo$hesklang['accepted_types']; ?>: <?php echo '*'.implode(', *', $hesk_settings['attachments']['allowed_types']); ?><br />
		<?php echo $hesklang['max_file_size']; ?>: <?php echo $hesk_settings['attachments']['max_size']; ?> Kb
		(<?php echo sprintf("%01.2f",($hesk_settings['attachments']['max_size']/1024)); ?> Mb)
		</p>

		<p align="center"><input type="hidden" name="a" value="save_article" />
	    <input type="hidden" name="id" value="<?php echo $id; ?>" /><input type="hidden" name="old_type" value="<?php echo $article['type']; ?>" />
	    <input type="hidden" name="old_catid" value="<?php echo $catid; ?>" />
        <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
        <input type="submit" value="<?php echo $hesklang['kb_save']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
        | <a href="javascript:history.go(-1)"><?php echo $hesklang['cancel']; ?></a></p>
		</form>

		</td>
		</tr>
		</table>
	    </div>

	</td>
	<td class="roundcornersright">&nbsp;</td>
	</tr>
	<tr>
	<td><img src="../img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornersbottom"></td>
	<td width="7" height="7"><img src="../img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
	</tr>
</table>

	    <br /><hr />

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornerstop"></td>
		<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
	</tr>
	<tr>
	<td class="roundcornersleft">&nbsp;</td>
	<td>

    	<h3><?php echo $hesklang['revhist']; ?></h3>

		<ul><?php echo $article['history']; ?></ul>

	</td>
	<td class="roundcornersright">&nbsp;</td>
	</tr>
	<tr>
	<td><img src="../img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornersbottom"></td>
	<td width="7" height="7"><img src="../img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
	</tr>
</table>

	<?php
    /* Clean unneeded session variables */
    hesk_cleanSessionVars('edit_article');

    require_once(HESK_PATH . 'inc/footer.inc.php');
    exit();
} // END edit_article()


function manage_category() {
	global $hesk_settings, $hesklang;

    $catid = hesk_isNumber($_GET['catid'],$hesklang['kb_cat_inv']);

	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` ORDER BY `parent` ASC, `cat_order` ASC';
	$result = hesk_dbQuery($sql);
	$kb_cat = array();

	while ($cat = hesk_dbFetchAssoc($result))
	{
		$kb_cat[] = $cat;
        if ($cat['id'] == $catid)
        {
        	$this_cat = $cat;
        }
	}

    if (isset($_SESSION['manage_cat']))
    {
    	$_SESSION['manage_cat'] = hesk_stripArray($_SESSION['manage_cat']);
		$this_cat['type'] = $_SESSION['manage_cat']['type'];
        $this_cat['parent'] = $_SESSION['manage_cat']['parent'];
        $this_cat['name'] = $_SESSION['manage_cat']['title'];
    }

	/* Translate main category "Knowledgebase" if needed */
	$kb_cat[0]['name'] = $hesklang['kb_text'];

	require(HESK_PATH . 'inc/TreeMenu.php');
	$icon         = HESK_PATH . 'img/folder.gif';
	$expandedIcon = HESK_PATH . 'img/folder-expanded.gif';
    $menu		  = new HTML_TreeMenu();

	$thislevel = array('0');
	$nextlevel = array();
	$i = 1;
	$j = 1;

	while (count($kb_cat) > 0)
	{

	    foreach ($kb_cat as $k=>$cat)
	    {

	    	if (in_array($cat['parent'],$thislevel))
	        {

	        	$up = $cat['parent'];
	            $my = $cat['id'];
	            $type = $cat['type'] ? '*' : '';

	            $text_short = $cat['name'].$type.' ('.$cat['articles'].')';

	            if (isset($node[$up]))
	            {
		            $node[$my] = &$node[$up]->addItem(new HTML_TreeNode(array('hesk_parent' => $this_cat['parent'], 'text' => 'Text', 'text_short' => $text_short, 'hesk_catid' => $cat['id'], 'hesk_select' => 'option'.$j, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true)));
	            }
	            else
	            {
	                $node[$my] = new HTML_TreeNode(array('hesk_parent' => $this_cat['parent'], 'text' => 'Text',  'text_short' => $text_short, 'hesk_catid' => $cat['id'], 'hesk_select' => 'option'.$j, 'icon' => $icon, 'expandedIcon' => $expandedIcon, 'expanded' => true));
	            }

		        $nextlevel[] = $cat['id'];
	            $j++;
		        unset($kb_cat[$k]);

	        }

	    }

	    $thislevel = $nextlevel;
	    $nextlevel = array();

	    /* Break after 20 recursions to avoid hang-ups in case of any problems */

	    if ($i > 20)
	    {
	    	break;
	    }
	    $i++;
	}

	$menu->addItem($node[1]);

	// Create the presentation class
	$listBox  = & ref_new(new HTML_TreeMenu_Listbox($menu));

	/* Print header */
	require_once(HESK_PATH . 'inc/header.inc.php');

	/* Print main manage users page */
	require_once(HESK_PATH . 'inc/show_admin_nav.inc.php');
	?>

	</td>
	</tr>
	<tr>
	<td>

    <span class="smaller"><a href="manage_knowledgebase.php" class="smaller"><?php echo $hesklang['kb']; ?></a> &gt; <?php echo $hesklang['kb_cat_man']; ?></span>

	<!-- SUB NAVIGATION -->
	<?php show_subnav('',$catid); ?>
	<!-- SUB NAVIGATION -->

	<?php
    if (!isset($_SESSION['hide']['article_list']))
    {
    ?>

    <h3><?php echo $hesklang['category']; ?>: <span class="black"><?php echo $this_cat['name']; ?></span></h3>

    &nbsp;<br />

    <?php
    $sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` WHERE `catid`='.hesk_dbEscape($catid).' ORDER BY `art_order` ASC';
	$result = hesk_dbQuery($sql);
    $num	= hesk_dbNumRows($result);

    if ($num == 0)
    {
    	echo '<p>'.$hesklang['kb_no_art'].' &nbsp; <a href="manage_knowledgebase.php?a=add_article&amp;catid='.$catid.'"><img src="../img/add_article.png" width="16" height="16" alt="'.$hesklang['kb_i_art2'].'" title="'.$hesklang['kb_i_art2'].'" border="0" style="border:none;vertical-align:text-bottom" /></a> <a href="manage_knowledgebase.php?a=add_article&amp;catid='.$catid.'"><b>'.$hesklang['kb_i_art2'].'</b></a></p>';
    }
    else
    {
    	?>
        <div style="float:right">
	        <?php echo '<a href="manage_knowledgebase.php?a=add_article&amp;catid='.$catid.'"><img src="../img/add_article.png" width="16" height="16" alt="'.$hesklang['kb_i_art2'].'" title="'.$hesklang['kb_i_art2'].'" border="0" style="border:none;vertical-align:text-bottom" /></a> <a href="manage_knowledgebase.php?a=add_article&amp;catid='.$catid.'"><b>'.$hesklang['kb_i_art2'].'</b></a>'; ?>
	    </div>

	    <h3 style="padding-bottom:5px;">&raquo; <?php echo $hesklang['kb_cat_art']; ?></h3>

		<div align="center">
		<table border="0" width="100%" cellspacing="1" cellpadding="3" class="white">
		<tr>
        <th class="admin_white">&nbsp;</th>
		<th class="admin_white"><b><i><?php echo $hesklang['kb_subject']; ?></i></b></th>
		<th class="admin_white"><b><i><?php echo $hesklang['kb_type']; ?></i></b></th>
        <th class="admin_white"><b><i><?php echo $hesklang['views']; ?></i></b></th>
        <?php
        if ($hesk_settings['kb_rating'])
        {
	        ?>
	        <th class="admin_white" style="white-space:nowrap" nowrap="nowrap" width="130"><b><i><?php echo $hesklang['rating'].' ('.$hesklang['votes'].')'; ?></i></b></th>
	        <?php
        }
        ?>
        <th class="admin_white" style="width:120px"><b><i>&nbsp;<?php echo $hesklang['opt']; ?>&nbsp;</i></b></th>
		</tr>
    	<?php

		$i=1;
        $j=1;

		while ($article=hesk_dbFetchAssoc($result))
		{

			if (isset($_SESSION['artord']) && $article['id'] == $_SESSION['artord'])
			{
				$color = 'admin_green';
				unset($_SESSION['artord']);
			}
			else
			{
				$color = $i ? 'admin_white' : 'admin_gray';
			}

			$tmp   = $i ? 'White' : 'Blue';
			$style = 'class="option'.$tmp.'OFF" onmouseover="this.className=\'option'.$tmp.'ON\'" onmouseout="this.className=\'option'.$tmp.'OFF\'"';
			$i     = $i ? 0 : 1;

        	switch ($article['type'])
            {
            	case '1':
                	$type = $hesklang['kb_private'];
                	break;
                case '2':
                	$type = $hesklang['kb_draft'];
                	break;
                default:
                	$type = $hesklang['kb_published'];
            }

            if ($hesk_settings['kb_rating'])
            {
	            $alt = $article['rating'] ? sprintf($hesklang['kb_rated'], sprintf("%01.1f", $article['rating'])) : $hesklang['kb_not_rated'];
	            $rat = '<td class="'.$color.'" style="white-space:nowrap;"><img src="../img/star_'.(hesk_round_to_half($article['rating'])*10).'.png" width="85" height="16" alt="'.$alt.'" title="'.$alt.'" border="0" style="vertical-align:text-bottom" /> ('.$article['votes'].') </td>';
            }
            else
            {
            	$rat = '';
            }

        	?>
			<tr>
			<td class="<?php echo $color; ?>"><?php echo $j; ?>.</td>
			<td class="<?php echo $color; ?>"><?php echo $article['subject']; ?></td>
            <td class="<?php echo $color; ?>"><?php echo $type; ?></td>
            <td class="<?php echo $color; ?>"><?php echo $article['views']; ?></td>
            <?php echo $rat; ?>
            <td class="<?php echo $color; ?>" style="text-align:center; white-space:nowrap;">
			<?php
            if ($num > 1)
            {
            	if ($j == 1)
                {
	            ?>
                    <img src="../img/blank.gif" width="16" height="16" alt="" style="padding:3px;border:none;" />
                	<a href="manage_knowledgebase.php?a=order_article&amp;id=<?php echo $article['id']; ?>&amp;catid=<?php echo $catid; ?>&amp;move=15&amp;token=<?php hesk_token_echo(); ?>"><img src="../img/move_down.png" width="16" height="16" alt="<?php echo $hesklang['move_dn']; ?>" title="<?php echo $hesklang['move_dn']; ?>" <?php echo $style; ?> /></a>
	            <?php
                }
                elseif ($j == $num)
                {
	            ?>
					<a href="manage_knowledgebase.php?a=order_article&amp;id=<?php echo $article['id']; ?>&amp;catid=<?php echo $catid; ?>&amp;move=-15&amp;token=<?php hesk_token_echo(); ?>"><img src="../img/move_up.png" width="16" height="16" alt="<?php echo $hesklang['move_up']; ?>" title="<?php echo $hesklang['move_up']; ?>" <?php echo $style; ?> /></a>
                    <img src="../img/blank.gif" width="16" height="16" alt="" style="padding:3px;border:none;" />
	            <?php
                }
                else
                {
	            ?>
					<a href="manage_knowledgebase.php?a=order_article&amp;id=<?php echo $article['id']; ?>&amp;catid=<?php echo $catid; ?>&amp;move=-15&amp;token=<?php hesk_token_echo(); ?>"><img src="../img/move_up.png" width="16" height="16" alt="<?php echo $hesklang['move_up']; ?>" title="<?php echo $hesklang['move_up']; ?>" <?php echo $style; ?> /></a>
					<a href="manage_knowledgebase.php?a=order_article&amp;id=<?php echo $article['id']; ?>&amp;catid=<?php echo $catid; ?>&amp;move=15&amp;token=<?php hesk_token_echo(); ?>"><img src="../img/move_down.png" width="16" height="16" alt="<?php echo $hesklang['move_dn']; ?>" title="<?php echo $hesklang['move_dn']; ?>" <?php echo $style; ?> /></a>
	            <?php
                }
            }
            else
            {
            	echo '<img src="../img/blank.gif" width="16" height="16" alt="" style="padding:3px;border:none;" />';
            }
            ?>
            <a href="knowledgebase_private.php?article=<?php echo $article['id']; ?>&amp;back=1" target="_blank"><img src="../img/article_text.png" width="16" height="16" alt="<?php echo $hesklang['viewart']; ?>" title="<?php echo $hesklang['viewart']; ?>" <?php echo $style; ?> /></a>
            <a href="manage_knowledgebase.php?a=edit_article&amp;id=<?php echo $article['id']; ?>"><img src="../img/edit.png" width="16" height="16" alt="<?php echo $hesklang['edit']; ?>" title="<?php echo $hesklang['edit']; ?>" <?php echo $style; ?> /></a>
            <a href="manage_knowledgebase.php?a=remove_article&amp;id=<?php echo $article['id']; ?>&amp;token=<?php hesk_token_echo(); ?>" onclick="return hesk_confirmExecute('<?php echo $hesklang['del_art']; ?>');"><img src="../img/delete.png" width="16" height="16" alt="<?php echo $hesklang['delete']; ?>" title="<?php echo $hesklang['delete']; ?>" <?php echo $style; ?> /></a>&nbsp;</td>
			</tr>
            <?php
			$j++;
		} // End while
		?>
		</table>
		</div>
		<?php
    }

        } // END if hide article list

        /* Manage Category (except the default one) */
		if ($catid != 1)
		{
        ?>

        &nbsp;<br />
        &nbsp;<br />

        <div style="float:right">
	        <?php echo '<a href="manage_knowledgebase.php?a=add_category&amp;parent='.$catid.'"><img src="../img/add_category.png" width="16" height="16" alt="'.$hesklang['kb_i_cat2'].'" title="'.$hesklang['kb_i_cat2'].'" border="0" style="border:none;vertical-align:text-bottom" /></a> <a href="manage_knowledgebase.php?a=add_category&amp;parent='.$catid.'"><b>'.$hesklang['kb_i_cat2'].'</b></a>'; ?>
	    </div>

        <h3 style="padding-bottom:5px;">&raquo; <?php echo $hesklang['catset']; ?></h3>

	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
			<td class="roundcornerstop"></td>
			<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
		</tr>
		<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>

			<form action="manage_knowledgebase.php" method="post" name="form1" onsubmit="Javascript:return hesk_deleteIfSelected('dodelete','<?php echo addslashes($hesklang['kb_delcat']); ?>')">

			<div align="center">
			<table border="0">
			<tr>
			<td>

			<table border="0">
			<tr>
			<td><b><?php echo $hesklang['kb_cat_title']; ?>:</b></td>
			<td><input type="text" name="title" size="70" maxlength="255" value="<?php echo $this_cat['name']; ?>" /></td>
			</tr>
			<tr>
			<td><b><?php echo $hesklang['kb_cat_parent']; ?>:</b></td>
			<td><select name="parent"><?php $listBox->printMenu();  ?></select></td>
			</tr>
			<tr>
			<td valign="top"><b><?php echo $hesklang['kb_type']; ?>:</b></td>
			<td>
				<label><input type="radio" name="type" value="0" <?php if (!$this_cat['type']) {echo 'checked="checked"';} ?> /> <b><i><?php echo $hesklang['kb_published']; ?></i></b></label><br />
				<?php echo $hesklang['kb_cat_published']; ?><br />&nbsp;<br />
				<label><input type="radio" name="type" value="1" <?php if ($this_cat['type']) {echo 'checked="checked"';} ?> /> <b><i><?php echo $hesklang['kb_private']; ?></i></b></label><br />
				<?php echo $hesklang['kb_cat_private']; ?><br />&nbsp;
			</td>
			</tr>
	        <tr>
	        <td valign="top"><b><?php echo $hesklang['opt']; ?>:</b></td>
	        <td>
	        	<label><input type="checkbox" name="dodelete" id="dodelete" value="Y" onclick="Javascript:hesk_toggleLayerDisplay('deleteoptions')" /> <i><?php echo $hesklang['delcat']; ?></i></label>
	            <div id="deleteoptions" style="display: none;">
	            &nbsp;&nbsp;&nbsp;&nbsp;<label><input type="radio" name="movearticles" value="Y" checked="checked" /> <?php echo $hesklang['move1']; ?></label><br />
	            &nbsp;&nbsp;&nbsp;&nbsp;<label><input type="radio" name="movearticles" value="N" /> <?php echo $hesklang['move2']; ?></label>
	            </div>
	        </td>
	        </tr>
			</table>

			</td>
			</tr>
			</table>
			</div>

			<p align="center"><input type="hidden" name="a" value="edit_category" />
	        <input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
	        <input type="hidden" name="catid" value="<?php echo $catid; ?>" /><input type="submit" value="<?php echo $hesklang['save_changes']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>
			</form>

		</td>
		<td class="roundcornersright">&nbsp;</td>
		</tr>
		<tr>
		<td><img src="../img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornersbottom"></td>
		<td width="7" height="7"><img src="../img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
		</tr>
	</table>

	<?php
    } // END if $catid != 1

    echo '&nbsp;<br />&nbsp;';

	/* Clean unneeded session variables */
	hesk_cleanSessionVars(array('hide','manage_cat','edit_article'));

    require_once(HESK_PATH . 'inc/footer.inc.php');
    exit();
} // END manage_category()


function new_category() {
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check($_POST['token']);

	$_SESSION['hide'] = array(
		'treemenu' => 1,
		'new_article' => 1,
		//'new_category' => 1,
	);

    $parent = hesk_isNumber($_POST['parent']) or $parent = 1;
    $type   = ($_POST['type']) ? 1 : 0;

    $_SESSION['KB_CATEGORY'] = $parent;
    $_SERVER['PHP_SELF'] = 'manage_knowledgebase.php';

    /* Check that title is valid */
	$title  = hesk_input($_POST['title']);
	if (!strlen($title))
	{
		$_SESSION['new_category'] = array(
			'type' => $type,
		);

		hesk_process_messages($hesklang['kb_cat_e_title'],$_SERVER['PHP_SELF']);
	}

	/* Get the latest reply_order */
	$sql = 'SELECT `cat_order` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` ORDER BY `cat_order` DESC LIMIT 1';
	$res = hesk_dbQuery($sql);
	$row = hesk_dbFetchRow($res);
	$my_order = $row[0]+10;

	$sql = "INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` (`name`,`parent`,`articles`,`cat_order`,`type`) VALUES ('".hesk_dbEscape($title)."','".hesk_dbEscape($parent)."',0,'".hesk_dbEscape($my_order)."','".hesk_dbEscape($type)."')";
	$result = hesk_dbQuery($sql);

    $_SESSION['newcat'] = hesk_dbInsertID();

	$_SESSION['hide'] = array(
		'treemenu' => 1,
		'new_article' => 1,
		//'new_category' => 1,
        'cat_treemenu' => 1,
	);

    hesk_process_messages($hesklang['kb_cat_added2'],$_SERVER['PHP_SELF'],'SUCCESS');
} // End new_category()


function new_article() {
	global $hesk_settings, $hesklang, $listBox;
    global $hesk_error_buffer;

	/* A security check */
	# hesk_token_check($_POST['token']);

	$_SESSION['hide'] = array(
		'treemenu' => 1,
		//'new_article' => 1,
		'new_category' => 1,
	);

    $hesk_error_buffer = array();

	$catid = hesk_isNumber($_POST['catid']) or $catid = 1;
    $type  = ($_POST['type'] == 1 || $_POST['type'] == 2) ? $_POST['type'] : 0;
    $html  = $hesk_settings['kb_wysiwyg'] ? 1 : ($_POST['html'] ? 1 : 0);
    $now   = hesk_date();

    $_SESSION['KB_CATEGORY'] = $catid;

    $subject = hesk_input($_POST['subject']) or $hesk_error_buffer[] = $hesklang['kb_e_subj'];

    if ($html)
    {
	    if (empty($_POST['content']))
	    {
        	$hesk_error_buffer[] = $hesklang['kb_e_cont'];
	    }

        $content = hesk_getHTML($_POST['content']);
    }
	else
    {
    	$content = hesk_input($_POST['content']) or $hesk_error_buffer[] = $hesklang['kb_e_cont'];
	    $content = nl2br($content);
	    $content = hesk_makeURL($content);
    }

    /* Article attachments */
	define('KB',1);
    require_once(HESK_PATH . 'inc/attachments.inc.php');
    $attachments = array();
    for ($i=1;$i<=3;$i++)
    {
        $att = hesk_uploadFile($i);
        if ( ! empty($att))
        {
            $attachments[$i] = $att;
        }
    }
	$myattachments='';

    /* Any errors? */
    if (count($hesk_error_buffer))
    {
		$_SESSION['new_article'] = array(
		'type' => $type,
		'html' => $html,
		'subject' => $subject,
		'content' => hesk_input($_POST['content']),
		);

		$tmp = '';
		foreach ($hesk_error_buffer as $error)
		{
			$tmp .= "<li>$error</li>\n";
		}
		$hesk_error_buffer = $tmp;

    	$hesk_error_buffer = $hesklang['rfm'].'<br /><br /><ul>'.$hesk_error_buffer.'</ul>';
    	hesk_process_messages($hesk_error_buffer,'manage_knowledgebase.php');
    }

    $revision = sprintf($hesklang['revision1'],$now,$_SESSION['name'].' ('.$_SESSION['user'].')');

	/* Add to database */
	if ( ! empty($attachments))
	{
	    foreach ($attachments as $myatt)
	    {
	        $sql = "INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` (`saved_name`,`real_name`,`size`) VALUES (
            '".hesk_dbEscape($myatt['saved_name'])."',
            '".hesk_dbEscape($myatt['real_name'])."',
            '".hesk_dbEscape($myatt['size'])."'
            )";
	        $result = hesk_dbQuery($sql);
	        $myattachments .= hesk_dbInsertID() . '#' . $myatt['real_name'] .',';
	    }
	}

	/* Get the latest reply_order */
	$sql = 'SELECT `art_order` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` WHERE `catid`='.hesk_dbEscape($catid).' ORDER BY `art_order` DESC LIMIT 1';
	$res = hesk_dbQuery($sql);
	$row = hesk_dbFetchRow($res);
	$my_order = $row[0]+10;

    /* Insert article into database */
	$sql = "INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` (`catid`,`dt`,`author`,`subject`,`content`,`type`,`html`,`art_order`,`history`,`attachments`) VALUES (
    '".hesk_dbEscape($catid)."',
    NOW(),
    '".hesk_dbEscape($_SESSION['id'])."',
    '".hesk_dbEscape($subject)."',
    '".hesk_dbEscape($content)."',
    '".hesk_dbEscape($type)."',
    '".hesk_dbEscape($html)."',
    '".hesk_dbEscape($my_order)."',
    '".hesk_dbEscape($revision)."',
    '".hesk_dbEscape($myattachments)."'
    )";
	$result = hesk_dbQuery($sql);

    $_SESSION['artord'] = hesk_dbInsertID();

	/* Update category article count */
    if ($type == 0)
    {
	    $sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` SET `articles`=`articles`+1 WHERE `id`='.hesk_dbEscape($catid);
		$result = hesk_dbQuery($sql);
	}

    unset($_SESSION['hide']);

    hesk_process_messages($hesklang['your_kb_added'],'NOREDIRECT','SUCCESS');
    $_GET['catid'] = $catid;
    manage_category();
} // End new_article()


function remove_article() {
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check($_GET['token']);

	$id = hesk_isNumber($_GET['id'],$hesklang['kb_art_id']);

    /* Get article details */
	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` WHERE `id`='.hesk_dbEscape($id).' LIMIT 1';
	$result = hesk_dbQuery($sql);

    if (hesk_dbNumRows($result) != 1)
    {
    	hesk_error($hesklang['kb_art_id']);
    }

    $article = hesk_dbFetchAssoc($result);
	$catid = $article['catid'];

    $sql = 'DELETE FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` WHERE `id`='.hesk_dbEscape($id).' LIMIT 1';
    $result = hesk_dbQuery($sql);

    /* Remove any attachments */
	if (!empty($article['attachments']))
	{
		$att=explode(',',substr($article['attachments'], 0, -1));
		foreach ($att as $myatt)
		{
			list($att_id, $att_name) = explode('#', $myatt);

			/* Get attachment info */
			$sql = "SELECT * FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` WHERE `att_id`=".hesk_dbEscape($att_id)." LIMIT 1";
			$result = hesk_dbQuery($sql);
			if (hesk_dbNumRows($result) == 1)
			{
				$file = hesk_dbFetchAssoc($result);
                unlink('../attachments/'.$file['saved_name']);
			}
			$sql = "DELETE FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` WHERE `att_id`=".hesk_dbEscape($att_id)." LIMIT 1";
			$result = hesk_dbQuery($sql);
		}
	}

    /* Update category article count */
    if ($article['type'] == 0)
	{
    	$sql = 'UPDATE `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` SET `articles`=`articles`-1 WHERE `id`='.hesk_dbEscape($catid);
		$result = hesk_dbQuery($sql);
    }

	hesk_process_messages($hesklang['your_kb_deleted'],'./manage_knowledgebase.php?a=manage_cat&catid='.$catid,'SUCCESS');
} // End remove_article()


function order_article() {
	global $hesk_settings, $hesklang;

	/* A security check */
	hesk_token_check($_GET['token']);

	$id    = hesk_isNumber($_GET['id'],$hesklang['kb_art_id']);
    $catid = hesk_isNumber($_GET['catid'],$hesklang['kb_cat_inv']);
	$move  = intval($_GET['move']);

    $_SESSION['artord'] = $id;

	$sql = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET `art_order`=`art_order`+".hesk_dbEscape($move)." WHERE `id`=".hesk_dbEscape($id)." LIMIT 1";
	$result = hesk_dbQuery($sql);
	if (hesk_dbAffectedRows() != 1)
    {
    	hesk_error($hesklang['kb_art_id']);
    }

	/* Update all category fields with new order */
	$sql = 'SELECT `id` FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` WHERE `catid`='.hesk_dbEscape($catid).' ORDER BY `art_order` ASC';
	$result = hesk_dbQuery($sql);

	$i = 10;
	while ($article=hesk_dbFetchAssoc($result))
	{
	    $sql = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` SET `art_order`=".hesk_dbEscape($i)." WHERE `id`=".hesk_dbEscape($article['id'])." LIMIT 1";
	    hesk_dbQuery($sql);
	    $i += 10;
	}

	header('Location: manage_knowledgebase.php?a=manage_cat&catid='.$catid);
	exit();
} // End order_article()


function show_treeMenu() {
	global $hesk_settings, $hesklang, $treeMenu;
	?>
	<script src="<?php echo HESK_PATH; ?>TreeMenu.js" language="JavaScript" type="text/javascript"></script>

	<h3 style="padding-bottom:5px;">&raquo; <?php echo $hesklang['kbstruct']; ?></h3>

	<div align="center">
	<table border="0" cellspacing="0" cellpadding="0" width="100%">
		<tr>
			<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
			<td class="roundcornerstop"></td>
			<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
		</tr>
		<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>

		<?php
		$treeMenu->printMenu();
		?>

		</td>
		<td class="roundcornersright">&nbsp;</td>
		</tr>
		<tr>
		<td><img src="../img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornersbottom"></td>
		<td width="7" height="7"><img src="../img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
		</tr>
	</table>
	</div>

	&nbsp;<br />

	<img src="../img/add_article.png" width="16" height="16" alt="<?php echo $hesklang['kb_i_art']; ?>" title="<?php echo $hesklang['kb_i_art']; ?>" style="padding:1px" class="optionWhiteNbOFF" /> = <?php echo $hesklang['kb_p_art2']; ?><br />
	<img src="../img/add_category.png" width="16" height="16" alt="<?php echo $hesklang['kb_i_cat']; ?>" title="<?php echo $hesklang['kb_i_cat']; ?>" style="padding:1px" class="optionWhiteNbOFF" /> = <?php echo $hesklang['kb_p_cat2']; ?><br />
	<img src="../img/manage.png" width="16" height="16" alt="<?php echo $hesklang['kb_p_man']; ?>" title="<?php echo $hesklang['kb_p_man']; ?>" style="padding:1px" class="optionWhiteNbOFF" /> = <?php echo $hesklang['kb_p_man2']; ?><br />
	<?php
}


function show_subnav($hide='',$catid=1) {
	global $hesk_settings, $hesklang;

    $link['view'] = '<a href="knowledgebase_private.php"><img src="../img/view.png" width="16" height="16" alt="'.$hesklang['gopr'].'" title="'.$hesklang['gopr'].'" border="0" style="border:none;vertical-align:text-bottom" /></a> <a href="knowledgebase_private.php">'.$hesklang['gopr'].'</a> | ';
    $link['newa'] = '<a href="manage_knowledgebase.php?a=add_article&amp;catid='.$catid.'"><img src="../img/add_article.png" width="16" height="16" alt="'.$hesklang['kb_i_art'].'" title="'.$hesklang['kb_i_art'].'" border="0" style="border:none;vertical-align:text-bottom" /></a> <a href="manage_knowledgebase.php?a=add_article&amp;catid='.$catid.'">'.$hesklang['kb_i_art'].'</a> | ';
    $link['newc'] = '<a href="manage_knowledgebase.php?a=add_category&amp;parent='.$catid.'"><img src="../img/add_category.png" width="16" height="16" alt="'.$hesklang['kb_i_cat'].'" title="'.$hesklang['kb_i_cat'].'" border="0" style="border:none;vertical-align:text-bottom" /></a> <a href="manage_knowledgebase.php?a=add_category&amp;parent='.$catid.'">'.$hesklang['kb_i_cat'].'</a> | ';

    if ($hide && isset($link[$hide]))
    {
    	$link[$hide] = preg_replace('#<a([^<]*)>#', '', $link[$hide]);
        $link[$hide] = str_replace('</a>','',$link[$hide]);
    }
	?>

	<form style="margin:0px;padding:0px;" method="get" action="manage_knowledgebase.php">
    <p>
    <?php
    echo $link['view'];
    echo $link['newa'];
    echo $link['newc'];
    ?>
	<img src="../img/edit.png" width="16" height="16" alt="<?php echo $hesklang['edit']; ?>" title="<?php echo $hesklang['edit']; ?>" border="0" style="border:none;vertical-align:text-bottom" /></a> <input type="hidden" name="a" value="edit_article" /><?php echo $hesklang['aid']; ?>: <input type="text" name="id" size="3" /> <input type="submit" value="<?php echo $hesklang['edit']; ?>" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
    </p>
	</form>

    &nbsp;<br />

	<?php

	/* This will handle error, success and notice messages */
	hesk_handle_messages();

} // End show_subnav()

?>
