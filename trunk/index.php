<?php
include_once("./include/includes.php");
DB_CONNECT($con);
SET_COOKIES($showdetails,$timezone,$userID,$con);

// Tell SELECTDATE to show next week in the dropdown
$shownextweek = 1;
// If a date isn't selected, then set default to this weeks schedule AND change the date to local time so that next week is based on Monday at 00:00 for local time
$dst_value_from_current_time_sec = date("I")*60*60; // This is a 1*60*60 if DST is set on the time
($_GET['selecteddate'] == '') ? $selecteddate = mktime()+60*60*$timezone+$dst_value_from_current_time_sec : $selecteddate = $_GET['selecteddate'];
  
if ( VERIFY_USER($con) ) 
  {
  ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
  <meta http-equiv='refresh' content='300; URL=./index.php' />
  <meta name="author" content="<? echo AUTHOR ?>" />
  <meta name="description" content="<? echo DESCRIPTION ?>" />
  <meta name="keywords" content="<? echo KEYWORDS ?>" />
  <title><? SITE_NAME($con) ?></title>
  <link type="text/css" rel="stylesheet" href="<? echo MAIN_CSS_FILE ?>" />
  <script type="text/javascript" src="<? echo MAIN_JS_FILE ?>"></script> 
</head>
<body>
<div id="page" class="page">

  <div id="header" class="header">
    <h1><? SITE_NAME($con) ?></h1>
  </div>
  
  <div id="topmenu" class="topmenu">
<? TOPMENU() ?>
  </div>
  
  <div id="selectuser" class="selectuser">
<? SELECTUSER($timezone,$userID,$con) ?>
  </div>
  
  <div id="selectdate" class="selectdate">
<? SELECTDATE($timezone,$shownextweek,$selecteddate,$con) ?>
  </div>
  
  <div id="mycasecount" class="mycasecount">
    <br />
<? MYCASECOUNT($userID,$selecteddate,$con) ?>
  </div>
  
  <div id="currentqueue" class="currentqueue">
		<br />
<? CURRENTQUEUE($userID,$selecteddate,$con) ?>
  </div>
  
  <div id="notes" class="notes">
<? NOTES($con) ?>
  </div>
  
  <div id="currenthistory" class="currenthistory">
<? CURRENTHISTORY($showdetails,$timezone,$userID,$selecteddate,$con);
  $dst_value_from_current_time_sec = date("I")*60*60; // This is a 1*60*60 if DST is set on the time
  echo "    Last updated: ".gmdate("n/j h:i A",mktime()+60*60*$timezone+$dst_value_from_current_time_sec)." - This page will refresh every 5 minutes\n";
  echo "    <hr width='50%' />\n";
?>
  </div>
  
  <div id="rules" class="rules">
    <h3>Queue Expectations</h3>
<? RULES($con) ?>
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