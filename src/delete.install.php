<?php
/***
| Please do not delete this file or installation might not work.
| Hello World Creation: jd1pinoy@gmx.ph
***/
$target = "install"; 
$newName = "_install";
$renameResult = rename($target, $newName);
// Evaluate the value returned from the function if needed
if ($renameResult == true) {
    echo $target . "<h3>is now renamed as</h3>" . $newName;
} else {
     echo "<h3>Could not rename that folder</h3>";
}
?>
<html>
<head>
<!--
<script>
function reloadPage()
  {
  history.go(-1)
  }
</script>
-->
</head>
<body>
<p><h3>Please refresh this page now.</h3></p>
</body>
</html>