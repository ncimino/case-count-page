<?php
include_once("./include/includes.php");
include_once("./include/crc_funcs.php");
DB_CONNECT($con);
SET_COOKIES($selected_page,$showdetails,$showdetails_cat1,$timezone,$userID,$con);

// Tell SELECTDATE to show next week in the dropdown
$shownextweek = 1;
// If a date isn't selected, then set default to this weeks schedule AND change the date to local time so that next week is based on Monday at 00:00 for local time
$dst_value_from_current_time_sec = date("I")*60*60; // This is a 1*60*60 if DST is set on the time
($_GET['selecteddate'] == '') ? $selecteddate = mktime()+60*60*$timezone+$dst_value_from_current_time_sec : $selecteddate = $_GET['selecteddate'];

//if ( VERIFY_USER($con) )
if (1) // Must be enabled to modify password info
{
  ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta name="author" content="<? echo AUTHOR ?>" />
<meta name="description" content="<? echo DESCRIPTION ?>" />
<meta name="keywords" content="<? echo KEYWORDS ?>" />
<title><? echo SITE_NAME($selected_page,$con) ?></title>
<link rel="icon" href="images/bomb.png" />
<link type="text/css" rel="stylesheet" href="<? echo MAIN_CSS_FILE ?>" />
<script type="text/javascript" src="<? echo MAIN_JS_FILE ?>"></script>
</head>
<body>
<div id="page" class="page">

<div id="header" class="header">

<div id="selectsite" class="selectsite"><?
SELECTSITE($selected_page,$con);
?></div>

<div id="title" class="title">
<h1>CRC Page</h1>
</div>

<div id="selectuser" class="selectuser"><? SELECTUSER($timezone,$userID,$con) ?>
</div>

</div>

<div id="topmenu" class="topmenu"><? TOPMENU('') ?></div>

<div id="checkcountdates" class="checkcountdates">
<hr width='50%' />
<? CHECKCOUNTDATES($con) ?></div>

<div id="checkscheduledates" class="checkscheduledates">
<hr width='50%' />
<? CHECKSCHEDULEDATES($con) ?></div>

<div id="addemailcolumntousers" class="addemailcolumntousers">
<hr width='50%' />
<? ADDEMAILCOLUMNTOUSERS($con) ?></div>

<div id="addqueuemaxtooptions" class="addqueuemaxtooptions">
<hr width='50%' />
<? ADDQUEUEMAXTOOPTIONS($con) ?></div>

<div id="addqueuecctooptions" class="addqueuecctooptions">
<hr width='50%' />
<? ADDQUEUECCTOOPTIONS($con) ?></div>

<div id="REMOVEUNIQUEFROMOPTIONNAMES"
  class="REMOVEUNIQUEFROMOPTIONNAMES"
>
<hr width='50%' />
<? REMOVEUNIQUEFROMOPTIONNAMES($con) ?></div>

<div id="ADD_OPTIONNAME_TO_OPTIONS" class="ADD_OPTIONNAME_TO_OPTIONS">
<hr width='50%' />
<? ADD_OPTIONNAME_TO_OPTIONS($con) ?></div>

<div id="ADDUNIQUETOOPTIONNAMES_PERPAGE"
  class="ADDUNIQUETOOPTIONNAMES_PERPAGE"
>
<hr width='50%' />
<? ADDUNIQUETOOPTIONNAMES_PERPAGE($con) ?></div>

<div id="UPDATE_OPTIONS_WITH_siteID" class="UPDATE_OPTIONS_WITH_siteID">
<hr width='50%' />
<? UPDATE_OPTIONS_WITH_siteID($con) ?></div>

<div id="ADD_REPLYTO_OPTION" class="ADD_REPLYTO_OPTION">
<hr width='50%' />
<? ADD_REPLYTO_OPTION($con) ?></div>

<div id="ADD_GENERAL_OPTION" class="ADD_GENERAL_OPTION">
<hr width='50%' />
<? ADD_GENERAL_OPTIONS($con) ?></div>

<div id="ADD_PHONESHIFT_OPTIONS" class="ADD_PHONESHIFT_OPTIONS">
<hr width='50%' />
<? ADD_PHONESHIFT_OPTIONS($con) ?></div>

<div id="ADD_SITES" class="ADD_SITES">
<hr width='50%' />
<? ADD_SITES($con) ?></div>

<div id="ADDFOREIGNKEYTOOPTION_siteID"
  class="ADDFOREIGNKEYTOOPTION_siteID"
>
<hr width='50%' />
<? ADDFOREIGNKEYTOOPTION_siteID($con) ?></div>

<div id="DROP_UNIQUE_USERNAME" class="DROP_UNIQUE_USERNAME">
<hr width='50%' />
<? DROP_UNIQUE_USERNAME($con) ?></div>

<div id="ADD_UNIQUE_usersites_IDs" class="ADD_UNIQUE_usersites_IDs">
<hr width='50%' />
<? ADD_UNIQUE_usersites_IDs($con) ?></div>

<div id="ADD_siteID_TO_SCHEDULE" class="ADD_siteID_TO_SCHEDULE">
<hr width='50%' />
<? ADD_siteID_TO_SCHEDULE($con) ?></div>

<div id="ADD_siteID_TO_COUNT" class="ADD_siteID_TO_COUNT">
<hr width='50%' />
<? ADD_siteID_TO_COUNT($con) ?></div>

</div>
</body>
</html>

<?
}
else
{
  VERIFY_FAILED($selected_page,$con);
}
mysql_close(&$con);
?>