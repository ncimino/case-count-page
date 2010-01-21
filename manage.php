<?php
include_once("./include/includes.php");
DB_CONNECT($con);
SET_COOKIES($selected_page,$showdetails,$timezone,$userID,$con);

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
<title><? SITE_NAME($selected_page,$con) ?></title>
<link rel="icon" href="images/bomb.png" sizes="64x64" />
<link type="text/css" rel="stylesheet" href="<? echo MAIN_CSS_FILE ?>" />
<script type="text/javascript" src="<? echo MAIN_JS_FILE ?>"></script>
</head>
<body>
<div id="page" class="page">

<div id="header" class="header">

<div id="selectsite" class="selectsite">
<?
UPDATE_DB_OPTIONS($selected_page,$con); 
SELECTSITE($selected_page,$con);
?>
</div>

<div id="title" class="title">
<h1>Manage</h1>
</div>

<div id="selectuser" class="selectuser">
<? SELECTUSER($timezone,$userID,$con) ?>
</div>

</div>

<div id="topmenu" class="topmenu"><? TOPMENU('') ?></div>

<div id="managesite" class="managesite"><? MANAGESITE($selected_page,$con) ?></div>
</div>
</body>
</html>

    <?
}
else
{
    VERIFY_FAILED($selected_page,$con);
}
mysql_close($con);
?>