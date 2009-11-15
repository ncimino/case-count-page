<?php
include_once("./include/includes.php");
DB_CONNECT($con);

// If a date isn't selected, then set default to this weeks schedule - change the date to local time so that next week is based on Monday at 00:00 for local time
  $daylightsavings = 1;
  if ($_GET['selecteddate'] == '') $selecteddate = mktime()+60*60*($_COOKIE['timezone']+$daylightsavings);
  else $selecteddate = $_GET['selecteddate'];

  $shownextweek = 0;
  
if ( VERIFY_USER($con) ) {
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<? 
if ( $_GET["userID"] != "" ) echo "<meta http-equiv='refresh' content='0; URL=/index.php' />";
else echo "<meta http-equiv='refresh' content='300; URL=/index.php' />";
?>
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
  <div id="selectuser" class="selectuser">
    <? SELECTUSER($con) ?>
  </div>
  <div id="selectdate" class="selectdate">
    <? SELECTDATE($shownextweek,$selecteddate,$con) ?>
  </div>
  <div id="mycasecount" class="mycasecount">
    <br />
    <? MYCASECOUNT($selecteddate,$con) ?>
  </div>
  <div id="currentqueue" class="currentqueue">
    <br />
    <? CURRENTQUEUE($selecteddate,$con) ?>
  </div>
  <div id="notes" class="notes">
    <? //echo NOTES() ?>
  </div>
  <div id="history" class="history">
    <br />
    <? HISTORY($selecteddate,$con);
      $daylightsavings = 1; // This will need to be replaced by the daylight savings variable
       echo "Last updated: ".gmdate("n/j h:i A",mktime()+60*60*($_COOKIE['timezone']+$daylightsavings))." - This page will refresh every 5 minutes";
    ?>
  </div>
  <div id="rules" class="rules">
    <? //echo RULES() ?>
  </div>
</div>
</body>
</html>

<? } else { 
VERIFY_FAILED($con); 
mysql_close(&$con);
} ?>