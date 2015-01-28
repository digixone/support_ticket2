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
    $hesk_settings['db_pfix']=hesk_input($_POST['pfix']);

    /* Connect to database */
    $hesk_db_link = @mysql_connect($hesk_settings['db_host'],$hesk_settings['db_user'], $hesk_settings['db_pass']) or $db_success=0;

    /* Select database works ok? */
    if ($db_success == 1 && !mysql_select_db($hesk_settings['db_name'], $hesk_db_link))
    {
    	/* Try to create the database */
		if (function_exists('mysql_create_db') && mysql_create_db($hesk_settings['db_name'], $hesk_db_link))
        {
        	if (mysql_select_db($hesk_settings['db_name'], $hesk_db_link))
            {
				$db_success = 1;
            }
            else
            {
				$db_success = 2;
            }
        }
        else
        {
        	$db_success = 2;
        }
    }

    if ($db_success == 2)
    {
        hesk_iDatabase(2);
        exit();
    }
    elseif ($db_success == 1)
    {
        /* Check if these MySQL tables already exist, stop if they do */
        $tables_exist=0;
        $sql='SHOW TABLES FROM `'.$hesk_settings['db_name'].'`';
        $result = hesk_dbQuery($sql);

		$hesk_tables = array(
			$hesk_settings['db_pfix'].'attachments',
			$hesk_settings['db_pfix'].'categories',
			$hesk_settings['db_pfix'].'kb_articles',
            $hesk_settings['db_pfix'].'kb_attachments',
			$hesk_settings['db_pfix'].'kb_categories',
			$hesk_settings['db_pfix'].'logins',
            $hesk_settings['db_pfix'].'mail',
			$hesk_settings['db_pfix'].'notes',
            $hesk_settings['db_pfix'].'online',
			$hesk_settings['db_pfix'].'replies',
			$hesk_settings['db_pfix'].'std_replies',
			$hesk_settings['db_pfix'].'tickets',
			$hesk_settings['db_pfix'].'users',
        );

        while ($row=mysql_fetch_array($result, MYSQL_NUM))
        {
            if (in_array($row[0],$hesk_tables))
            {
                $tables_exist = 1;
                break;
            }
        }
        mysql_free_result($result);

        if ($tables_exist)
        {
            $_SESSION['step']=0;
            $_SESSION['license_agree']=0;
            hesk_iFinish(1);
        }

        /* All ok, save settings and install the tables */
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
<td>INSTALLATION STEPS:<br />
<font color="#008000">1. License agreement</font> -&gt; <font color="#008000">2. Check setup</font> -&gt; <font color="#008000">3. Database settings</font> -&gt; <b>4. Setup database tables</b></td>
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

<h3>Setup database tables</h3>

<table>
<tr>
<td>-&gt; Testing database connection...</td>
<td><font color="#008000"><b>SUCCESS</b></td>
</tr>
<tr>
<td>-&gt; Installing database tables...</td>

<?php
if ($problem==1)
{
?>

    <td><font color="#FF0000"><b>ERROR: Database tables already exist!</b></td>
    </tr>
    </table>

    <p style="color:#FF0000;">Hesk database tables with <?php echo $hesk_settings['db_pfix']; ?> prefix already exist in this database. If you are trying
    to upgrade from a previous version please run the installation program again and select
    <b>Update existing install</b> from the installation page. If you are trying to
    install a new copy of Hesk in the same database make sure you change
    table prefix to a unique one.</p>

    <p align="center"><a href="index.php">Click here to continue</a></p>

<?php
}
else
{
?>
<!-- START INSERT JAVA POP UP FOR DELETE.INSTALL.PHP FILE -->
<head>
<script language="javascript" type="text/javascript">
<!--
function popitup(url) {
	newwindow=window.open(/modules/support_ticket2/hesk/,'delete.install.php','height=200,width=350');
	if (window.focus) {newwindow.focus()}
	return false;
}

// -->
</script>
</head>
<!-- END INSERT JAVA POP UP FOR DELETE.INSTALL.PHP FILE -->
<body>
    <td><font color="#008000"><b>SUCCESS</b></font></td>
    </tr>
    </table>

    <p>Congratulations, you have successfully completed Hesk database setup!</p>

    <p style="color:#FF0000"><b>Next steps:</b><br />
	<font color="#FF0000"><b>IMPORTANT:</b></font></p>
    <ol>
    <li> Before doing anything else <b>delete</b> or <strong>rename</strong> the <b>install</b> folder from your server!     
    <li>Click the button 
      below to temporarily rename install folder to _install.<br />
      <!-- You can leave this browser window open. Click <a href="/modules/support_ticket2/hesk/delete.install.php" onclick="return popitup('/modules/support_ticket2/hesk/delete.install.php')"
	><b>here</b></a> to rename the <b>install</b> folder to <b>_install</b>.<br />&nbsp;</li> -->
    <li>Please do make sure that you write down the login information.</li>
    <li>Setup your help desk from the Administration panel. Login using the default
      username and password:<br />
      <br />
      Username: <b>Administrator</b><br />
      Password: <b>admin</b><br />
      <br />
      
      <form action="<?php echo HESK_PATH; ?>admin/index.php" method="post">
        <input type="hidden" name="a" value="do_login" />
        <input type="hidden" name="remember_user" value="JUSTUSER" />
        <input type="hidden" name="user" value="Administrator" />
        <input type="hidden" name="pass" value="admin" />
        <input type="hidden" name="goto" value="admin_settings.php" /><br />
        <input type="button" value=" Delete Install Folder " class="orangebutton" onclick="document.location.href='/modules/support_ticket2/hesk/delete.install.php';" />
        <!-- <input type="submit" value="Login Admin Panel" class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" /> --></p>
        </form>
    </li>
    </ol>

    <p>&nbsp;</p>

    <p align="center">For further instructions please see the readme.htm file!</p>
</body>
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
/* This function setups all required MySQL tables */

// -> Attachments
$sql="
CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` (
  `att_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` varchar(13) NOT NULL DEFAULT '',
  `saved_name` varchar(255) NOT NULL DEFAULT '',
  `real_name` varchar(255) NOT NULL DEFAULT '',
  `size` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`att_id`),
  KEY `ticket_id` (`ticket_id`)
) ENGINE=MyISAM
";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

// -> Categories
$sql="
CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '',
  `cat_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM
";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

$sql="INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."categories` VALUES (1, 'General', 10)";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

// -> KB Articles
$sql="
CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_articles` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `catid` smallint(5) unsigned NOT NULL,
  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `author` smallint(5) unsigned NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `rating` float NOT NULL DEFAULT '0',
  `votes` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `views` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `type` enum('0','1','2') NOT NULL DEFAULT '0',
  `html` enum('0','1') NOT NULL DEFAULT '0',
  `art_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  `history` text NOT NULL,
  `attachments` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `catid` (`catid`),
  KEY `type` (`type`),
  FULLTEXT KEY `subject` (`subject`,`content`)
) ENGINE=MyISAM
";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

// -> KB Attachments
$sql="
CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_attachments` (
  `att_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `saved_name` varchar(255) NOT NULL DEFAULT '',
  `real_name` varchar(255) NOT NULL DEFAULT '',
  `size` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`att_id`)
) ENGINE=MyISAM
";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

// -> KB Categories
$sql="
CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `parent` smallint(5) unsigned NOT NULL,
  `articles` smallint(5) unsigned NOT NULL,
  `cat_order` smallint(5) unsigned NOT NULL,
  `type` enum('0','1') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`)
) ENGINE=MyISAM
";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

$sql="INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."kb_categories` VALUES (1, 'Knowledgebase', 0, 0, 10, '0')";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

// -> Login attempts
$sql="
CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."logins` (
  `ip` varchar(46) NOT NULL,
  `number` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `last_attempt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM
";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

// -> Private messages
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
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

$sql="INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."mail` (`from`, `to`, `subject`, `message`, `dt`, `read`, `deletedby`) VALUES (9999, 1, 'Welcome to HESK!', '<div style=\"text-align:justify;padding:3px\">\r\n\r\n<p style=\"color:green;font-weight:bold\">Congratulations for installing HESK, a lightweight and easy-to-use ticket support system!</p>\r\n\r\n<p>I am sure you are eager to use your <b>HESK&trade;</b> helpdesk to improve your customer support and reduce your workload, so check the rest of this message for some quick  &quot;Getting Started&quot; tips.</p>\r\n\r\n<p>Once you have learned the power of <b>HESK&trade;</b>, please consider supporting its future enhancement by purchasing an <a href=\"https://www.hesk.com/buy.php\" target=\"_blank\">inexpensive license</a>. Having a site license will remove the &quot;Powered by Help Desk Software HESK&quot; links from the bottom of your screens to make it look even more professional.</p>\r\n\r\n<p>Enjoy using HESK&trade; - and I value receiving your constructive feedback and feature suggestions.</p>\r\n\r\n<p>Klemen Stirn,<br />\r\nHESK owner and author<br />\r\n<a href=\"http://www.hesk.com/\" target=\"_blank\">www.hesk.com</a>\r\n\r\n<p>&nbsp;</p>\r\n\r\n<p style=\"text-align:center;font-weight:bold\">*** Quick &quot;Getting Started&quot; Tips ***</p>\r\n\r\n<ul style=\"padding-left:20px;padding-right:10px\">\r\n<li>Click the profile link to set your Profile name, e-mail, signature, and *CHANGE YOUR PASSWORD*.<br />&nbsp;</li>\r\n<li>Click the settings link in the top menu to get to the Settings page. Take some time and get familiar with all the available settings. Most should be self-explanatory; for additional information about each setting, click the [?] link for help about the current setting.<br />&nbsp;</li>\r\n<li>Create new staff accounts on the Users page. The default user (Administrator) cannot be deleted, but you can change the password on the Profile page.<br />&nbsp;</li>\r\n<li>Add new categories (departments) on the Categories page. The default category cannot be deleted, but it can be renamed.<br />&nbsp;</li>\r\n<li>Use the integrated Knowledgebase - it is one of the most powerful support tools as it gives self-help resources to your customers. A comprehensive and well-written knowledgebase can drastically reduce the number of support tickets you receive and save a lot of your time in the long run. Arrange answers to frequently asked questions and articles into categories.<br />&nbsp;</li>\r\n<li>Create canned responses on the Canned Responses page. These are pre-written replies to common support questions. However, you should also contribute by adding answers to other typical questions in the Knowledgebase.<br />&nbsp;</li>\r\n<li>Subscribe to the <a href=\"http://www.hesk.com/newsletter.php\" target=\"_blank\">HESK Newsletter</a> to be notified of updates and new versions.<br />&nbsp;</li>\r\n<li><a href=\"https://www.hesk.com/buy.php\" target=\"_blank\">Buy a license</a> to remove the &quot;<span class=\"smaller\">Powered by Help Desk Software HESK</span>&quot; links from the bottom of your help desk.<br />&nbsp;</li></ul>\r\n\r\n</div>', NOW(), '0', 9999)";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

// -> Notes
$sql="
CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."notes` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `ticket` mediumint(8) unsigned NOT NULL,
  `who` smallint(5) unsigned NOT NULL,
  `dt` datetime NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ticketid` (`ticket`)
) ENGINE=MyISAM
";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

// -> Online
$sql="
CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."online` (
  `user_id` smallint(5) unsigned NOT NULL,
  `dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tmp` int(11) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `user_id` (`user_id`),
  KEY `dt` (`dt`)
) ENGINE=MyISAM
";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

// -> Replies
$sql="
CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."replies` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `replyto` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `dt` datetime DEFAULT NULL,
  `attachments` text,
  `staffid` smallint(5) unsigned NOT NULL DEFAULT '0',
  `rating` enum('1','5') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `replyto` (`replyto`)
) ENGINE=MyISAM
";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

// -> Canned Responses
$sql="
CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."std_replies` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `reply_order` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM
";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

// -> Tickets
$sql="
CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."tickets` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `trackid` varchar(13) NOT NULL,
  `name` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `category` smallint(5) unsigned NOT NULL DEFAULT '1',
  `priority` enum('0','1','2','3') NOT NULL DEFAULT '3',
  `subject` varchar(70) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `dt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastchange` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(46) NOT NULL DEFAULT '',
  `status` enum('0','1','2','3','4','5') DEFAULT '0',
  `owner` smallint(5) unsigned NOT NULL DEFAULT '0',
  `lastreplier` enum('0','1') NOT NULL DEFAULT '0',
  `replierid` smallint(5) unsigned DEFAULT NULL,
  `archive` enum('0','1') NOT NULL DEFAULT '0',
  `locked` enum('0','1') NOT NULL DEFAULT '0',
  `attachments` text,
  `history` text NOT NULL,
  `custom1` text NOT NULL,
  `custom2` text NOT NULL,
  `custom3` text NOT NULL,
  `custom4` text NOT NULL,
  `custom5` text NOT NULL,
  `custom6` text NOT NULL,
  `custom7` text NOT NULL,
  `custom8` text NOT NULL,
  `custom9` text NOT NULL,
  `custom10` text NOT NULL,
  `custom11` text NOT NULL,
  `custom12` text NOT NULL,
  `custom13` text NOT NULL,
  `custom14` text NOT NULL,
  `custom15` text NOT NULL,
  `custom16` text NOT NULL,
  `custom17` text NOT NULL,
  `custom18` text NOT NULL,
  `custom19` text NOT NULL,
  `custom20` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `trackid` (`trackid`),
  KEY `archive` (`archive`),
  KEY `categories` (`category`),
  KEY `statuses` (`status`),
  KEY `owner` (`owner`)
) ENGINE=MyISAM
";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

// -> Users
$sql="
CREATE TABLE `".hesk_dbEscape($hesk_settings['db_pfix'])."users` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `user` varchar(20) NOT NULL DEFAULT '',
  `pass` char(40) NOT NULL,
  `isadmin` enum('0','1') NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `signature` varchar(255) NOT NULL DEFAULT '',
  `categories` varchar(255) NOT NULL DEFAULT '',
  `afterreply` enum('0','1','2') NOT NULL DEFAULT '0',
  `notify_new_unassigned` enum('0','1') NOT NULL DEFAULT '1',
  `notify_new_my` enum('0','1') NOT NULL DEFAULT '1',
  `notify_reply_unassigned` enum('0','1') NOT NULL DEFAULT '1',
  `notify_reply_my` enum('0','1') NOT NULL DEFAULT '1',
  `notify_assigned` enum('0','1') NOT NULL DEFAULT '1',
  `notify_pm` enum('0','1') NOT NULL DEFAULT '1',
  `default_list` varchar(255) NOT NULL DEFAULT '',
  `autoassign` enum('0','1') NOT NULL DEFAULT '1',
  `heskprivileges` TEXT NOT NULL,
  `ratingneg` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `ratingpos` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `rating` float NOT NULL DEFAULT '0',
  `replies` mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `autoassign` (`autoassign`)
) ENGINE=MyISAM
";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

$sql="INSERT INTO `".hesk_dbEscape($hesk_settings['db_pfix'])."users` VALUES (1, 'Administrator', '499d74967b28a841c98bb4baaabaad699ff3c079', '1', 'Your name', 'you@yourwebsite.com', 'Best regards,\r\n\r\nYour name\r\nYour website\r\nhttp://www.yourwebsite.com', '', '0', '1', '1', '1', '1', '1', '1', '', '1', '', 0, 0, 0, 0)";
$result = hesk_dbQuery($sql) or hesk_error("Couldn't execute SQL: $sql. MySQL said: ".mysql_error()."<br />&nbsp;<br /> Please make sure you delete any old installations of Hesk before installing this version!");

} // End hesk_iTables()


function hesk_iSaveSettings() {
    global $hesk_settings, $hesklang;

	$spam_question = hesk_generate_SPAM_question();

    $path = substr($_SERVER["SCRIPT_FILENAME"],0,-11);
	$path = rtrim($path,'\/');
    $path = substr($path,0,-7);
    $path = rtrim($path,'\/');

    $hesk_settings['server_path'] = $path;
    $hesk_settings['secimg_use'] = empty($_SESSION['set_captcha']) ? 0 : 1;
    $hesk_settings['use_spamq'] = empty($_SESSION['use_spamq']) ? 0 : 1;
    $hesk_settings['question_ask'] = $spam_question[0];
    $hesk_settings['question_ans'] = $spam_question[1];
    $hesk_settings['set_attachments'] = empty($_SESSION['set_attachments']) ? 0 : 1;
    $hesk_settings['hesk_version'] = HESK_NEW_VERSION;

	if (isset($_SERVER['HTTP_HOST']))
    {
		$hesk_settings['site_url']='http://' . $_SERVER['HTTP_HOST'];

	    if (isset($_SERVER['REQUEST_URI']))
	    {
			$hesk_settings['hesk_url']='http://' . $_SERVER['HTTP_HOST'] . str_replace('/install/install.php','',$_SERVER['REQUEST_URI']);
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
/* Settings file for Hesk '.$hesk_settings['hesk_version'].' */

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

$fp=fopen(HESK_PATH . 'hesk_settings.inc.php','w') or hesk_error($hesklang['err_openset']);
fputs($fp,$settings_file_content);
fclose($fp);

return true;
} // End hesk_iSaveSettings()


function hesk_iDatabase($problem=0) {
    global $hesk_settings, $hesk_db_link;
    hesk_iHeader();
?>

<table border="0" width="100%">
<tr>
<td>INSTALLATION STEPS:<br />
<font color="#008000">1. License agreement</font> -&gt; <font color="#008000">2. Check setup</font> -&gt; <b>3. Database settings</b> -&gt; 4. Setup database tables</td>
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
<p><b>Hesk pre-configured setting for Zpanel Support Ticket module.
<br/>We have already setup a default database entry that will match your zadmin_hesk database information.
<br/>Please make sure that you created the Hesk database information that we gave you, you must only enter
<br/>the password for Hesk database that you created previously.
<br/>Hesk will not work unless the information below is correct and database connection test is successful. 
</b></p>

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
<!--/readonly="readonly"-->
<form action="install.php" method="post">
<table>
<tr>
<td>Database Host:</td>
<td><input type="text" name="host" readonly="readonly" value="<?php echo $hesk_settings['db_host']; ?>" size="40" /></td>
</tr>
<tr>
<td>Database Name:</td>
<td><input type="text" name="name" value="<?php echo $hesk_settings['db_name']; ?>" placeholder="<?php echo $hesk_settings['db_name']; ?>" size="40" /></td>
</tr>
<tr>
<td>Database User (login):</td>
<td><input type="text" name="user" value="<?php $hesk_settings['db_user']; ?>" placeholder="<?php echo $hesk_settings['db_user']; ?>" size="40" /></td>
</tr>
<tr>
<td>User Password:</td>
<td><input type="text" name="pass" value="<?php $hesk_settings['db_pass']; ?>" placeholder="<?php echo $hesk_settings['db_pass']; ?>" autocomplete="off" size="40" /></td>
</tr>
<tr>
<td>Table prefix:</td>
<td><input type="text" name="pfix" readonly="readonly" value="<?php echo $hesk_settings['db_pfix']; ?>" size="40" /></td>
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
<td>INSTALLATION STEPS:<br />
<font color="#008000">1. License agreement</font> -&gt; <b>2. Check setup</b> -&gt; 3. Database settings -&gt; 4. Setup database tables</td>
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
    $_SESSION['use_spamq']=1;
}
else
{
	$_SESSION['set_captcha']=1;
    $_SESSION['use_spamq']=0;
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
	<form method="post" action="install.php">
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
	<form method="POST" action="install.php">
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
<td>INSTALLATION STEPS:<br />
<b>1. License agreement</b> -&gt; 2. Check setup -&gt; 3. Database settings -&gt; 4. Setup database tables</td>
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

<form method="get" action="install.php" name="license" onsubmit="return hesk_checkAgree()">
<div align="center">
<table border="0">
<tr>
<td><center>
<!--<p><b>Create your Zadmin Hesk database first before we proceed to the installation process, click</b> <font color="#FF0000"><i>"Create Database"</i></font>-->
<br /><br/><b>Do you agree to the License agreement and all the terms incorporated therein?</b> <font color="#FF0000"><i>(required)</i></center></font></b></p>

<p align="center">
<input type="hidden" name="agree" value="YES" />
<!--<input type="button" value=" CREATE DATABASE " class="orangebutton" onclick="window.open('/?module=mysql_databases')" />-->
&nbsp;
<input type="submit" value=" YES,I AGREE " class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
&nbsp;
<input type="button" onclick="javascript:self.location='index.php'" value=" NO, I DO NOT AGREE " class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
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
	<title>Install Hesk <?php echo HESK_NEW_VERSION; ?></title>
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
	<td class="headersm">HESK <?php echo HESK_NEW_VERSION; ?> installation script</td>
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
	<p style="text-align:center"><span class="smaller">&nbsp;<br />Powered by <a href="http://www.hesk.com" class="smaller" title="Free PHP Help Desk Software">Help Desk Software</a> <b>HESK</b> - brought to you by <a href="http://www.ilient.com">Help Desk Software</a> SysAid</span></p></td></tr></table></div></body></html>
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
