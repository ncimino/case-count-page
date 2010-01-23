<?php
include_once("./include/includes.php");
DB_CONNECT($con);
$site_name = SITE_NAME($_GET['calendar_page'],$con);
$file_name = preg_replace('/ /', '', $site_name);

header("Content-Type: text/Calendar");
header("Content-Disposition: inline; filename=".$file_name.".ics");

BUILD_VCALENDAR_HEADER($cal_file,-7,'ical','Phone Shifts');

$current_time = time(); // Time is PST, but doesn't matter as this is just used to determine the current week
$last_week = DETERMINE_WEEK($current_time-7*24*60*60);
$next_week = DETERMINE_WEEK($current_time+7*24*60*60);

BUILD_PHONE_SCHEDULE_ARRAY($schedule,$last_week['Monday'],$next_week['Friday'],$_GET['calendar_page'],$con);

$current = gmdate('Ymd\THis',$current_time);

$from = MAIN_EMAILS_FROM;

// Build all events for each of the different of the different events
foreach ($schedule as $date)
{
  foreach ($date as $userID)
  {
    foreach ($userID as $shift)
    {
      BUILD_VEVENT($cal_file,$from,$shift,'','ical');
    }
  }
}
BUILD_VCALENDAR_END($cal_file);

echo $cal_file;
mysql_close($con);

?>