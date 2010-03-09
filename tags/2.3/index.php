<?php
include_once("./include/includes.php");
include_once("./include/index_funcs.php");
DB_CONNECT($con);
SET_COOKIES($selected_page,$showdetails,$showdetails_cat1,$timezone,$userID,$con);

// Tell SELECTDATE to show next week in the dropdown
$shownextweek = 1;
// If a date isn't selected, then set default to this weeks schedule AND change the date to local time so that next week is based on Monday at 00:00 for local time
$dst_value_from_current_time_sec = date("I")*60*60; // This is a 1*60*60 if DST is set on the time
($_GET['selecteddate'] == '') ? $selecteddate = time()+60*60*$timezone+$dst_value_from_current_time_sec : $selecteddate = $_GET['selecteddate'];

if ( VERIFY_USER($con) )
{
	?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta http-equiv='refresh' content='300; URL=./index.php?option_page=<?php echo $selected_page;?>' />
<meta name="author" content="<? echo AUTHOR; ?>" />
<meta name="description" content="<? echo DESCRIPTION; ?>" />
<meta name="keywords" content="<? echo KEYWORDS; ?>" />
<title><? echo SITE_NAME($selected_page,$con); ?></title>
<link rel="icon" href="images/bomb.png" />
<link type="text/css" rel="stylesheet" href="<? echo MAIN_CSS_FILE; ?>" />
<script type="text/javascript" src="<? echo MAIN_JS_FILE; ?>"></script>
</head>
<body>
<div id="page" class="page">

<div id="header" class="header">

<div id="selectsite" class="selectsite">
<?
SELECTSITE($selected_page,$con);
?>
</div>

<div id="title" class="title">
<h1><? echo SITE_NAME($selected_page,$con); ?></h1>
</div>

<div id="selectuser" class="selectuser">
<? 
SELECTUSER($timezone,$userID,$con); 
?>
</div>

</div>

<div id="topmenu" class="topmenu"><? TOPMENU('') ?></div>
<? 
INDEX($selected_page,$showdetails,$showdetails_cat1,$userID,$timezone,$shownextweek,$selecteddate,$con); 
?>
<div id="bottommenu" class="bottommenu"><? BOTTOMMENU($con) ?></div>

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