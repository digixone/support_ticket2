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
define('INSTALL',1);
define('HESK_NEW_VERSION','2.3');
define('HESK_PATH','../');

/* Get all the required files and functions */
require(HESK_PATH . 'hesk_settings.inc.php');

define('HIDE_ONLINE',1);

/* Debugging should be enabled in installation mode */
$hesk_settings['debug_mode'] = 1;
error_reporting(E_ALL);

$hesk_settings['language']='English';
$hesk_settings['languages']=array('English' => array('folder'=>'en'));
require(HESK_PATH . 'inc/common.inc.php');
require(HESK_PATH . 'inc/database.inc.php');
hesk_session_start();

/* Convert old database settings */
if (isset($hesk_settings['database_user']))
{
	$hesk_settings['db_user'] = $hesk_settings['database_user'];
    $hesk_settings['db_name'] = $hesk_settings['database_name'];
    $hesk_settings['db_pass'] = $hesk_settings['database_pass'];
    $hesk_settings['db_host'] = $hesk_settings['database_host'];
}

/* Set the table prefix to default for versions older than 2.0 */
if ( ! isset($hesk_settings['db_pfix']))
{
	$hesk_settings['db_pfix'] = 'hesk_';
}

/* Don't trust settings, we will find the real version we are upgrading from using MySQL tables setup */
$hesk_settings['update_from']='';

/* Check for license agreement */
if (empty($_SESSION['license_agree']))
{
    $agree = !empty($_GET['agree']) ? hesk_input($_GET['agree']) : '';
    if ($agree == 'YES')
    {
        $_SESSION['license_agree']=1;
        $_SESSION['step']=1;
    }
    else
    {
        $_SESSION['step']=0;
    }
}

if (!isset($_SESSION['step']))
{
    $_SESSION['step']=0;
}

/* Test database connection */
if (isset($_POST['dbtest']))
{
    $db_success = 1;
    $hesk_settings['db_host']=hesk_input($_POST['host']);
    $hesk_settings['db_name']=hesk_input($_POST['name']);
    $hesk_settings['db_user']=hesk_input($_POST['user']);
    $hesk_settings['db_pass']=hesk_input($_POST['pass']);
    //$hesk_settings['db_pfix']=hesk_input($_POST['pfix']);

    /* Connect to database */
    $hesk_db_link = @mysql_connect($hesk_settings['db_host'],$hesk_settings['db_user'], $hesk_settings['db_pass']) or $db_success=0;

    /* Select database works ok? */
    if ($db_success == 1 && !mysql_select_db($hesk_settings['db_name'], $hesk_db_link))
    {
        $db_success=2;
    }

    if ($db_success == 2)
    {
        hesk_iDatabase(2);
        exit();
    }
    elseif ($db_success == 1)
    {

        /* Get a list of all HESK MySQL tables */
        $tables = array();
        $sql = 'SHOW TABLES FROM `'.hesk_dbEscape($hesk_settings['db_name']).'`';
        $res = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
        while ($row=mysql_fetch_array($res, MYSQL_NUM))
        {
        	$tables[] = $row[0];
        }

		/* Version 2.3 tables installed? */
		if (in_array($hesk_settings['db_pfix'].'online', $tables) || in_array($hesk_settings['db_pfix'].'logins', $tables))
		{
        	$hesk_settings['update_from']='2.3';
			$_SESSION['step']=0;
			$_SESSION['license_agree']=0;
			hesk_iFinish(1);
		}

		/* Version 2.2 tables installed? */
		if (in_array($hesk_settings['db_pfix'].'mail', $tables))
		{
        	$hesk_settings['update_from']='2.2';
		}

		/* Version 2.1 tables installed? */
		elseif (in_array($hesk_settings['db_pfix'].'kb_attachments', $tables))
		{
        	$hesk_settings['update_from']='2.1';
		}

		/* Version 2.0 tables installed? */
		elseif (in_array($hesk_settings['db_pfix'].'kb_articles', $tables))
		{
			$hesk_settings['update_from']='2.0';
		}

		/* Version 0.94.1 tables installed? */
		elseif (in_array('hesk_attachments', $tables))
		{
			$hesk_settings['update_from']='0.94.1';
		}
		/* Version 0.94 tables installed? */
		elseif (in_array('hesk_std_replies', $tables))
		{
			$hesk_settings['update_from']='0.94';
		}
        /* It's a version older than 0.94 or no tables found */
        else
        {
            /* If we don't have four tables this is not a valid HESK install */
            if (count($tables) != 4)
            {
	            $_SESSION['step']=0;
	            $_SESSION['license_agree']=0;
	            hesk_iFinish(2);
            }

            /* Version 0.90 didn't have the notify column in users table */
            $sql2 = "SELECT * FROM `hesk_users` WHERE `id`=1 LIMIT 1";
            $res2 = hesk_dbQuery($sql2) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
            $row2 = mysql_fetch_array($res2);
            if (isset($row2['notify']))
            {
            	$hesk_settings['update_from'] = '0.91-0.93.1';
            }
            else
            {
            	$hesk_settings['update_from'] = '0.90';
            }
        }

        //die("UPDATING FROM: ".$hesk_settings['update_from']);

        /* All ok, save settings update database tables */
        hesk_iSaveSettings();
        hesk_iTables();

        /* Close database conenction and move to the next step */
        mysql_close($hesk_db_link);
        $_SESSION['step']=3;
    }
    else
    {
        hesk_iDatabase(1);
        exit();
    }

}


switch ($_SESSION['step'])
{
	case 1:
	   hesk_iCheckSetup();
	   break;
	case 2:
	   hesk_iDatabase();
	   break;
	case 3:
	   hesk_iFinish();
	   break;
	default:
	   hesk_iStart();
}

function hesk_iFinish($problem=0) {
    global $hesk_settings;
    hesk_iHeader();
?>

<table border="0" width="100%">
<tr>
<td>UPDATE STEPS:<br />
<font color="#008000">1. License agreement</font> -&gt; <font color="#008000">2. Check setup</font> -&gt; <font color="#008000">3. Database settings</font> -&gt; <b>4. Update database tables</b></td>
</tr>
</table>

	<br />

    <div align="center">
	<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornerstop"></td>
		<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
	</tr>
	<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>

<h3>Update database tables</h3>

<table>
<tr>
<td>-&gt; Testing database connection...</td>
<td><font color="#008000"><b>SUCCESS</b></td>
</tr>
<tr>
<td>-&gt; Installing database tables...</td>

<?php
if ($problem==1) {
?>

    <td><font color="#FF0000"><b>ERROR: hesk_kb_attachments table exists</b></td>
    </tr>
    </table>

    <p style="color:#FF0000;">Database table <i>hesk_kb_attachments</i> exists on
    the server, your Hesk had already been updated to version <?php echo HESK_NEW_VERSION; ?>!</p>
    <p align="center"><a href="index.php">Click here to continue</a></p>

<?php
} elseif ($problem==2) {
?>

    <td><font color="#FF0000"><b>ERROR: Old tables not found</b></td>
    </tr>
    </table>

    <p style="color:#FF0000;">Old Hesk database tables not found, unable to update. If you are trying
    to install a new copy of Hesk please run the installation program again and select
    <b>New install</b> from the installation page!</p></p>
    <p align="center"><a href="index.php">Click here to continue</a></p>

<?php
} else {
?>

    <td><font color="#008000"><b>SUCCESS</b></font></td>
    </tr>
    </table>

    <p>Congratulations, you have successfully completed Hesk <?php echo HESK_NEW_VERSION; ?> database update!</p>

    <p style="color:#FF0000"><b>Next steps:</b></p>

    <ol>
    <li><font color="#FF0000"><b>IMPORTANT:</b></font> Before doing anything else <b>delete</b> the <b>install</b> folder from your server!
    You can leave this browser window open.<br />&nbsp;</li>
    <li>Setup your help desk from the Administration panel.<br /><br />

		<form action="<?php echo HESK_PATH; ?>admin/index.php" method="post">
        <input type="hidden" name="a" value="do_login" />
        <input type="hidden" name="remember_user" value="JUSTUSER" />
        <input type="hidden" name="user" value="Administrator" />
        <input type="hidden" name="pass" value="admin" />
		<input type="hidden" name="goto" value="admin_settings.php" />
		<input type="submit" value="Click here to login automatically" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>
		</form>

    </li>
    </ol>

    <p>&nbsp;</p>

    <p align="center">For further instructions please see the readme.htm file!</p>

<?php
} // End else
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

<?php
    hesk_iFooter();
    exit();
} // End hesk_iFinish()


function hesk_iTables() {
	global $hesk_settings;

    $update_all_next = 0;

	/* Updating version 0.90 to 0.91 */
	if ($hesk_settings['update_from'] == '0.90')
	{
		$sql = "ALTER TABLE `hesk_users` ADD `notify` CHAR( 1 ) DEFAULT '1' NOT NULL";
		hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

        $update_all_next = 1;
	} // END version 0.90 to 0.91


	/* Updating versions 0.91 through 0.93.1 to 0.94  */
	if ($update_all_next || $hesk_settings['update_from'] == '0.91-0.93.1')
	{
		$sql="CREATE TABLE `hesk_attachments` (
		  `att_id` mediumint(8) unsigned NOT NULL auto_increment,
		  `ticket_id` varchar(10) NOT NULL default '',
		  `saved_name` varchar(255) NOT NULL default '',
		  `real_name` varchar(255) NOT NULL default '',
		  `size` int(10) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`att_id`),
		  KEY `ticket_id` (`ticket_id`)
		) ENGINE=MyISAM";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		$sql="CREATE TABLE `hesk_std_replies` (
		`id` smallint(5) unsigned NOT NULL auto_increment,
		`title` varchar(70) NOT NULL default '',
		`message` text NOT NULL,
		`reply_order` smallint(5) unsigned NOT NULL default '0',
		PRIMARY KEY  (`id`)
		) TYPE=MyISAM";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		$sql="ALTER TABLE `hesk_categories`
		CHANGE `name` `name` varchar(60) NOT NULL default '',
		ADD `cat_order` smallint(5) unsigned NOT NULL default '0'";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		$sql="ALTER TABLE `hesk_replies`
		CHANGE `name` `name` varchar(50) NOT NULL default '',
		ADD `attachments` TEXT";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		$sql="ALTER TABLE `hesk_tickets`
		CHANGE `name` `name` varchar(50) NOT NULL default '',
		CHANGE `category` `category` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '1',
		CHANGE `priority` `priority` enum('1','2','3') NOT NULL default '3',
		CHANGE `subject` `subject` varchar(70) NOT NULL default '',
		ADD `lastchange` datetime NOT NULL default '0000-00-00 00:00:00' AFTER `dt`,
		CHANGE `status` `status` enum('0','1','2','3') default '1',
		ADD `lastreplier` enum('0','1') NOT NULL default '0',
		ADD `archive` enum('0','1') NOT NULL default '0',
		ADD `attachments` text,
		ADD `custom1` VARCHAR( 255 ) NOT NULL default '',
		ADD `custom2` VARCHAR( 255 ) NOT NULL default '',
		ADD `custom3` VARCHAR( 255 ) NOT NULL default '',
		ADD `custom4` VARCHAR( 255 ) NOT NULL default '',
		ADD `custom5` VARCHAR( 255 ) NOT NULL default '',
		ADD INDEX `archive` ( `archive` )";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		/* Change status of closed tickets to the new "Resolved" status */
		$sql="UPDATE `hesk_tickets` SET `status`='3' WHERE `status`='0'";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		/* Populate lastchange */
		$sql="UPDATE `hesk_tickets` SET `lastchange`=`dt`";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		/* Update categories with order values */
		$sql = "SELECT `id` FROM `hesk_categories`";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
		$i = 10;
		while ($mycat=hesk_dbFetchAssoc($result))
		{
			$sql = "UPDATE `hesk_categories` SET `cat_order`=$i WHERE `id`=$mycat[id] LIMIT 1";
			hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
			$i += 10;
		}

        $update_all_next = 1;
	} // END versions 0.91 through 0.93.1 to 0.94


    /* Updating version 0.94 to 0.94.1 */
    if ($hesk_settings['update_from'] == '0.94')
    {
		$sql="CREATE TABLE `hesk_attachments` (
		  `att_id` mediumint(8) unsigned NOT NULL auto_increment,
		  `ticket_id` varchar(10) NOT NULL default '',
		  `saved_name` varchar(255) NOT NULL default '',
		  `real_name` varchar(255) NOT NULL default '',
		  `size` int(10) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`att_id`),
		  KEY `ticket_id` (`ticket_id`)
		) ENGINE=MyISAM";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		if ($hesk_settings['attachments']['use'])
		{
			/* Update attachments for tickets */
			$sql = "SELECT * FROM `hesk_tickets` WHERE `attachments` != ''";
			$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
			while ($ticket = hesk_dbFetchAssoc($result))
			{
				$att=explode('#####',substr($ticket['attachments'], 0, -5));
				$myattachments = '';
				foreach ($att as $myatt)
				{
					$name = substr(strstr($myatt, $ticket['trackid']),16);
					$saved_name = strstr($myatt, $ticket['trackid']);
					$size = filesize($hesk_settings['server_path'].'/attachments/'.$saved_name);

					$sql2 = "INSERT INTO `hesk_attachments` (`ticket_id`,`saved_name`,`real_name`,`size`) VALUES ('".hesk_dbEscape($ticket['trackid'])."', '".hesk_dbEscape($saved_name)."', '".hesk_dbEscape($name)."', '".hesk_dbEscape($size)."')";
					$result2 = hesk_dbQuery($sql2) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
					$myattachments .= hesk_dbInsertID() . '#' . $name .',';
				}

				$sql2 = "UPDATE `hesk_tickets` SET `attachments` = '".hesk_dbEscape($myattachments)."' WHERE `id` = ".hesk_dbEscape($ticket['id'])." LIMIT 1";
				$result2 = hesk_dbQuery($sql2) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
			}

			/* Update attachments for replies */
			$sql = "SELECT * FROM `hesk_replies` WHERE `attachments` != ''";
			$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
			while ($ticket = hesk_dbFetchAssoc($result))
			{
				$sql2 = "SELECT `trackid` FROM `hesk_tickets` WHERE `id` = '".hesk_dbEscape($ticket['replyto'])."' LIMIT 1";
				$result2 = hesk_dbQuery($sql2) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
				$trackingID = hesk_dbResult($result2,0,0);

				$att=explode('#####',substr($ticket['attachments'], 0, -5));
				$myattachments = '';
				foreach ($att as $myatt)
                {
					$name = substr(strstr($myatt, $trackingID),16);
					$saved_name = strstr($myatt, $trackingID);
					$size = filesize($hesk_settings['server_path'].'/attachments/'.$saved_name);

					$sql2 = "INSERT INTO `hesk_attachments` (`ticket_id`,`saved_name`,`real_name`,`size`) VALUES ('".hesk_dbEscape($trackingID)."', '".hesk_dbEscape($saved_name)."', '".hesk_dbEscape($name)."', '".hesk_dbEscape($size)."')";
					$result2 = hesk_dbQuery($sql2) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
					$myattachments .= hesk_dbInsertID() . '#' . $name .',';
				}

				$sql2 = "UPDATE `hesk_replies` SET `attachments` = '".hesk_dbEscape($myattachments)."' WHERE `id` = ".hesk_dbEscape($ticket['id'])." LIMIT 1";
				$result2 = hesk_dbQuery($sql2);
			}
		}  // END if attachments use

        $update_all_next = 1;
    } // END version 0.94 to 0.94.1


	/* Updating version 0.94.1 to 2.0 */
	if ($update_all_next || $hesk_settings['update_from'] == '0.94.1')
	{
		$sql = "CREATE TABLE `hesk_kb_articles` (
		  `id` smallint(5) unsigned NOT NULL auto_increment,
		  `catid` smallint(5) unsigned NOT NULL default '0',
		  `dt` timestamp NOT NULL default CURRENT_TIMESTAMP,
		  `author` smallint(5) unsigned NOT NULL default '0',
		  `subject` varchar(255) NOT NULL default '',
		  `content` text NOT NULL,
		  `rating` float NOT NULL default '0',
		  `votes` mediumint(8) unsigned NOT NULL default '0',
		  `views` mediumint(8) unsigned NOT NULL default '0',
		  `type` enum('0','1','2') NOT NULL default '0',
		  `html` enum('0','1') NOT NULL default '0',
		  `art_order` smallint(5) unsigned NOT NULL default '0',
		  `history` text NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `catid` (`catid`),
		  KEY `type` (`type`),
		  FULLTEXT KEY `subject` (`subject`,`content`)
		) ENGINE=MyISAM";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());


		$sql = "CREATE TABLE `hesk_kb_categories` (
		  `id` smallint(5) unsigned NOT NULL auto_increment,
		  `name` varchar(255) NOT NULL default '',
		  `parent` smallint(5) unsigned NOT NULL default '0',
		  `articles` smallint(5) unsigned NOT NULL default '0',
		  `cat_order` smallint(5) unsigned NOT NULL default '0',
		  `type` enum('0','1') NOT NULL default '0',
		  PRIMARY KEY  (`id`),
		  KEY `type` (`type`)
		) ENGINE=MyISAM";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		$sql="INSERT INTO `hesk_kb_categories` VALUES (1, 'Knowledgebase', 0, 0, 10, '0')";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		$sql = "CREATE TABLE `hesk_notes` (
		  `id` mediumint(8) unsigned NOT NULL auto_increment,
		  `ticket` mediumint(8) unsigned NOT NULL default '0',
		  `who` smallint(5) unsigned NOT NULL default '0',
		  `dt` datetime NOT NULL default '0000-00-00 00:00:00',
		  `message` text NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `ticketid` (`ticket`)
		) ENGINE=MyISAM";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

	    $sql = array();
		$sql[] = "ALTER TABLE `hesk_replies` ADD `staffid` SMALLINT UNSIGNED NOT NULL DEFAULT '0'";
		$sql[] = "ALTER TABLE `hesk_replies` ADD `rating` ENUM( '1', '5' ) default NULL";

		$sql[] = "ALTER TABLE `hesk_tickets` ADD INDEX `categories` ( `category` )";
		$sql[] = "ALTER TABLE `hesk_tickets` ADD INDEX `statuses` ( `status` ) ";
		$sql[] = "ALTER TABLE `hesk_tickets` CHANGE `custom1` `custom1` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` CHANGE `custom2` `custom2` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` CHANGE `custom3` `custom3` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` CHANGE `custom4` `custom4` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` CHANGE `custom5` `custom5` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` ADD `custom6` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` ADD `custom7` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` ADD `custom8` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` ADD `custom9` text NOT NULL";
		$sql[] = "ALTER TABLE `hesk_tickets` ADD `custom10` text NOT NULL";

		$sql[] = "ALTER TABLE `hesk_users` CHANGE `pass` `pass` CHAR( 40 ) NOT NULL";
		$sql[] = "ALTER TABLE `hesk_users` CHANGE `isadmin` `isadmin` ENUM( '0', '1' ) NOT NULL DEFAULT '0'";
		$sql[] = "ALTER TABLE `hesk_users` CHANGE `notify` `notify` ENUM( '0', '1' ) NOT NULL DEFAULT '1'";
		$sql[] = "ALTER TABLE `hesk_users` ADD `heskprivileges` VARCHAR( 255 ) NOT NULL";
		$sql[] = "ALTER TABLE `hesk_users` ADD `ratingneg` mediumint(8) unsigned NOT NULL default '0'";
		$sql[] = "ALTER TABLE `hesk_users` ADD `ratingpos` mediumint(8) unsigned NOT NULL default '0'";
		$sql[] = "ALTER TABLE `hesk_users` ADD `rating` float NOT NULL default '0'";
		$sql[] = "ALTER TABLE `hesk_users` ADD `replies` mediumint(8) unsigned NOT NULL default '0'";

		$sql[] = "ALTER TABLE `hesk_std_replies` CHANGE `title` `title` VARCHAR( 100 ) NOT NULL";

		foreach ($sql as $s)
	    {
			$result = hesk_dbQuery($s) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
	    }

	    /* Update passwords to the new type and hesk privileges for non-admins */
		$sql    = 'SELECT `id`,`pass`,`isadmin` FROM `hesk_users` ORDER BY `id` ASC';
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

	    $sql = array();
		while ($row=hesk_dbFetchAssoc($result))
		{
			$new_pass = hesk_Pass2Hash($row['pass']);
	        $s = "UPDATE `hesk_users` SET `pass`='".hesk_dbEscape($new_pass)."' ";
	        if ($row['isadmin'] == 0)
	        {
	        	$s .= ", `heskprivileges`='can_view_tickets,can_reply_tickets,can_change_cat,' ";
	        }
	        $s.= "WHERE `id`=".hesk_dbEscape($row['id']);
	        $sql[] = $s;
		}

		foreach ($sql as $s)
	    {
			$result = hesk_dbQuery($s) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
	    }

        $update_all_next = 1;
    } // END version 0.94.1 to 2.0


	/* Updating version 2.0 to 2.1 */
	if ($update_all_next || $hesk_settings['update_from'] == '2.0')
	{
		$sql = "CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` (
		  `att_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
		  `saved_name` varchar(255) NOT NULL DEFAULT '',
		  `real_name` varchar(255) NOT NULL DEFAULT '',
		  `size` int(10) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`att_id`)
		) ENGINE=MyISAM";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		$sql = array();
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` ADD `attachments` TEXT NOT NULL";

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom11` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom12` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom13` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom14` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom15` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom16` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom17` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom18` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom19` text NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `custom20` text NOT NULL";

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `afterreply` ENUM( '0', '1', '2' ) NOT NULL DEFAULT '0' AFTER `categories`";

		foreach ($sql as $s)
	    {
			$result = hesk_dbQuery($s) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
	    }

        $update_all_next = 1;
	} // END version 2.0 to 2.1


	/* Updating version 2.1 to 2.2 */
	if ($update_all_next || $hesk_settings['update_from'] == '2.1')
	{
		$sql="
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."mail` (
		  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		  `from` smallint(5) unsigned NOT NULL,
		  `to` smallint(5) unsigned NOT NULL,
		  `subject` varchar(255) NOT NULL,
		  `message` text NOT NULL,
		  `dt` datetime NOT NULL,
		  `read` enum('0','1') NOT NULL DEFAULT '0',
		  `deletedby` smallint(5) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`id`),
		  KEY `recipients` (`from`,`to`)
		) ENGINE=MyISAM
		";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		$sql = array();

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `replierid` SMALLINT UNSIGNED NULL AFTER `lastreplier`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `owner` SMALLINT UNSIGNED NOT NULL DEFAULT '0' AFTER `status`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `locked` ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER `archive`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD `history` TEXT NOT NULL AFTER `attachments`";

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` CHANGE `notify` `notify_new_unassigned` ENUM( '0', '1' ) NOT NULL DEFAULT '1'";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `notify_new_my` ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `notify_new_unassigned`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `notify_reply_unassigned` ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `notify_new_my`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `notify_reply_my` ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `notify_reply_unassigned`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `notify_assigned` ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `notify_reply_my`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `notify_pm` ENUM( '0', '1' ) NOT NULL DEFAULT '1' AFTER `notify_assigned`";

        $sql[] = "UPDATE  `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `categories` = TRIM(TRAILING ',' FROM `categories`)";
        $sql[] = "UPDATE  `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `heskprivileges` = TRIM(TRAILING ',' FROM `heskprivileges`)";

		foreach ($sql as $s)
	    {
			$result = hesk_dbQuery($s) or die("MySQL error!<br />Query: $s<br /><br />Error info: " . mysql_error());
	    }

		/* Update privileges - anyone can assign ticket to himself/herself by default */
		$sql = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `heskprivileges`=CONCAT(`heskprivileges`,',can_assign_self') WHERE `isadmin`!='1' ";
        $res = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

        $update_all_next = 1;
	} // END version 2.1 to 2.2


	/* Updating version 2.2 to 2.3 */
	if ($update_all_next || $hesk_settings['update_from'] == '2.2')
	{
    	/* Logins table */
		$sql="
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."logins` (
		  `ip` varchar(46) NOT NULL,
		  `number` tinyint(3) unsigned NOT NULL DEFAULT '1',
		  `last_attempt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  UNIQUE KEY `ip` (`ip`)
		) ENGINE=MyISAM
		";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

        /* Online table */
		$sql="
		CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."online` (
		  `user_id` smallint(5) unsigned NOT NULL,
		  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  `tmp` int(11) unsigned NOT NULL DEFAULT '0',
		  UNIQUE KEY `user_id` (`user_id`),
		  KEY `dt` (`dt`)
		) ENGINE=MyISAM
		";
		$result = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

		$sql = array();

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `trackid` `trackid` VARCHAR( 13 ) NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `priority` `priority` ENUM( '0', '1', '2', '3' ) NOT NULL DEFAULT '3'";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `status` `status` ENUM('0','1','2','3','4','5') NOT NULL DEFAULT '0'";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `ip` `ip` VARCHAR( 46 ) NOT NULL DEFAULT ''";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `lastchange` `lastchange` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` CHANGE `email` `email` VARCHAR(255) NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` ADD INDEX (`owner`) ";

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` CHANGE `heskprivileges` `heskprivileges` TEXT NOT NULL";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `default_list` VARCHAR( 255) NOT NULL DEFAULT '' AFTER `notify_pm`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD `autoassign` ENUM('0','1') NOT NULL DEFAULT '1' AFTER `notify_pm`";
    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` ADD INDEX (`autoassign`) ";

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` CHANGE `ticket_id` `ticket_id` VARCHAR(13) NOT NULL DEFAULT ''";

    	$sql[] = "ALTER TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` CHANGE `replyto` `replyto` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0'";

		foreach ($sql as $s)
	    {
			$result = hesk_dbQuery($s) or die("MySQL error!<br />Query: $s<br /><br />Error info: " . mysql_error());
	    }

		/* Update knowledgebase category article count because of a bug prior to 2.3 */
        $sql = "SELECT COUNT(*) as `cnt`, `catid` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` WHERE `type`='0' GROUP BY `catid` ";
        $res = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());
        if (hesk_dbNumRows($res))
        {
        	$kb_count = array();
        	while ($tmp = hesk_dbFetchAssoc($res))
            {
            	$kb_count[$tmp['catid']] = $tmp['cnt'];
            }

            $sql = "SELECT `id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` ";
            $res = hesk_dbQuery($sql) or die("MySQL error!<br />Query: $sql<br /><br />Error info: " . mysql_error());

        	while ($cat = hesk_dbFetchAssoc($res))
            {
            	$tmp = isset($kb_count[$cat['id']]) ? $kb_count[$cat['id']] : 0;
            	$sql2 = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` SET `articles` = ".hesk_dbEscape($tmp)." WHERE `id`=".hesk_dbEscape($cat['id'])." LIMIT 1";
                $res2 = hesk_dbQuery($sql2) or die("MySQL error!<br />Query: $sql2<br /><br />Error info: " . mysql_error());
            }
        }

        /* Update staff with new permissions (allowed by default) */
		$sql = "SELECT `id`,`heskprivileges` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."users` WHERE `isadmin` != '1' ";
		$res = hesk_dbQuery($sql);
		while ($row=hesk_dbFetchAssoc($res))
		{
			/* Not admin, is user allowed to view tickets? */
			if (strpos($row['heskprivileges'], 'can_view_tickets') !== false)
			{
				$sql2 = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `heskprivileges`=CONCAT(`heskprivileges`,',can_view_unassigned,can_view_online') WHERE `id`=".hesk_dbEscape($row['id'])." LIMIT 1";
				$res2 = hesk_dbQuery($sql2) or die("MySQL error!<br />Query: $sql2<br /><br />Error info: " . mysql_error());
			}
            else
            {
				$sql2 = "UPDATE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` SET `heskprivileges`=CONCAT(`heskprivileges`,',can_view_online') WHERE `id`=".hesk_dbEscape($row['id'])." LIMIT 1";
				$res2 = hesk_dbQuery($sql2) or die("MySQL error!<br />Query: $sql2<br /><br />Error info: " . mysql_error());
            }
		}

        $update_all_next = 1;
	} // END version 2.2 to 2.3


	return true;
} // End hesk_iTables()


function hesk_compareVariable($k,$v) {
	global $hesk_settings;

    if (is_array($v))
    {
    	foreach ($v as $sub_k => $sub_v)
        {
			$v[$k] = hesk_compareVariable($sub_k,$sub_v);
        }
    }

	if (isset($hesk_settings[$k]))
    {
    	return $hesk_settings[$k];
    }
    else
    {
    	return $v;
    }
}


function hesk_iSaveSettings() {
    global $hesk_settings;

    /* Get default settings */
	$hesk_default = hesk_defaultSettings();

    /* Set a new version number */
    $hesk_settings['hesk_version'] = HESK_NEW_VERSION;


    foreach ($hesk_default as $k => $v)
    {
    	if (is_array($v))
        {
        	/* Arrays will be processed separately */
        	continue;
        }
    	if ( ! isset($hesk_settings[$k]))
        {
			$hesk_settings[$k] = $v;
        }
    }

	/* Arrays need special care */
    $hesk_settings['attachments'] = isset($hesk_settings['attachments']) ? $hesk_settings['attachments'] : $hesk_default['attachments'];

	/* Custom fields */
	for ($i=1;$i<=20;$i++)
	{
		$this_field = 'custom' . $i;

        if (isset($hesk_settings['custom_fields'][$this_field]) && $hesk_settings['custom_fields'][$this_field]['use'])
        {
			if ( ! isset($hesk_settings['custom_fields'][$this_field]['place']))
			{
				$hesk_settings['custom_fields'][$this_field]['place'] = 0;
				$hesk_settings['custom_fields'][$this_field]['type']  = 'text';
		        $hesk_settings['custom_fields'][$this_field]['value'] = '';
			}

            $hesk_settings['custom_fields'][$this_field]['name'] = addslashes($hesk_settings['custom_fields'][$this_field]['name']);
            $hesk_settings['custom_fields'][$this_field]['value'] = addslashes($hesk_settings['custom_fields'][$this_field]['value']);

        }
        else
        {
        	$hesk_settings['custom_fields'][$this_field] = $hesk_default['custom_fields'][$this_field];
        }
	}

    /* Encode and escape characters */
    $set = $hesk_settings;
    foreach ($hesk_settings as $k=> $v)
    {
    	if (is_array($v))
        {
        	continue;
        }
    	$set[$k] = addslashes($v);
    }
    $set['debug_mode'] = 0;

$settings_file_content='<?php
/* Settings file for HESK '.$hesk_settings['hesk_version'].' */

/*** GENERAL ***/

/* --> General settings */
$hesk_settings[\'site_title\']=\'' . $set['site_title'] . '\';
$hesk_settings[\'site_url\']=\'' . $set['site_url'] . '\';
$hesk_settings[\'support_mail\']=\'' . $set['support_mail'] . '\';
$hesk_settings[\'webmaster_mail\']=\'' . $set['webmaster_mail'] . '\';
$hesk_settings[\'noreply_mail\']=\'' . $set['noreply_mail'] . '\';

/* --> Language settings */
$hesk_settings[\'can_sel_lang\']=' . $set['can_sel_lang'] . ';
$hesk_settings[\'language\']=\'English\';
$hesk_settings[\'languages\']=array(
\'English\' => array(\'folder\'=>\'en\'),
);

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
?' . '>';

$fp=fopen('../hesk_settings.inc.php','w') or hesk_error($hesklang['err_openset'] . '(w)');
fputs($fp,$settings_file_content);
fclose($fp);

return true;
} // End hesk_iSaveSettings()


function hesk_defaultSettings() {

    $path = substr($_SERVER["SCRIPT_FILENAME"],0,-11);
	$path = rtrim($path,'\/');
    $path = substr($path,0,-7);
    $path = rtrim($path,'\/');

	$spam_question = hesk_generate_SPAM_question();

	$secimg_sum = '';
	for ($i=1;$i<=10;$i++)
	{
	    $secimg_sum .= substr('AEUYBDGHJLMNPQRSTVWXZ123456789', rand(0,29), 1);
	}

 	/*** GENERAL ***/

	/* --> General settings */
	$hesk_settings['site_title']='My Web site';
	$hesk_settings['site_url']='http://www.domain.com';
	$hesk_settings['support_mail']='support@domain.com';
	$hesk_settings['webmaster_mail']='support@domain.com';
	$hesk_settings['noreply_mail']='noreply@domain.com';

	/* --> Language settings */
	$hesk_settings['can_sel_lang']=0;
	$hesk_settings['language']='English';
	$hesk_settings['languages']=array(
	'English' => array('folder'=>'en'),
	);

	/* --> Database settings */
	$hesk_settings['db_host']='localhost';
	$hesk_settings['db_name']='hesk';
	$hesk_settings['db_user']='test';
	$hesk_settings['db_pass']='test';
	$hesk_settings['db_pfix']='hesk_';


	/*** HELP DESK ***/

	/* --> Help desk settings */
	$hesk_settings['hesk_title']='Help Desk';
	$hesk_settings['hesk_url']='http://www.domain.com/helpdesk';
	$hesk_settings['server_path']=$path;
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
	$hesk_settings['secimg_sum']=$secimg_sum;
	$hesk_settings['question_use']=0;
	$hesk_settings['question_ask']=$spam_question[0];
	$hesk_settings['question_ans']=$spam_question[1];
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

    return $hesk_settings;
}


function hesk_iDatabase($problem=0) {
    global $hesk_settings, $hesk_db_link;
    hesk_iHeader();
?>

<table border="0" width="100%">
<tr>
<td>UPDATE STEPS:<br />
<font color="#008000">1. License agreement</font> -&gt; <font color="#008000">2. Check setup</font> -&gt; <b>3. Database settings</b> -&gt; 4. Update database tables</td>
</tr>
</table>

	<br />

    <div align="center">
	<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornerstop"></td>
		<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
	</tr>
	<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>

<h3>Database settings</h3>

<div align="center">
<table border="0" width="750" cellspacing="1" cellpadding="5" class="white">
<tr>
<td>
<p><b>Hesk will not work unless the information below is correct and database connection
test is successful. For correct database information contact your hosting company,
I cannot help you find this information!</b></p>

<?php
if ($problem==1)
{
    echo '<p style="color:#FF0000;"><b>Database connection failed!</b><br />Double-check all the information below. If not sure contact your hosting company for the correct information!<br /><br />MySQL said: '.mysql_error().'</p>';
}
elseif ($problem==2)
{
    echo '<p style="color:#FF0000;"><b>Database connection failed!</b><br />Double-check <b>database name</b> and make sure the user has access to the database. If not sure contact your hosting company for the correct information!<br /><br />MySQL said: '.mysql_error().'</p>';
}
?>

<form action="update.php" method="post">
<table>
<tr>
<td>Database Host:</td>
<td><input type="text" name="host" value="<?php echo $hesk_settings['db_host']; ?>" size="40" /></td>
</tr>
<tr>
<td>Database Name:</td>
<td><input type="text" name="name" value="<?php echo $hesk_settings['db_name']; ?>" size="40" /></td>
</tr>
<tr>
<td>Database User (login):</td>
<td><input type="text" name="user" value="<?php echo $hesk_settings['db_user']; ?>" size="40" /></td>
</tr>
<tr>
<td>User Password:</td>
<td><input type="text" name="pass" value="<?php echo $hesk_settings['db_pass']; ?>" size="40" /></td>
</tr>
</table>

<p align="center"><input type="hidden" name="dbtest" value="1" /><input type="submit" value="Continue to Step 4" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>
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
	    </div>

<?php
    hesk_iFooter();
} // End hesk_iDatabase()


function hesk_iCheckSetup() {
    global $hesk_settings;
    hesk_iHeader();
    $_SESSION['all_passed']=1;
    $correct_this=array();
?>

<table border="0" width="100%">
<tr>
<td>UPDATE STEPS:<br />
<font color="#008000">1. License agreement</font> -&gt; <b>2. Check setup</b> -&gt; 3. Database settings -&gt; 4. Update database tables</td>
</tr>
</table>

<br />

    <div align="center">
	<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornerstop"></td>
		<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
	</tr>
	<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>

<h3>Check setup</h3>

<p>Checking wether your server meets all requirements and that files are setup correctly</p>

<div align="center">
<table border="0" width="750" cellspacing="1" cellpadding="3" class="white">
<tr>
<th class="admin_white"><b>Required</b></th>
<th class="admin_white"><b>Your setting</b></th>
<th class="admin_white"><b>Status</b></th>
</tr>

<tr>
<td class="admin_gray"><b>PHP version</b><br />Should be at least PHP 4 >= 4.3.2</td>
<td class="admin_gray" valign="middle" nowrap="nowrap"><b><?php echo PHP_VERSION; ?></b></td>
<td class="admin_gray" valign="middle">
<?php
if (function_exists('version_compare') && version_compare(PHP_VERSION,'4.3.2','>='))
{
    echo '<font color="#008000"><b>Passed</b></font>';
}
else
{
    $_SESSION['all_passed']=0;
    echo '<font color="#FF0000"><b>Failed</b></font>';
    $correct_this[]='You are using an old and non-secure version of PHP, ask your host to update your PHP version!';
}
?>
</td>
</tr>

<tr>
<td class="admin_white"><b>hesk_settings.inc.php file</b><br />Must be uploaded and writable by the script</td>
<td class="admin_white" valign="middle" nowrap="nowrap">
<?php
$mypassed=1;
if (file_exists('../hesk_settings.inc.php'))
{
    echo '<b><font color="#008000">Exists</font>, ';
    if (is__writable('../hesk_settings.inc.php'))
    {
        echo '<font color="#008000">Writable</font></b>';
    }
    else
    {
        echo '<font color="#FF0000">Not writable</font></b>';
        $mypassed=2;
    }
}
else
{
    $mypassed=0;
    echo '<b><font color="#FF0000">Not uploaded</font>, <font color="#FF0000">Not writable</font></b>';
}
?>
</td>
<td class="admin_white" valign="middle">
<?php
if ($mypassed==1)
{
    echo '<font color="#008000"><b>Passed</b></font>';
}
elseif ($mypassed==2)
{
    $_SESSION['all_passed']=0;
    echo '<font color="#FF0000"><b>Failed</b></font>';
    $correct_this[]='Make sure the <b>hesk_settings.inc.php</b> file is writable: on Linux chmod it to 666 or rw-rw-rw-, on Windows (IIS) make sure IUSR account has modify/read/write permissions';
}
else
{
    $_SESSION['all_passed']=0;
    echo '<font color="#FF0000"><b>Failed</b></font>';
    $correct_this[]='Upload the <b>hesk_settings.inc.php</b> file to the server and make sure it\'s writable!';
}
?>
</td>
</tr>

<tr>
<td class="admin_gray"><b>attachments directory</b><br />Must exist and be writable by the script</td>
<td class="admin_gray" valign="middle" nowrap="nowrap">
<?php
$mypassed=1;

if (!file_exists('../attachments'))
{
    @mkdir('../attachments', 0777);
}

if (is_dir('../attachments')) {
    echo '<b><font color="#008000">Exists</font>, ';
    if (is__writable('../attachments/'))
    {
        echo '<font color="#008000">Writable</font></b>';
    }
    else
    {
        echo '<font color="#FF0000">Not writable</font></b>';
        $mypassed=2;
    }
}
else
{
    $mypassed=0;
    echo '<b><font color="#FF0000">Not uploaded</font>, <font color="#FF0000">Not writable</font></b>';
}
?>
</td>
<td class="admin_gray" valign="middle">
<?php
if ($mypassed==1)
{
    echo '<font color="#008000"><b>Passed</b></font>';
} elseif ($mypassed==2)
{
    $_SESSION['all_passed']=0;
    echo '<font color="#FF0000"><b>Failed</b></font>';
    $correct_this[]='Make sure the <b>attachments</b> directory is writable: on Linux chmod it to 777 or rwxrwxrwx, on Windows (IIS) make sure IUSR account has modify/read/write permissions';
}
else
{
    $_SESSION['all_passed']=0;
    echo '<font color="#FF0000"><b>Failed</b></font>';
    $correct_this[]='Within hesk folder create a new one called <b>attachments</b> and make sure it is writable: on Linux chmod it to 777 or rwxrwxrwx, on Windows (IIS) make sure IUSR account has modify/read/write permissions';
}
?>
</td>
</tr>

<tr>
<td class="admin_white"><b>File uploads</b><br />To use file attachments <i>file_uploads</i> must be enabled in PHP</td>
<td class="admin_white" valign="middle">
<?php
$mypassed=1;
$can_use_attachments=1;
if (ini_get('file_uploads'))
{
    echo '<b><font color="#008000">Enabled</font></b>';
}
else
{
    $mypassed=0;
    $can_use_attachments=0;
    echo '<b><font color="#FFA500">Disabled</font></b>';
}
?>
</td>
<td class="admin_white" valign="middle">
<?php
if ($mypassed==1)
{
    echo '<font color="#008000"><b>Passed</b></font>';
}
else
{
    echo '<font color="#FFA500"><b>Unavailable*</b></font>';
}
?>
</td>
</tr>

<tr>
<td class="admin_gray"><b>ZLib Support</b><br />PHP must be compiled with ZLib support</td>
<td class="admin_gray" valign="middle" nowrap="nowrap">
<?php
$mypassed=1;
if (function_exists('gzdeflate'))
{
    echo '<b><font color="#008000">Enabled</font></b>';
}
else
{
    $mypassed=0;
    $_SESSION['all_passed']=0;
    echo '<font color="#FF0000"><b>Disabled</b></font>';
    $correct_this[]='PHP needs to be compiled with ZLib support enabled (--with-zlib[=DIR]) in order for Hesk to work correctly. Contact your host and ask them to enable ZLib support for PHP.';
}
?>
</td>
<td class="admin_gray" valign="middle">
<?php
if ($mypassed==1)
{
    echo '<font color="#008000"><b>Passed</b></font>';
}
else
{
    echo '<font color="#FF0000"><b>Failed</b></font>';
}
?>
</td>
</tr>

<tr>
<td class="admin_white"><b>GD Library</b><br />Check if GD library is enabled</td>
<td class="admin_white" valign="middle" nowrap="nowrap">
<?php
$mypassed=1;
if (extension_loaded('gd') && function_exists('gd_info'))
{
    echo '<b><font color="#008000">Enabled</font></b>';
    $can_use_gd=1;
}
else
{
    $mypassed=0;
    $can_use_gd=0;
    echo '<font color="#FF0000"><b>Disabled</b></font>';
}
?>
</td>
<td class="admin_white" valign="middle">
<?php
if ($mypassed==1)
{
    echo '<font color="#008000"><b>Passed</b></font>';
}
else
{
    echo '<font color="#ff9900"><b>Not available</b></font>';
}
?>
</td>
</tr>

<tr>
<td class="admin_gray"><b>Old files deleted</b><br />Old files that aren't used anymore must be removed</td>
<td class="admin_gray" valign="middle" nowrap="nowrap"><b>Checking...</b></td>
<td class="admin_gray" valign="middle">
<?php
$old_files = array(

    /* pre-0.93 *.inc files */
    'hesk_settings.inc','hesk.sql','inc/common.inc','inc/database.inc','inc/footer.inc','inc/header.inc',
    'inc/print_tickets.inc','inc/show_admin_nav.inc','inc/show_search_form.inc','install.php','update.php',

	/* pre-2.0 files */
	'admin.php','admin_change_status.php','admin_main.php','admin_move_category','admin_reply_ticket.php',
    'admin_settings.php','admin_settings_save.php','admin_ticket.php','archive.php',
    'delete_tickets.php','find_tickets.php','manage_canned.php','manage_categories.php',
    'manage_users.php','profile.php','show_tickets.php',

	/* pre-2.1 files */
	'emails/','language/english.php',

    /* pre-2.3 files */
    'secimg.inc.php','hesk_style.css',

    );
sort($old_files);
$still_exist = array();

foreach ($old_files as $f)
{
	if (file_exists('../'.$f))
    {
    	$still_exist[] = $f;
    }
}

if (count($still_exist) == 0)
{
    echo '<font color="#008000"><b>Passed</b></font>';
}
else
{
    $_SESSION['all_passed']=0;
    echo '<font color="#FF0000"><b>Failed</b></font>';
    $correct_this[]='For security reasons delete these old files and folders from your
    main HESK folder:<br />&nbsp;<br /><ul><li><b>'.implode('</b></li><li><b>',$still_exist).'</b></li></ul>';
}
?>
</td>
</tr>

</table>
</div>

<?php
if ($can_use_attachments==0)
{
    echo '<p><font color="#FFA500"><b>*</b></font> Hesk will still work if all other tests are successful, but File attachments won\'t work.</p>';
    $_SESSION['set_attachments']=0;
}
else
{
	$_SESSION['set_attachments']=1;
}

if ($can_use_gd==0)
{
    echo '<p><font color="#FFA500"><b>*</b></font> Hesk will work without GD library but you will not be able to use the anti-SPAM image (captcha)</p>';
    $_SESSION['set_captcha']=0;
}
else
{
	$_SESSION['set_captcha']=1;
}
?>

<p>&nbsp;</p>

<?php
if (!empty($correct_this))
{
	?>
	<div align="center">
	<table border="0" width="750" cellspacing="1" cellpadding="3">
	<tr>
	<td>
	<p><font color="#FF0000"><b>You will not be able to continue installation until the required tests are passed. Things you need to correct before continuing installation:</b></font></p>
	<ol>
	<?php
	foreach($correct_this as $mythis)
	{
	    echo "<li>$mythis<br />&nbsp;</li>";
	}
	?>
	</ol>
	<form method="post" action="update.php">
	<p align="center">&nbsp;<br /><input type="submit" value="Test again" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>
	</form>
	</td>
	</tr>
	</table>
	</div>
	<?php
}
else
{
	$_SESSION['step']=2;
	?>
	<form method="POST" action="update.php">
	<div align="center">
	<table border="0">
	<tr>
	<td>
	<p align="center"><font color="#008000"><b>All required tests passed, you may now continue to database setup</b></font></p>
	<p align="center"><input type="submit" value="Continue to Step 3" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /></p>
	</td>
	</tr>
	</table>
	</div>
	</form>

	<?php
}
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
<?php
    hesk_iFooter();
} // End hesk_iCheckSetup()



function hesk_iStart() {
    global $hesk_settings;
    hesk_iHeader();
?>

<table border="0" width="100%">
<tr>
<td>UPDATE STEPS:<br />
<b>1. License agreement</b> -&gt; 2. Check setup -&gt; 3. Database settings -&gt; 4. Update database tables</td>
</tr>
</table>

<br />

    <div align="center">
	<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="7" height="7"><img src="../img/roundcornerslt.jpg" width="7" height="7" alt="" /></td>
		<td class="roundcornerstop"></td>
		<td><img src="../img/roundcornersrt.jpg" width="7" height="7" alt="" /></td>
	</tr>
	<tr>
		<td class="roundcornersleft">&nbsp;</td>
		<td>

<h3>License agreement</h3>

<p><b>Summary:</b></p>

<ul>
<li>The script is provided &quot;as is&quot;, without any warranty. Use at your own risk.<br />&nbsp;</li>
<li>HESK is a registered trademark, except in some special cases using the term HESK requires permission.<br />&nbsp;</li>
<li>You are not allowed to redistribute this script or any software based on this script over the Internet or in any other medium without express written permission<br />&nbsp;</li>
<li>Using this code, in part or full, to create new scripts or products is expressly forbidden.<br />&nbsp;</li>
<li>You mustn't edit or remove any &quot;Powered by&quot; links without purchasing a <a href="https://www.hesk.com/buy.php" target="_blank">License</a> to do so</li>
</ul>

<p><b>The entire License agreement:</b></p>

<p align="center"><textarea rows="15" cols="70">
LICENSE AGREEMENT

The &quot;script&quot; is all files included with the HESK distribution archive as well as all files produced as a result of the installation scripts. Klemen Stirn (&quot;Author&quot;,&quot;HESK&quot;) is the author and copyrights owner of the script. The &quot;Licensee&quot; (&quot;you&quot;) is the person downloading or using the Licensed version of script. &quot;User&quot; is any person using or viewing the script with their HTML browser.

&quot;Powered by&quot; link is herein defined as an anchor link pointing to HESK website and/or script webpage, usually located at the bottom of the script and visible to users of the script without looking into source code.

&quot;Copyright headers&quot; is a written copyright notice located in script source code and normally not visible to users.

This License may be modified by the Author at any time. The new version of the License becomes valid when published on HESK website. You are encouraged to regularly check back for License updates.

THIS SCRIPT IS PROVIDED &quot;AS IS&quot; AND ANY EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL KLEMEN STIRN BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SCRIPT, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

Using this code, in part or full, to create derivate work, new scripts or products is expressly forbidden. Obtain permission before redistributing this software over the Internet or in any other medium.

REMOVING POWERED BY LINKS
You are not allowed to remove or in any way edit the &quot;Powered by&quot; links in this script without purchasing a License. You can purchase a License at
https://www.hesk.com/buy.php

If you remove the Powered by links without purchasing a License and paying the licensee fee, you are in a direct violation of European Union and International copyright laws. Your Licence to use the scripts is immediately terminated and you must delete all copies of the entire program from your web server. Klemen Stirn may, at any time, terminate this License agreement if Klemen Stirn determines, that this License agreement has been breached.

Under no circumstance is the removal of copyright headers from the script source code permitted.

TRADEMARK POLICY

HESK is a US registered trademark of Klemen Stirn. Certain usages of the Trademark are fine and no specific permission from the author is needed:

- there is no commercial intent behind the use

- what you are referring to is in fact HESK. If someone is confused into thinking that what isn't HESK is in fact HESK, you are probably doing something wrong

- there is no suggestion (through words or appearance) that your project is approved, sponsored, or affiliated with HESK or its related projects unless it actually has been approved by and is accountable to the author

Permission from the author is necessary to use the HESK trademark under any circumstances other than those specifically permitted above. These include:

- any commercial use

- use on or in relation to a software product that includes or is built on top of a product supplied by author, if there is any commercial intent associated with that product

- use in a domain name or URL

- use for merchandising purposes, e.g. on t-shirts and the like

- use of a name which includes the letters HESK in relation to computer hardware or software.

- services relating to any of the above

If you wish to have permission for any of the uses above or for any other use which is not specifically referred to in this policy, please contact me and I'll let you know as soon as possible if your proposed use is permissible. Note that due to the volume of mail I receive, it may take some time to process your request. Permission may only be granted subject to certain conditions and these may include the requirement that you enter into an agreement with me to maintain the quality of the product and/or service which you intend to supply at a prescribed level.

While there may be exceptions, it is very unlikely that I will approve Trademark use in the following cases:

- use of a Trademark in a company name

- use of a Trademark in a domain name which has a commercial intent. The commercial intent can range from promotion of a company or product, to collecting revenue generated by advertising

- the calling of any software or product by the name HESK (or another related Trademark), unless that software or product is a substantially unmodified HESK product

- use in combination with any other marks or logos. This include use of a Trademark in a manner that creates a "combined mark," or use that integrates other wording with the Trademark in a way that the public may think of the use as a new mark (for example Club HESK or HESKBooks, or in a way that by use of special fonts or presentation with nearby words or images conveys an impression that the two are tied in some way)

- use in combination with any product or service which is presented as being Certified or Official or formally associated with me or my products or services

- use in a way which implies an endorsement where that doesn't exist, or which attempts to unfairly or confusingly capitalise on the goodwill or brand of the project

- use of a Trademark in a manner that disparages HESK and is not clearly third-party parody

- on or in relation to a software product which constitutes a substantially modified version of a product supplied by HESK.com, that is to say with material changes to the code, or services relating to such a product

- in a title or metatag of a web page whose sole intention or result is to influence search engine rankings or result listings, rather than for discussion, development or advocacy of the Trademarks

OTHER

This License Agreement is governed by the laws of Slovenia, European Union. Both the Licensee and Klemen Stirn submit to the jurisdiction of the courts of Slovenia, European Union. Both the Licensee and Klemen Stirn agree to commence any litigation that may arise hereunder in the courts located in Slovenia.

If any provision hereof shall be held illegal, invalid or unenforceable, in whole or in part, such provision shall be modified to the minimum extent necessary to make it legal, valid and enforceable, and the legality, validity and enforceability of all other provisions of this Agreement shall not be affected thereby. No delay or failure by either party to exercise or enforce at any time any right or provision hereof shall be considered a waiver thereof or of such party's right thereafter to exercise or enforce each and every right and provision of this Agreement.
</textarea></p>

<hr />

<form method="get" action="update.php" name="license" onsubmit="return hesk_checkAgree()">
<div align="center">
<table border="0">
<tr>
<td>

<p><b>Do you agree to the License agreement and all the terms incorporated therein?</b> <font color="#FF0000"><i>(required)</i></font></b></p>

<p align="center">
<input type="hidden" name="agree" value="YES" />
<input type="submit" value="YES, I AGREE (Click to continue)" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
&nbsp;
<input type="button" onclick="javascript:self.location='index.php'" value="NO, I DO NOT AGREE (Cancel setup)" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
</p>

</td>
</tr>
</table>
</div>
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
    </div>
<?php
    hesk_iFooter();
} // End hesk_iStart()


function hesk_iHeader() {
    global $hesk_settings;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Update HESK to version <?php echo HESK_NEW_VERSION; ?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1" />
	<link href="../hesk_style_v23.css" type="text/css" rel="stylesheet" />
	<script language="Javascript" type="text/javascript" src="../hesk_javascript.js"></script>
    </head>
<body>


<div align="center">
<table border="0" cellspacing="0" cellpadding="5" class="enclosing">
<tr>
<td>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td width="3"><img src="../img/headerleftsm.jpg" width="3" height="25" alt="" /></td>
	<td class="headersm">HESK <?php echo HESK_NEW_VERSION; ?> update script</td>
	<td width="3"><img src="../img/headerrightsm.jpg" width="3" height="25" alt="" /></td>
	</tr>
	</table>

	</td>
	</tr>
	<tr>
	<td>
<?php
} // End hesk_iHeader()


function hesk_iFooter() {
    global $hesk_settings;
?>
	<p style="text-align:center"><span class="smaller">Powered by <a href="http://www.hesk.com" class="smaller" target="_blank">Help Desk Software</a> HESK<sup>TM</sup></span></p></td></tr></table></div></body></html>
<?php
} // End hesk_iFooter()


/*
This function is from http://www.php.net/is_writable
and is a work-around for IIS bug which returns files as
writable by PHP when in fact they are not.
*/
function is__writable($path) {
//will work in despite of Windows ACLs bug
//NOTE: use a trailing slash for folders!!!
//see http://bugs.php.net/bug.php?id=27609
//see http://bugs.php.net/bug.php?id=30931

    if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
        return is__writable($path.uniqid(mt_rand()).'.tmp');
    else if (is_dir($path))
        return is__writable($path.'/'.uniqid(mt_rand()).'.tmp');
    // check tmp file for read/write capabilities
    $rm = file_exists($path);
    $f = @fopen($path, 'a');
    if ($f===false)
        return false;
    fclose($f);
    if (!$rm)
        unlink($path);
    return true;
}
?>
