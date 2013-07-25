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

/* What should we do? */
$action = isset($_REQUEST['a']) ? hesk_input($_REQUEST['a']) : $action='start';

switch ($action)
{
	case 'add':
		hesk_session_start();
        print_add_ticket();
	    break;

	case 'forgot_tid':
		hesk_session_start();
        forgot_tid();
	    break;

	default:
		print_start();
}

/* Print footer */
require_once(HESK_PATH . 'inc/footer.inc.php');
exit();

/*** START FUNCTIONS ***/

function print_add_ticket() {
global $hesk_settings, $hesklang;

/* Varibles for coloring the fields in case of errors */
if (!isset($_SESSION['iserror']))
{
	$_SESSION['iserror'] = array();
}

if (!isset($_SESSION['isnotice']))
{
	$_SESSION['isnotice'] = array();
}

hesk_cleanSessionVars('already_submitted');

/* Print header */
$hesk_settings['tmp_title'] = $hesk_settings['hesk_title'] . ' - ' . $hesklang['submit_ticket'];
require_once(HESK_PATH . 'inc/header.inc.php');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td width="3"><img src="img/headerleftsm.jpg" width="3" height="25" alt="" /></td>
<td class="headersm"><?php hesk_showTopBar($hesklang['submit_ticket']); ?></td>
<td width="3"><img src="img/headerrightsm.jpg" width="3" height="25" alt="" /></td>
</tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="3">
<tr>
<td><span class="smaller"><a href="<?php echo $hesk_settings['site_url']; ?>" class="smaller"><?php echo $hesk_settings['site_title']; ?></a> &gt;
<a href="<?php echo $hesk_settings['hesk_url']; ?>" class="smaller"><?php echo $hesk_settings['hesk_title']; ?></a>
&gt; <?php echo $hesklang['submit_ticket']; ?></span></td>
</tr>
</table>

</td>
</tr>
<tr>
<td>

<?php
/* This will handle error, success and notice messages */
hesk_handle_messages();
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td width="7" height="7"><img src="img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornerstop"></td>
	<td><img src="img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
</tr>
<tr>
	<td class="roundcornersleft">&nbsp;</td>
	<td>
    <!-- START FORM -->

	<p><?php echo $hesklang['use_form_below']; ?>
	<font class="important"> *</font></p>

	<form method="post" action="submit_ticket.php" name="form1" enctype="multipart/form-data">

	<!-- Contact info -->
	<table border="0" width="100%">
	<tr>
	<td style="text-align:right" width="150"><?php echo $hesklang['name']; ?>: <font class="important">*</font></td>
	<td width="80%"><input type="text" name="name" size="40" maxlength="30" value="<?php if (isset($_SESSION['c_name'])) {echo stripslashes(hesk_input($_SESSION['c_name']));} ?>" <?php if (in_array('name',$_SESSION['iserror'])) {echo ' class="isError" ';} ?> /></td>
	</tr>
	<tr>
	<td style="text-align:right" width="150"><?php echo $hesklang['email']; ?>: <font class="important">*</font></td>
	<td width="80%"><input type="text" name="email" size="40" maxlength="50" value="<?php if (isset($_SESSION['c_email'])) {echo stripslashes(hesk_input($_SESSION['c_email']));} ?>" <?php if (in_array('email',$_SESSION['iserror'])) {echo ' class="isError" ';} elseif (in_array('email',$_SESSION['isnotice'])) {echo ' class="isNotice" ';} ?> /></td>
	</tr>
    <?php
    if ($hesk_settings['confirm_email'])
    {
	    ?>
		<tr>
		<td style="text-align:right" width="150"><?php echo $hesklang['confemail']; ?>: <font class="important">*</font></td>
		<td width="80%"><input type="text" name="email2" size="40" maxlength="50" value="<?php if (isset($_SESSION['c_email2'])) {echo stripslashes(hesk_input($_SESSION['c_email2']));} ?>" <?php if (in_array('email2',$_SESSION['iserror'])) {echo ' class="isError" ';} ?> /></td>
		</tr>
	    <?php
    } // End if $hesk_settings['confirm_email']
    ?>
	</table>

	<hr />

	<!-- Department and priority -->

    <?php
    $is_table = 0;

	require(HESK_PATH . 'inc/database.inc.php');

    /* Get categories */
	hesk_dbConnect();
	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'categories` ORDER BY `cat_order` ASC';
	$res = hesk_dbQuery($sql);

    if (hesk_dbNumRows($res) == 1)
    {
    	$row = hesk_dbFetchAssoc($res);
		echo '<input type="hidden" name="category" value="'.$row['id'].'" />';
    }
    else
    {
		$is_table = 1;
		?>
		<table border="0" width="100%">
		<tr>
		<td style="text-align:right" width="150"><?php echo $hesklang['category']; ?>: <font class="important">*</font></td>
		<td width="80%"><select name="category" <?php if (in_array('category',$_SESSION['iserror'])) {echo ' class="isError" ';} ?> >
		<?php
		if (!empty($_GET['catid']))
		{
			$_SESSION['c_category'] = intval($_GET['catid']);
		}

		while ($row = hesk_dbFetchAssoc($res))
		{
			$selected = (isset($_SESSION['c_category']) && $_SESSION['c_category'] == $row['id']) ? ' selected="selected"' : '';
			echo '<option value="'.$row['id'].'"'.$selected.'>'.$row['name'].'</option>';
		}
		?>
		</select></td>
		</tr>
        <?php
    }

	/* Can customer assign urgency? */
	if ($hesk_settings['cust_urgency'])
	{
		if (!$is_table)
		{
			echo '<table border="0" width="100%">';
            $is_table = 1;
		}
		?>
		<tr>
		<td style="text-align:right" width="150"><?php echo $hesklang['priority']; ?>: <font class="important">*</font></td>
		<td width="80%"><select name="priority" <?php if (in_array('priority',$_SESSION['iserror'])) {echo ' class="isError" ';} ?> >
		<option value="3" <?php if(isset($_SESSION['c_priority']) && $_SESSION['c_priority']==3) {echo 'selected="selected"';} ?>><?php echo $hesklang['low']; ?></option>
		<option value="2" <?php if(isset($_SESSION['c_priority']) && $_SESSION['c_priority']==2) {echo 'selected="selected"';} ?>><?php echo $hesklang['medium']; ?></option>
		<option value="1" <?php if(isset($_SESSION['c_priority']) && $_SESSION['c_priority']==1) {echo 'selected="selected"';} ?>><?php echo $hesklang['high']; ?></option>
		</select></td>
		</tr>
		<?php
	}

	/* Need to close the table? */
	if ($is_table)
	{
		echo '</table> <hr />';
	}
	?>
	<!-- START CUSTOM BEFORE -->
	<?php

	/* custom fields BEFORE comments */

	$print_table = 0;

	foreach ($hesk_settings['custom_fields'] as $k=>$v)
	{
		if ($v['use'] && $v['place']==0)
	    {
	    	if ($print_table == 0)
	        {
	        	echo '<table border="0" width="100%">';
	        	$print_table = 1;
	        }

			$v['req'] = $v['req'] ? '<font class="important">*</font>' : '';

			if ($v['type'] == 'checkbox')
            {
            	$k_value = array();
                if (isset($_SESSION["c_$k"]) && is_array($_SESSION["c_$k"]))
                {
	                foreach ($_SESSION["c_$k"] as $myCB)
	                {
	                	$k_value[] = stripslashes(hesk_input($myCB));
	                }
                }
            }
            elseif (isset($_SESSION["c_$k"]))
            {
            	$k_value  = stripslashes(hesk_input($_SESSION["c_$k"]));
            }
            else
            {
            	$k_value  = '';
            }

	        switch ($v['type'])
	        {
	        	/* Radio box */
	        	case 'radio':
					echo '
					<tr>
					<td style="text-align:right" width="150" valign="top">'.$v['name'].': '.$v['req'].'</td>
	                <td width="80%">';

	            	$options = explode('#HESK#',$v['value']);
                    $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

	                foreach ($options as $option)
	                {

		            	if (strlen($k_value) == 0 || $k_value == $option)
		                {
	                    	$k_value = $option;
							$checked = 'checked="checked"';
	                    }
	                    else
	                    {
	                    	$checked = '';
	                    }

	                	echo '<label><input type="radio" name="'.$k.'" value="'.$option.'" '.$checked.' '.$cls.' /> '.$option.'</label><br />';
	                }

	                echo '</td>
					</tr>
					';
	            break;

	            /* Select drop-down box */
	            case 'select':

                	$cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

					echo '
					<tr>
					<td style="text-align:right" width="150">'.$v['name'].': '.$v['req'].'</td>
	                <td width="80%"><select name="'.$k.'" '.$cls.'>';

	            	$options = explode('#HESK#',$v['value']);

	                foreach ($options as $option)
	                {

		            	if (strlen($k_value) == 0 || $k_value == $option)
		                {
	                    	$k_value = $option;
	                        $selected = 'selected="selected"';
		                }
	                    else
	                    {
	                    	$selected = '';
	                    }

	                	echo '<option '.$selected.'>'.$option.'</option>';
	                }

	                echo '</select></td>
					</tr>
					';
	            break;

	            /* Checkbox */
	        	case 'checkbox':
					echo '
					<tr>
					<td style="text-align:right" width="150" valign="top">'.$v['name'].': '.$v['req'].'</td>
	                <td width="80%">';

	            	$options = explode('#HESK#',$v['value']);
                    $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

	                foreach ($options as $option)
	                {

		            	if (in_array($option,$k_value))
		                {
							$checked = 'checked="checked"';
	                    }
	                    else
	                    {
	                    	$checked = '';
	                    }

	                	echo '<label><input type="checkbox" name="'.$k.'[]" value="'.$option.'" '.$checked.' '.$cls.' /> '.$option.'</label><br />';
	                }

	                echo '</td>
					</tr>
					';
	            break;

	            /* Large text box */
	            case 'textarea':
	                $size = explode('#',$v['value']);
                    $size[0] = empty($size[0]) ? 5 : intval($size[0]);
                    $size[1] = empty($size[1]) ? 30 : intval($size[1]);

                    $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

					echo '
					<tr>
					<td style="text-align:right" width="150" valign="top">'.$v['name'].': '.$v['req'].'</td>
					<td width="80%"><textarea name="'.$k.'" rows="'.$size[0].'" cols="'.$size[1].'" '.$cls.'>'.$k_value.'</textarea></td>
					</tr>
	                ';
	            break;

	            /* Default text input */
	            default:
                	if (strlen($k_value) != 0)
                    {
                    	$v['value'] = $k_value;
                    }

                    $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

					echo '
					<tr>
					<td style="text-align:right" width="150">'.$v['name'].': '.$v['req'].'</td>
					<td width="80%"><input type="text" name="'.$k.'" size="40" maxlength="'.$v['maxlen'].'" value="'.$v['value'].'" '.$cls.' /></td>
					</tr>
					';
	        }
	    }
	}

	/* If table was started we need to close it */
	if ($print_table)
	{
		echo '</table> <hr />';
		$print_table = 0;
	}
	?>
	<!-- END CUSTOM BEFORE -->

	<!-- ticket info -->
	<table border="0" width="100%">
	<tr>
	<td style="text-align:right" width="150"><?php echo $hesklang['subject']; ?>: <font class="important">*</font></td>
	<td width="80%"><input type="text" name="subject" size="40" maxlength="40" value="<?php if (isset($_SESSION['c_subject'])) {echo stripslashes(hesk_input($_SESSION['c_subject']));} ?>" <?php if (in_array('subject',$_SESSION['iserror'])) {echo ' class="isError" ';} ?> /></td>
	</tr>
	<tr>
	<td style="text-align:right" width="150" valign="top"><?php echo $hesklang['message']; ?>: <font class="important">*</font></td>
	<td width="80%"><textarea name="message" rows="12" cols="60" <?php if (in_array('message',$_SESSION['iserror'])) {echo ' class="isError" ';} ?> ><?php if (isset($_SESSION['c_message'])) {echo stripslashes(hesk_input($_SESSION['c_message']));} ?></textarea>

		<!-- START KNOWLEDGEBASE SUGGEST -->
		<?php
		if ($hesk_settings['kb_enable'] && $hesk_settings['kb_recommendanswers'])
		{
			?>
			<div id="kb_suggestions" style="display:none">
            <br />&nbsp;<br />
			<img src="img/loading.gif" width="24" height="24" alt="" border="0" style="vertical-align:text-bottom" /> <i><?php echo $hesklang['lkbs']; ?></i>
			</div>

			<script language="Javascript" type="text/javascript"><!--
			hesk_suggestKB();
			//-->
			</script>
			<?php
		}
		?>
		<!-- END KNOWLEDGEBASE SUGGEST -->
    </td>
	</tr>
	</table>

	<hr />

	<!-- START CUSTOM AFTER -->
	<?php
	/* custom fields AFTER comments */
	$print_table = 0;

	foreach ($hesk_settings['custom_fields'] as $k=>$v)
	{
		if ($v['use'] && $v['place'])
	    {
	    	if ($print_table == 0)
	        {
	        	echo '<table border="0" width="100%">';
	        	$print_table = 1;
	        }

			$v['req'] = $v['req'] ? '<font class="important">*</font>' : '';

			if ($v['type'] == 'checkbox')
            {
            	$k_value = array();
                if (isset($_SESSION["c_$k"]) && is_array($_SESSION["c_$k"]))
                {
	                foreach ($_SESSION["c_$k"] as $myCB)
	                {
	                	$k_value[] = stripslashes(hesk_input($myCB));
	                }
                }
            }
            elseif (isset($_SESSION["c_$k"]))
            {
            	$k_value  = stripslashes(hesk_input($_SESSION["c_$k"]));
            }
            else
            {
            	$k_value  = '';
            }


	        switch ($v['type'])
	        {
	        	/* Radio box */
	        	case 'radio':
					echo '
					<tr>
					<td style="text-align:right" width="150" valign="top">'.$v['name'].': '.$v['req'].'</td>
	                <td width="80%">';

	            	$options = explode('#HESK#',$v['value']);
                    $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

	                foreach ($options as $option)
	                {

		            	if (strlen($k_value) == 0 || $k_value == $option)
		                {
	                    	$k_value = $option;
							$checked = 'checked="checked"';
	                    }
	                    else
	                    {
	                    	$checked = '';
	                    }

	                	echo '<label><input type="radio" name="'.$k.'" value="'.$option.'" '.$checked.' '.$cls.' /> '.$option.'</label><br />';
	                }

	                echo '</td>
					</tr>
					';
	            break;

	            /* Select drop-down box */
	            case 'select':

                	$cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

					echo '
					<tr>
					<td style="text-align:right" width="150">'.$v['name'].': '.$v['req'].'</td>
	                <td width="80%"><select name="'.$k.'" '.$cls.'>';

	            	$options = explode('#HESK#',$v['value']);

	                foreach ($options as $option)
	                {

		            	if (strlen($k_value) == 0 || $k_value == $option)
		                {
	                    	$k_value = $option;
	                        $selected = 'selected="selected"';
		                }
	                    else
	                    {
	                    	$selected = '';
	                    }

	                	echo '<option '.$selected.'>'.$option.'</option>';
	                }

	                echo '</select></td>
					</tr>
					';
	            break;

	            /* Checkbox */
	        	case 'checkbox':
					echo '
					<tr>
					<td style="text-align:right" width="150" valign="top">'.$v['name'].': '.$v['req'].'</td>
	                <td width="80%">';

	            	$options = explode('#HESK#',$v['value']);
                    $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

	                foreach ($options as $option)
	                {

		            	if (in_array($option,$k_value))
		                {
							$checked = 'checked="checked"';
	                    }
	                    else
	                    {
	                    	$checked = '';
	                    }

	                	echo '<label><input type="checkbox" name="'.$k.'[]" value="'.$option.'" '.$checked.' '.$cls.' /> '.$option.'</label><br />';
	                }

	                echo '</td>
					</tr>
					';
	            break;

	            /* Large text box */
	            case 'textarea':
	                $size = explode('#',$v['value']);
                    $size[0] = empty($size[0]) ? 5 : intval($size[0]);
                    $size[1] = empty($size[1]) ? 30 : intval($size[1]);

                    $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

					echo '
					<tr>
					<td style="text-align:right" width="150" valign="top">'.$v['name'].': '.$v['req'].'</td>
					<td width="80%"><textarea name="'.$k.'" rows="'.$size[0].'" cols="'.$size[1].'" '.$cls.'>'.$k_value.'</textarea></td>
					</tr>
	                ';
	            break;

	            /* Default text input */
	            default:
                	if (strlen($k_value) != 0)
                    {
                    	$v['value'] = $k_value;
                    }

                    $cls = in_array($k,$_SESSION['iserror']) ? ' class="isError" ' : '';

					echo '
					<tr>
					<td style="text-align:right" width="150">'.$v['name'].': '.$v['req'].'</td>
					<td width="80%"><input type="text" name="'.$k.'" size="40" maxlength="'.$v['maxlen'].'" value="'.$v['value'].'" '.$cls.' /></td>
					</tr>
					';
	        }
	    }
	}

	/* If table was started we need to close it */
	if ($print_table)
	{
		echo '</table> <hr />';
		$print_table = 0;
	}
	?>
	<!-- END CUSTOM AFTER -->

	<?php
	/* attachments */
	if ($hesk_settings['attachments']['use']) {

	?>
	<table border="0" width="100%">
	<tr>
	<td style="text-align:right" width="150" valign="top"><?php echo $hesklang['attachments']; ?>:</td>
	<td width="80%" valign="top">
	<?php
	for ($i=1;$i<=$hesk_settings['attachments']['max_number'];$i++)
    {
    	$cls = ($i == 1 && in_array('attachments',$_SESSION['iserror'])) ? ' class="isError" ' : '';
		echo '<input type="file" name="attachment['.$i.']" size="50" '.$cls.' /><br />';
	}
	?>
	<?php echo$hesklang['accepted_types']; ?>: <?php echo '*'.implode(', *', $hesk_settings['attachments']['allowed_types']); ?><br />
	<?php echo $hesklang['max_file_size']; ?>: <?php echo $hesk_settings['attachments']['max_size']; ?> Kb
	(<?php echo sprintf("%01.2f",($hesk_settings['attachments']['max_size']/1024)); ?> Mb)
	</td>
	</tr>
	</table>

	<?php
	}

	if ($hesk_settings['question_use'] || $hesk_settings['secimg_use'])
    {
	?>
		<hr />

        <!-- Security checks -->
		<table border="0" width="100%">
		<?php
		if ($hesk_settings['question_use'])
	    {
			?>
			<tr>
			<td style="text-align:right;vertical-align:top" width="150"><?php echo $hesklang['verify_q']; ?> <font class="important">*</font></td>
			<td width="80%">
            <?php
        	$value = '';
        	if (isset($_SESSION['c_question']))
            {
	        	$value = stripslashes(hesk_input($_SESSION['c_question']));
            }
            $cls = in_array('question',$_SESSION['iserror']) ? ' class="isError" ' : '';
		    echo $hesk_settings['question_ask'].'<br /><input type="text" name="question" size="20" value="'.$value.'" '.$cls.'  />';
            ?><br />&nbsp;
	        </td>
			</tr>
            <?php
		}

		if ($hesk_settings['secimg_use'])
	    {
			?>
			<tr>
			<td style="text-align:right;vertical-align:top" width="150"><?php echo $hesklang['verify_i']; ?> <font class="important">*</font></td>
			<td width="80%">
            <?php

            if (isset($_SESSION['img_verified']))
            {
				echo '<img src="'.HESK_PATH.'img/success.png" width="16" height="16" border="0" alt="" style="vertical-align:text-bottom" /> '.$hesklang['vrfy'];
            }
            else
            {
	            $cls = in_array('mysecnum',$_SESSION['iserror']) ? ' class="isError" ' : '';

	            echo $hesklang['sec_enter'].'<br />&nbsp;<br /><img src="print_sec_img.php?'.rand(10000,99999).'" width="150" height="40" alt="'.$hesklang['sec_img'].'" title="'.$hesklang['sec_img'].'" border="1" name="secimg" style="vertical-align:text-bottom" /> '.
	            '<a href="javascript:void(0)" onclick="javascript:document.form1.secimg.src=\'print_sec_img.php?\'+ ( Math.floor((90000)*Math.random()) + 10000);"><img src="img/reload.png" height="24" width="24" alt="'.$hesklang['reload'].'" title="'.$hesklang['reload'].'" border="0" style="vertical-align:text-bottom" /></a>'.
	            '<br />&nbsp;<br /><input type="text" name="mysecnum" size="20" maxlength="5" '.$cls.' />';
            }
            ?>
	        </td>
			</tr>
            <?php
		}
		?>
		</table>

    <?php
    }
	?>

	<!-- Submit -->
    <?php
    if ($hesk_settings['submit_notice'])
    {
	    ?>

	    <hr />

		<div align="center">
		<table border="0">
		<tr>
		<td>

	    <b><?php echo $hesklang['before_submit']; ?></b>
	    <ul>
	    <li><?php echo $hesklang['all_info_in']; ?>.</li>
		<li><?php echo $hesklang['all_error_free']; ?>.</li>
	    </ul>


		<b><?php echo $hesklang['we_have']; ?>:</b>
	    <ul>
	    <li><?php echo htmlspecialchars($_SERVER['REMOTE_ADDR']).' '.$hesklang['recorded_ip']; ?></li>
		<li><?php echo $hesklang['recorded_time']; ?></li>
		</ul>

		<p align="center"><input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
	    <input type="submit" value="<?php echo $hesklang['sub_ticket']; ?>" class="orangebutton"  onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>

	    </td>
		</tr>
		</table>
		</div>
	    <?php
    } // End IF submit_notice
    else
    {
	    ?>
        &nbsp;<br />&nbsp;<br />
		<table border="0" width="100%">
		<tr>
		<td style="text-align:right" width="150">&nbsp;</td>
		<td width="80%"><input type="hidden" name="token" value="<?php hesk_token_echo(); ?>" />
	    <input type="submit" value="<?php echo $hesklang['sub_ticket']; ?>" class="orangebutton"  onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /><br />
	    &nbsp;<br />&nbsp;</td>
		</tr>
		</table>
	    <?php
    } // End ELSE submit_notice
    ?>

	</form>

    <!-- END FORM -->
	</td>
	<td class="roundcornersright">&nbsp;</td>
</tr>
<tr>
	<td><img src="img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornersbottom"></td>
	<td width="7" height="7"><img src="img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
</tr>
</table>

<?php

hesk_cleanSessionVars('iserror');
hesk_cleanSessionVars('isnotice');

} // End print_add_ticket()


function print_start() {
global $hesk_settings, $hesklang;

/* Print header */
require_once(HESK_PATH . 'inc/header.inc.php');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td width="3"><img src="img/headerleftsm.jpg" width="3" height="25" alt="" /></td>
<td class="headersm"><?php hesk_showTopBar($hesk_settings['hesk_title']); ?></td>
<td width="3"><img src="img/headerrightsm.jpg" width="3" height="25" alt="" /></td>
</tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="3">
<tr>
<td><span class="smaller"><a href="<?php echo $hesk_settings['site_url']; ?>" class="smaller"><?php echo $hesk_settings['site_title']; ?></a> &gt;
<?php echo $hesk_settings['hesk_title']; ?></span></td>
<?php
if ($hesk_settings['kb_enable'] && $hesk_settings['kb_search'] == 1)
{
	echo '
	<td style="text-align:right" valign="top" width="300">
    <div style="display: inline;">
        <form action="knowledgebase.php" method="get" style="display: inline; margin: 0;">
		<input type="text" name="search" size="20" />
		<input type="submit" value="'.$hesklang['search'].'" class="greenbutton" onmouseover="hesk_btn(this,\'greenbuttonover\');" onmouseout="hesk_btn(this,\'greenbutton\');" />
		</form>
	</div>
	</td>
    ';
}
?>
</tr>
</table>

</td>
</tr>
<tr>
<td>

<?php
if ($hesk_settings['kb_enable'] && $hesk_settings['kb_search'] == 2)
{
		?>
		<br />
		<div style="text-align:center">
			<form action="knowledgebase.php" method="get" style="display: inline; margin: 0;" name="searchform">
			<span class="largebold"><?php echo $hesklang['ask']; ?></span> <input type="text" name="search" size="60" class="large" />
			<input type="submit" value="<?php echo $hesklang['shlp']; ?>" class="greenbutton" onmouseover="hesk_btn(this,'greenbuttonover');" onmouseout="hesk_btn(this,'greenbutton');" style="font-size:14px;height: 22px;" /><br />
			</form>
		</div>
		<br />

		<!-- START KNOWLEDGEBASE SUGGEST -->
			<div id="kb_suggestions" style="display:none">
			<img src="img/loading.gif" width="24" height="24" alt="" border="0" style="vertical-align:text-bottom" /> <i>Loading knowledgebase suggestions...</i>
			</div>

			<script language="Javascript" type="text/javascript"><!--
			hesk_suggestKBsearch();
			//-->
			</script>
		<!-- END KNOWLEDGEBASE SUGGEST -->

		<br />

	    <?php
}
?>

<table border="0" width="100%" cellspacing="0" cellpadding="0">
<tr>
<td width="50%">
<!-- START SUBMIT -->
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="7" height="7"><img src="img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornerstop"></td>
		<td><img src="img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
	</tr>
	<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>
	    <table width="100%" border="0" cellspacing="0" cellpadding="0">
	    <tr>
	    	<td width="1"><img src="img/newticket.png" alt="" width="60" height="60" /></td>
	        <td>
	        <p><b><a href="index.php?a=add"><?php echo $hesklang['sub_support']; ?></a></b><br />
            <?php echo $hesklang['open_ticket']; ?></p>
	        </td>
	    </tr>
	    </table>
		</td>
		<td class="roundcornersright">&nbsp;</td>
	</tr>
	<tr>
		<td><img src="img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornersbottom"></td>
		<td width="7" height="7"><img src="img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
	</tr>
	</table>
<!-- END SUBMIT -->
</td>
<td width="1"><img src="img/blank.gif" width="5" height="1" alt="" /></td>
<td width="50%">
<!-- START VIEW -->
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="7" height="7"><img src="img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornerstop"></td>
		<td><img src="img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
	</tr>
	<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>
	    <table width="100%" border="0" cellspacing="0" cellpadding="0">
	    <tr>
	    	<td width="1"><img src="img/existingticket.png" alt="" width="60" height="60" /></td>
	        <td>
	        <p><b><a href="ticket.php"><?php echo $hesklang['view_existing']; ?></a></b><br />
            <?php echo $hesklang['vet']; ?></p>
	        </td>
	    </tr>
	    </table>
		</td>
		<td class="roundcornersright">&nbsp;</td>
	</tr>
	<tr>
		<td><img src="img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornersbottom"></td>
		<td width="7" height="7"><img src="img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
	</tr>
	</table>
<!-- END VIEW -->
</td>
</tr>
</table>

<?php
if ($hesk_settings['kb_enable'])
{
	require(HESK_PATH . 'inc/database.inc.php');
    hesk_dbConnect();
?>
	<br />

	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="7" height="7"><img src="img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornerstop"></td>
		<td><img src="img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
	</tr>
	<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>

        <p><span class="homepageh3"><?php echo $hesklang['kb_text']; ?></span></p>

    <?php
	if ($hesk_settings['kb_index_popart'])
	{
		?>
        <table border="0" width="100%">
        <tr>
        <td>&raquo; <i><?php echo $hesklang['popart']; ?></i></td>
        <td style="text-align:right"><i><?php echo $hesklang['views']; ?></i></td>
        </tr>
        </table>
		<?php
		$sql = 'SELECT `t1`.* FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` AS `t1`
        		LEFT JOIN `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` AS `t2` ON `t1`.`catid` = `t2`.`id`
                WHERE `t1`.`type`=\'0\' AND `t2`.`type`=\'0\'
                ORDER BY `t1`.`views` DESC, `t1`.`art_order` ASC LIMIT '.hesk_dbEscape($hesk_settings['kb_index_popart']);
		$res = hesk_dbQuery($sql);
		if (hesk_dbNumRows($res) == 0)
		{
			echo '<p><i>'.$hesklang['noa'].'</i></p>';
		}
	    else
	    {
			echo '<div align="center"><table border="0" cellspacing="1" cellpadding="3" width="100%">';
			while ($article = hesk_dbFetchAssoc($res))
			{
				echo '
				<tr>
				<td>
	                <table border="0" width="100%" cellspacing="0" cellpadding="0">
	                <tr>
	                <td width="1" valign="top"><img src="img/article_text.png" width="16" height="16" border="0" alt="" style="vertical-align:middle" /></td>
	                <td valign="top">&nbsp;<a href="knowledgebase.php?article='.$article['id'].'">'.$article['subject'].'</a></td>
                    <td valign="top" style="text-align:right" width="200">'.$article['views'].'</td>
                    </tr>
	                </table>
	            </td>
				</tr>';
			}
		    echo '</table></div>';
	    }
	}


	if ($hesk_settings['kb_index_latest'])
	{
		?>
		&nbsp;
        <table border="0" width="100%">
        <tr>
        <td>&raquo; <i><?php echo $hesklang['latart']; ?></i></td>
        <td style="text-align:right"><i><?php echo $hesklang['dta']; ?></i></td>
        </tr>
        </table>
		<?php
		$sql = 'SELECT `t1`.* FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_articles` AS `t1`
        		LEFT JOIN `'.hesk_dbEscape($hesk_settings['db_pfix']).'kb_categories` AS `t2` ON `t1`.`catid` = `t2`.`id`
                WHERE `t1`.`type`=\'0\' AND `t2`.`type`=\'0\'
                ORDER BY `t1`.`dt` DESC LIMIT '.hesk_dbEscape($hesk_settings['kb_index_latest']);
		$res = hesk_dbQuery($sql);
		if (hesk_dbNumRows($res) == 0)
		{
			echo '<p><i>'.$hesklang['noa'].'</i></p>';
		}
	    else
	    {
			echo '<div align="center"><table border="0" cellspacing="1" cellpadding="3" width="100%">';
			while ($article = hesk_dbFetchAssoc($res))
			{
				echo '
				<tr>
				<td>
	                <table border="0" width="100%" cellspacing="0" cellpadding="0">
	                <tr>
	                <td width="1" valign="top"><img src="img/article_text.png" width="16" height="16" border="0" alt="" style="vertical-align:middle" /></td>
	                <td valign="top">&nbsp;<a href="knowledgebase.php?article='.$article['id'].'">'.$article['subject'].'</a></td>
                    <td valign="top" style="text-align:right" width="200">'.hesk_date($article['dt']).'</td>
                    </tr>
	                </table>
	            </td>
				</tr>';
			}
		    echo '</table></div>';
	    }
	}

    ?>

        <p>&raquo; <b><a href="knowledgebase.php"><?php echo $hesklang['viewkb']; ?></a></b></p>

		</td>
		<td class="roundcornersright">&nbsp;</td>
	</tr>
	<tr>
		<td><img src="img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornersbottom"></td>
		<td width="7" height="7"><img src="img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
	</tr>
	</table>

    <br />
	<?php
	}

	/* Show a link to admin panel? */
    if ($hesk_settings['alink'])
    {
	    ?>
	    <p style="text-align:center"><a href="admin/" target="_blank" class="smaller"><?php echo $hesklang['ap']; ?></a></p>
	    <?php
    }

} // End print_start()


function forgot_tid() {
global $hesk_settings, $hesklang;

require(HESK_PATH . 'inc/email_functions.inc.php');

$email = isset($_POST['email']) ? hesk_validateEmail($_POST['email'],'ERR',0) : '';

if (empty($email))
{
	hesk_process_messages($hesklang['enter_valid_email'],'ticket.php?remind=1&e='.$email);
	exit();
}

/* Prepare ticket statuses */
$my_status = array(
    0 => $hesklang['open'],
    1 => $hesklang['wait_staff_reply'],
    2 => $hesklang['wait_cust_reply'],
    3 => $hesklang['closed'],
    4 => $hesklang['in_progress'],
    5 => $hesklang['on_hold'],
);

/* Get ticket(s) from database */
require(HESK_PATH . 'inc/database.inc.php');
hesk_dbConnect();

if ($hesk_settings['multi_eml'])
{
	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'tickets` WHERE `email` LIKE \''.hesk_dbEscape($email).'\' OR `email` LIKE \'%,'.hesk_dbEscape($email).'\' OR `email` LIKE \''.hesk_dbEscape($email).',%\' OR `email` LIKE \'%,'.hesk_dbEscape($email).',%\'';
}
else
{
	$sql = 'SELECT * FROM `'.hesk_dbEscape($hesk_settings['db_pfix']).'tickets` WHERE `email` LIKE \''.hesk_dbEscape($email).'\'';
}

$result = hesk_dbQuery($sql);
$num=hesk_dbNumRows($result);
if ($num < 1)
{
    #hesk_error($hesklang['tid_not_found']);
	hesk_process_messages($hesklang['tid_not_found'],'ticket.php?remind=1&e='.$email);
}

$tid_list='';
$name='';
while ($my_ticket=hesk_dbFetchAssoc($result))
{
$name = $name ? $name : $my_ticket['name'];
$tid_list .= "
$hesklang[trackID]: $my_ticket[trackid]
$hesklang[subject]: ".html_entity_decode($my_ticket['subject'])."
$hesklang[status]: ".$my_status[$my_ticket['status']]."
$hesk_settings[hesk_url]/ticket.php?track=$my_ticket[trackid]
";
}

/* Get e-mail message for customer */
$msg = hesk_getEmailMessage('forgot_ticket_id','',0,0,1);
$msg = str_replace('%%NAME%%',$name,$msg);
$msg = str_replace('%%NUM%%',$num,$msg);
$msg = str_replace('%%LIST_TICKETS%%',$tid_list,$msg);
$msg = str_replace('%%SITE_TITLE%%',$hesk_settings['site_title'],$msg);
$msg = str_replace('%%SITE_URL%%',$hesk_settings['site_url'],$msg);

/* Send e-mail */
hesk_mail($email,$hesklang['tid_email_subject'],$msg);

/* Show success message */
$tmp  = '<b>'.$hesklang['tid_sent'].'!</b>';
$tmp .= '<br />&nbsp;<br />'.$hesklang['tid_sent2'].'.';
$tmp .= '<br />&nbsp;<br />'.$hesklang['check_spambox'];
hesk_process_messages($tmp,'ticket.php?e='.$email,'SUCCESS');
exit();

/* Print header */
$hesk_settings['tmp_title'] = $hesk_settings['hesk_title'] . ' - ' . $hesklang['tid_sent'];
require_once(HESK_PATH . 'inc/header.inc.php');
?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td width="3"><img src="img/headerleftsm.jpg" width="3" height="25" alt="" /></td>
<td class="headersm"><?php hesk_showTopBar($hesklang['tid_sent']); ?></td>
<td width="3"><img src="img/headerrightsm.jpg" width="3" height="25" alt="" /></td>
</tr>
</table>

<table width="100%" border="0" cellspacing="0" cellpadding="3">
<tr>
<td><span class="smaller"><a href="<?php echo $hesk_settings['site_url']; ?>" class="smaller"><?php echo $hesk_settings['site_title']; ?></a> &gt;
<a href="<?php echo $hesk_settings['hesk_url']; ?>" class="smaller"><?php echo $hesk_settings['hesk_title']; ?></a>
&gt; <?php echo $hesklang['tid_sent']; ?></span></td>
</tr>
</table>

</td>
</tr>
<tr>
<td>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td width="7" height="7"><img src="img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornerstop"></td>
	<td><img src="img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
</tr>
<tr>
	<td class="roundcornersleft">&nbsp;</td>
	<td>

	<p>&nbsp;</p>
	<p align="center"><?php echo $hesklang['tid_sent2']; ?></p>
	<p align="center"><b><?php echo $hesklang['check_spambox']; ?></b></p>
	<p>&nbsp;</p>
	<p align="center"><a href="<?php echo $hesk_settings['hesk_url']; ?>"><?php echo $hesk_settings['hesk_title']; ?></a></p>
	<p>&nbsp;</p>

	</td>
	<td class="roundcornersright">&nbsp;</td>
</tr>
<tr>
	<td><img src="img/roundcornerslb.jpg" width="7" height="7" alt="" /></td>
	<td class="roundcornersbottom"></td>
	<td width="7" height="7"><img src="img/roundcornersrb.jpg" width="7" height="7" alt="" /></td>
</tr>
</table>

<?php
} // End forgot_tid()

?>
