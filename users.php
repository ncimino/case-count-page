<?php
include_once("./include/includes.php");
DB_CONNECT($con);
SET_COOKIES($option_page,$showdetails,$timezone,$userID,$con);

if ( VERIFY_USER($con) )
{
    ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta name="author" content="<? echo AUTHOR ?>" />
<meta name="description" content="<? echo DESCRIPTION ?>" />
<meta name="keywords" content="<? echo KEYWORDS ?>" />
<title><? SITE_NAME($con) ?></title>
<link rel="icon" href="images/bomb.png" sizes="64x64" />
<link type="text/css" rel="stylesheet" href="<? echo MAIN_CSS_FILE ?>" />
<script type="text/javascript" src="<? echo MAIN_JS_FILE ?>"></script>
</head>
<body>
<div id="page" class="page">

<div id="header" class="header">
<h1><? SITE_NAME($con) ?></h1>
</div>

<div id="topmenu" class="topmenu"><? TOPMENU() ?></div>

<div id="users" class="users"><? USERS($con) ?></div>

</div>
</body>
</html>

    <?
}
else
{
    VERIFY_FAILED($con);
}
mysql_close(&$con);
?>