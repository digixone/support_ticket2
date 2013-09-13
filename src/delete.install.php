<?php
/***
| Change name
***/
$target = "install"; 
$newName = "_delete_me";
$renameResult = rename($target, $newName);
// Evaluate the value returned from the function if needed
if ($renameResult == true) {
    echo "<div class=style2 style=color:#FF0000><b>$target</b></div>" . "<div class=style3><h4>is now removed or renamed as</h4></div>" . "<div class=style2 style=color:#0000FF><b>$newName</b></div>";
} else {
     echo "<div class=style1><h3>Could not rename that folder</h3></div>";
}

$output = shell_exec('rm -r -f /etc/zpanel/panel/modules/support_ticket2/hesk/_delete_me/index.php');
echo "<pre>$output</pre>";
$output = shell_exec('rm -r -f /etc/zpanel/panel/modules/support_ticket2/hesk/_delete_me/install.php');
echo "<pre>$output</pre>";
$output = shell_exec('rm -r -f /etc/zpanel/panel/modules/support_ticket2/hesk/_delete_me/update.php');
echo "<pre>$output</pre>";
$output = shell_exec('rm -r -f /etc/zpanel/panel/modules/support_ticket2/hesk/_delete_me');
echo "<pre>$output</pre>";
?>
<html>
<head>
<title>Change Interface</title>
<script language="Javascript" type="text/javascript" src="../hesk_javascript.js"></script>
<script>
    window.onunload = refreshParent;
    function refreshParent() {
        window.opener.location.reload();
    }
</script>
<!--
<script>
function reloadPage()
  {
  history.go(-1)
  }
</script>
-->
<style type="text/css">
<!--
body {
background-color: #EAEAEA;
margin-left: 20px;
}
.style1 {
font-family: Geneva, Arial, Helvetica, sans-serif
}
.style2 {
font-family: Geneva, Arial, Helvetica, sans-serif; 
font-size: small;
text-transform:uppercase;
}
.style3 {
font-family: Geneva, Arial, Helvetica, sans-serif; 
)
-->
</style>
</head>
<!--<body onload=setTimeout("self.close()",5000)>-->
<body>
<br/><br/>
<input type="button" onclick="javascript:self.location='index.php'" value=" RELOAD PAGE " class="orangebutton" onmouseover="hesk_btn(this,'orangebuttonover');" onmouseout="hesk_btn(this,'orangebutton');" />
  </p>
  </span></h4>
</body>
</html>
