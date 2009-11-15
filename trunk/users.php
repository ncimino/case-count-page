<?php
include_once("./include/includes.php");
DB_CONNECT($con);
if ( VERIFY_USER($con) ) {
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta name="author" content="<? echo AUTHOR ?>" />
<meta name="description" content="<? echo DESCRIPTION ?>" />
<meta name="keywords" content="<? echo SITE_NAME.", ".KEYWORDS ?>" />
<title><? echo SITE_NAME ?></title>
<link rel="stylesheet" href="<? echo MAIN_CSS_FILE ?>" />
<!--script src='http://...js'></script--> 
</head>
<body>
<div id="page" class="page">
  <div id="header" class="header">
    <h1><? echo SITE_NAME ?></h1>
  </div>
  <div id="topmenu" class="topmenu">
    <? TOPMENU() ?>
  </div>
  <div id="users" class="users">
    <? USERS($con) ?>
  </div>
</div>
</body>
</html>

<? } else { 
VERIFY_FAILED($con); 
mysql_close(&$con);
} ?>