<?php
include_once("./include/includes.php");
DB_CONNECT($con);
SET_COOKIES($selected_page,$showdetails,$timezone,$userID,$con);

// Tell SELECTDATE to show next week in the dropdown
$shownextweek = 1;
// If a date isn't selected, then set default to next weeks schedule - change the date to local time so that next week is based on Monday at 00:00 for local time
$dst_value_from_current_time_sec = date("I")*60*60; // This is a 1*60*60 if DST is set on the time
($_GET['selecteddate'] == '') ? $selecteddate = mktime()+60*60*24*7+60*60*$timezone+$dst_value_from_current_time_sec : $selecteddate = $_GET['selecteddate'];

$current_week = DETERMINE_WEEK($selecteddate);

$activeusers = mysql_query("SELECT * FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."' ORDER BY UserName;",$con);
$site_name = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options,Sites WHERE OptionName='sitename' AND Options.siteID=Sites.siteID AND SiteName='main';",$con));
$replyto = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='replyto' AND siteID='".$selected_page."';",$con));

BUILD_PHONE_SCHEDULE_ARRAY($schedule,$current_week['Monday'],$current_week['Friday'],$selected_page,$con);

// Send an event email to each on schedule
foreach ($schedule as $date)
{
  foreach ($date as $userID => $userID_array)
  {
    foreach ($userID_array as $shift)
    {
      echo "<br />\n";
      echo "Email going to " . $shift['username'] . " is being sent to: " . $shift['useremail'] . "<br />\n";
      PRINT_PHONE_EMAIL($replyto['OptionValue'],$site_name['OptionValue'],$userID,$shift,$current_week,$selected_page,$con);
      $email_sent[$userID] = 1;
    }
  }
}

// Send regular schedule email to those not on shift
//while ( $currentuser = mysql_fetch_array($activeusers) )
//{
//  if ($email_sent[$currentuser['userID']] != 1)
//  PHONE_EMAIL($replyto['OptionValue'],$site_name['OptionValue'],$currentuser['UserEmail'],$currentuser['userID'],$current_week,$selected_page,$con);
//}

function PRINT_PHONE_EMAIL($replyto,$site_name,$userID,$shift,$current_week,$selected_page,&$con)
{
  $from = MAIN_EMAILS_FROM;
  $to = $shift['useremail'];
  
  BUILD_PHONE_SHIFT_TABLE_HTML($currentqueue,$current_week,$userID,$selected_page,$con);
  
  //Create Calendar Event
  BUILD_VCALENDAR_HEADER($cal_file,-7,0,'');
  BUILD_VEVENT($cal_file,$from,$shift);
  BUILD_VCALENDAR_END($cal_file);
  
  CREATE_MIME_BOUNDARY($mime_boundary,$userID.$shift['start'].rand()); 

  //Create Email subject
  $subject = "Phone Schedule - ".gmdate("n/j",$current_week[0])." to ".gmdate("n/j",$current_week[4]);
  if ($_POST['initial_email'] == 2)
  $subject = "Updated: ".$subject;

  //Create Email Body (HTML)
  CREATE_EVENT_EMAIL_BODY($message,$mime_boundary,$currentqueue,$site_name,$cal_file);
  CREATE_EVENT_EMAIL_HEADER($header,$from,$replyto,$mime_boundary);

  echo "<table style='border: 1px solid black;width: 1800 px;'>";
  echo "<tr><td style='border: 1px solid black;'>To:</td>";
  echo "<td style='border: 1px solid black;'>".$to."</td></tr>\n";
  echo "<tr><td style='border: 1px solid black;'>Subject:</td>";
  echo "<td style='border: 1px solid black;'>".$subject."</td></tr>\n";
  echo "<tr><td style='border: 1px solid black;'>Header:</td>";
  echo "<td style='border: 1px solid black;'><pre>".$header."</pre></td></tr>\n";
  echo "<tr><td style='border: 1px solid black;'>Body:</td>";
  echo "<td  style='border: 1px solid black;'><pre>".$message."</pre></td></tr></table>\n";
}
 
mysql_close($con);
?>