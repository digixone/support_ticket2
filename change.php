<?php
/***
| Change interface from 10.0.2 to 10.1.0
***/
$target = "module.zpm.10.1.0"; 
$newName = "module.zpm";
$renameResult = rename($target, $newName);
// Evaluate the value returned from the function if needed
if ($renameResult == true) {
    echo "<div class=style2 style=color:#FF0000><b>$target</b></div>" . "<div class=style3><h4>is now renamed as</h4></div>" . "<div class=style2 style=color:#0000FF><b>$newName</b></div>";
} else {
     echo "<div class=style1><h3>Could not rename that folder</h3></div>";
}
chmod("module.zpm",0777);
?>
<html>
<head>
<title>Change Interface</title>
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
<body onload=setTimeout("self.close()",5000)>
<h4><span class="style1">Changes will refresh in 5 seconds.
  </p>
  </span></h4>
</body>
</html>