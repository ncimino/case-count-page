<?php

function SITE_NAME(&$con)
{
  $site_name = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='sitename';",&$con));
  echo $site_name['OptionValue'];
}


function TOPMENU()
{ ?>
    <a href='index.php'>Home</a> -
    <a href='schedule.php'>Schedule</a> -
    <abr href='reports.php'>Reports</a> -
    <a href='users.php'>Users</a> -
    <a href='manage.php'>Manage</a> -
    <a href='index.php?logout=1'>Logout</a>
<? }


function DETERMINE_WEEK($timestamp)
{
  if ( $timestamp == '' ) $timestamp = mktime();
  $timestamp_zero = mktime(0,0,0,gmdate("m",$timestamp),gmdate("d",$timestamp),gmdate("Y",$timestamp)); // Get the timestamp of 00:00 for today
  if ( date("l",$timestamp_zero) == 'Monday' ) $shift = 0;
  else if ( date("l",$timestamp_zero) == 'Tuesday' ) $shift = -1;
  else if ( date("l",$timestamp_zero) == 'Wednesday' ) $shift = -2;
  else if ( date("l",$timestamp_zero) == 'Thursday' ) $shift = -3;
  else if ( date("l",$timestamp_zero) == 'Friday' ) $shift = -4;
  else if ( date("l",$timestamp_zero) == 'Saturday' ) $shift = -5;
  else if ( date("l",$timestamp_zero) == 'Sunday' ) $shift = -6;
  $current_week['Monday'] = mktime(0,0,0,gmdate("m",$timestamp_zero),gmdate("d",$timestamp_zero)+$shift,gmdate("Y",$timestamp_zero));
  $current_week['Tuesday'] = mktime(0,0,0,gmdate("m",$timestamp_zero),gmdate("d",$timestamp_zero)+$shift+1,gmdate("Y",$timestamp_zero));
  $current_week['Wednesday'] = mktime(0,0,0,gmdate("m",$timestamp_zero),gmdate("d",$timestamp_zero)+$shift+2,gmdate("Y",$timestamp_zero));
  $current_week['Thursday'] = mktime(0,0,0,gmdate("m",$timestamp_zero),gmdate("d",$timestamp_zero)+$shift+3,gmdate("Y",$timestamp_zero));
  $current_week['Friday'] = mktime(0,0,0,gmdate("m",$timestamp_zero),gmdate("d",$timestamp_zero)+$shift+4,gmdate("Y",$timestamp_zero));
  $current_week[0] = $current_week['Monday'];
  $current_week[1] = $current_week['Tuesday'];
  $current_week[2] = $current_week['Wednesday'];
  $current_week[3] = $current_week['Thursday'];
  $current_week[4] = $current_week['Friday'];
  return $current_week;
}


function SELECTDATE($timezone,$shownextweek,$selecteddate,&$con)
{
$display_week = DETERMINE_WEEK($selecteddate); 

$oldest_schedule = mysql_query("SELECT MIN(Date) FROM Schedule,Users WHERE Users.Active = 1 AND Users.userID = Schedule.userID",$con);
$oldest_case = mysql_query("SELECT MIN(Date) FROM Count,Users WHERE Users.Active = 1 AND Users.userID = Count.userID",$con);
if (( mysql_num_rows($oldest_schedule) == 0 ) and ( mysql_num_rows($oldest_case) == 0 ))
  {
  echo "    No active schedule or case history found, cannot show date selection.<br />\n";
  }
else
  {
  if ( mysql_num_rows($oldest_schedule) != 0 )
    {
    $oldest_schedule_date = mysql_fetch_array($oldest_schedule);
    }
  if ( mysql_num_rows($oldest_case) != 0 )
    {
    $oldest_case_date = mysql_fetch_array($oldest_case);
    }
  
  // Pick the oldest date of schedule_date and case_date and set the oldest_date equal to that date
  ($schedule_date > $case_date) ? $oldest_date = $oldest_schedule_date['MIN(Date)'] : $oldest_date = $oldest_case_date['MIN(Date)'];
  
  // Set the DST time automatically from the date
  $dst_value_from_current_time_sec = date("I")*60*60; // This is a 1*60*60 if DST is set on the time
  $end_week = DETERMINE_WEEK(mktime()+60*60*$timezone+$dst_value_from_current_time_sec); // Set last date in drop-down to todays date
  
  // If 'shownextweek' is set, then add next week to the drop-down
  ($shownextweek == 1) ? $end_date = $end_week['Monday']+60*60*24*7 : $end_date = $end_week['Monday'];  

  // Create a date for each Monday in the drop-down
  $i = 0;
  do
    {
    $mondays[$i] = $oldest_date+($i*60*60*24*7);
    $dst_value_from_current_time_sec = date("I",$mondays[$i])*60*60; // This is a 1 if DST is set on the time
    $mondays[$i] = $mondays[$i]+60*60-$dst_value_from_current_time_sec;  // If DST is on 
    }
    while ($mondays[$i++] < $end_date);
  rsort($mondays,SORT_NUMERIC);
  
  echo "    <form name='dateselection'> Show:\n";
  echo "      <select name='selecteddate' OnChange='dateselection.submit();'>\n";
  foreach ( $mondays as $key => $value )
    {
    echo "        <option value='" . $value . "'";
    if ($display_week['Monday'] == $value) 
      echo " selected='selected' ";
    echo ">" . substr(date("l",$value),0,3) . " " . date("m/d",$value) . " - " . substr(date("l",$value+60*60*24*4),0,3) . " " . date("m/d",$value+60*60*24*4) . "</option>\n";
    }
  echo "      </select>\n";
  echo "      <input type='submit' id='dateselection_submit' value='go'>\n";
  echo "    </form>\n";
  echo "    <script type='text/javascript'>\n";
  echo "      <!--\n";
  echo "      document.getElementById('dateselection_submit').style.display='none'; // hides button if JS is enabled-->\n";
  echo "    </script>\n";
  }
}

?>