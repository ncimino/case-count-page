<?php
include_once("./include/includes.php");
DB_CONNECT($con);
SET_COOKIES();

// Tell SELECTDATE to show next week in the dropdown, if next week doesn't have a schedule
$shownextweek = 1;
// If a date isn't selected, then set default to next weeks schedule - change the date to local time so that next week is based on Monday at 00:00 for local time
$daylightsavings = 1;
if ($_GET['selecteddate'] == '')
  $selecteddate = mktime()+60*60*24*7+60*60*($_COOKIE['timezone']+$daylightsavings);
else
  $selecteddate = $_GET['selecteddate'];
  
if ( VERIFY_USER($con) ) 
  {
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
  <script type="text/javascript" src="<? echo MAIN_JS_FILE ?>"></script> 
</head>
<body>
<div id="page" class="page">
  <div id="header" class="header">
    <h1><? echo SITE_NAME ?></h1>
  </div>
  <div id="topmenu" class="topmenu">
<? TOPMENU() ?>
  </div>
  <div id="selectdate" class="selectdate">
    <br />
<? SELECTDATE($shownextweek,$selecteddate,$con) ?>
  </div>
  <div id="schedule" class="schedule">
    <br />
<? SCHEDULE($selecteddate,$con) ?>
  </div>
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