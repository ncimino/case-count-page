<?php

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

$activitydates = mysql_query("SELECT Date FROM Schedule,Users WHERE Users.Active = 1 AND Users.userID = Schedule.userID",$con);
if ( mysql_num_rows($activitydates) == 0 )
  {
  echo "    No active schedule history found, cannot show date selection.<br />\n";
  }
else
  {
  $i = 0;
  while ( $currentdate = mysql_fetch_array($activitydates) )
    {
    $current_week = DETERMINE_WEEK($currentdate['Date']); 
    $mondays[$i++] = $current_week['Monday'];
    }
  $mondays = array_unique($mondays);
  rsort($mondays,SORT_NUMERIC);
  
  // This will add next week to the drop down if it is specified and next week doesn't already have data
  $checknextweek = DETERMINE_WEEK(mktime()+60*60*24*7+60*60*($timezone+$daylightsavings)); 
  if (($shownextweek == 1) and ($mondays[0] != $checknextweek['Monday']))
    {
    $mondays[$i++] = $checknextweek['Monday'];
    rsort($mondays,SORT_NUMERIC);
    }
  
    echo "    <form name='dateselection'> Show:\n";
    echo "      <select name='selecteddate' OnChange='dateselection.submit();'>\n";
    foreach ( $mondays as $key => $value )
      {
      echo "        <option value='" . $value . "'";
      if ($display_week['Monday'] == $value) 
        echo " selected='selected' ";
      echo ">" . substr(date("l",$value),0,3) . " " . date("n/j",$value) . " - " . substr(date("l",$value+60*60*24*4),0,3) . " " . date("n/j",$value+60*60*24*4) . "</option>\n";
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