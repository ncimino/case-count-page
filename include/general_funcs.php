<?php

function SELECTUSER($timezone,$userID,&$con)
{
    // Creates timezones add as necessary from GMT
    $alltimezones['MST'] = -7;
    $alltimezones['PST'] = -8;

    // This variable is set to 1 if a user that exists is found
    $userselected = 0;

    // First determine if there are any active users
    $activeusers = mysql_query("SELECT * FROM Users WHERE Active=1 ORDER BY UserName;",$con);
    if ( mysql_num_rows($activeusers) == 0 )
    {
        echo "    No active users found.<br />\n";
    }
    else
    {
        echo "    <form method='post' name='selectuser'> User:\n";
        echo "      <select name='userID' OnChange='selectuser.submit();'>\n";

        while ( $currentuser = mysql_fetch_array($activeusers) )
        {
            echo "        <option ";
            if ( $userID == $currentuser['userID'] )
            {
                echo "selected='selected' ";
                $userselected = 1;
            }
            echo "value='".$currentuser['userID']."'>".$currentuser['UserName']."</option>\n";
        }

        echo "        <option";
        if ($userselected != 1)
        echo " selected='selected'";
        echo " value='NULL'>-----</option>\n";
        echo "      </select>\n";

        echo "      <select name='timezone' OnChange='selectuser.submit();'>\n";
        foreach ( $alltimezones as $key => $value )
        {
            echo "        <option ";
            if ( $timezone == $value ) echo "selected='selected'";
            echo "value='".$value."'>".$key."</option>\n";
        }
        echo "      </select>\n";
        echo "      <input type='submit' id='selectuser_submit' value='select' />\n";
        echo "    </form>\n";
        echo "    <script type='text/javascript'>\n";
        echo "      <!--\n";
        echo "      document.getElementById('selectuser_submit').style.display='none'; // hides button if JS is enabled-->\n";
        echo "    </script>\n";
    }
}

function SITE_NAME($selected_page,&$con)
{
    $site_name = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='sitename' AND siteID='".$selected_page."';",$con));
    return $site_name['OptionValue'];
}

function TOPMENU($path)
{ ?>
<a href='<? echo $path?>index.php'>Home</a>
-
<a href='<? echo $path?>schedule.php'>Schedule</a>
-
<a href='<? echo $path?>users.php'>Users</a>
-
<a href='<? echo $path?>manage.php'>Manage</a>
-
<a href='<? echo $path?>index.php?logout=1'>Logout</a>
<? }

function BOTTOMMENU(&$con)
{
	$pages_query = mysql_query("SELECT Options.siteID,OptionValue FROM Sites,Options WHERE Active='1' AND OptionName='sitename' AND SiteName<>'main' AND Options.siteID=Sites.siteID;",$con);

	$dash_check = 0;
	echo "<a href='http://172.19.68.184:5800/' target='_blank'>Phone VNC Viewer</a>\n";
	while($pages = mysql_fetch_array($pages_query))
	{
		//if ($dash_check++ != 0)
		echo "-\n";
		echo "<a href='?option_page=".$pages['siteID']."'>".$pages['OptionValue']."</a>\n";
	}
}

function DETERMINE_WEEK($timestamp)
{
  if ( $timestamp == '' )
  $timestamp = mktime();

  // Get the timestamp of 00:00 for today
  $timestamp_zero = strtotime(gmdate("d",$timestamp)." ".gmdate("M",$timestamp)." ".gmdate("Y",$timestamp)." 00:00:00 +0000");

  // How many days away from Monday is the timestamp_zero: shift
  if ( gmdate("l",$timestamp_zero) == 'Monday' ) $shift = 0;
  else if ( gmdate("l",$timestamp_zero) == 'Tuesday' ) $shift = -1;
  else if ( gmdate("l",$timestamp_zero) == 'Wednesday' ) $shift = -2;
  else if ( gmdate("l",$timestamp_zero) == 'Thursday' ) $shift = -3;
  else if ( gmdate("l",$timestamp_zero) == 'Friday' ) $shift = -4;
  else if ( gmdate("l",$timestamp_zero) == 'Saturday' ) $shift = -5;
  else if ( gmdate("l",$timestamp_zero) == 'Sunday' ) $shift = -6;
  
  for ($i=0;$i<=4;$i++)
    $current_week[$i] = $timestamp_zero+($i+$shift)*60*60*24;
  
  $current_week['Monday'] = $current_week[0];
  $current_week['Tuesday'] = $current_week[1];
  $current_week['Wednesday'] = $current_week[2];
  $current_week['Thursday'] = $current_week[3];
  $current_week['Friday'] = $current_week[4];

  return $current_week;
}

function BUILD_PHONE_SHIFT_TABLE_HTML(&$currentqueue,$current_week,$selected_userID,$selected_page,&$con)
{
  $currentqueue = "<table style=\"border-collapse:collapse;width:50em;border: 1px solid black;\">";

  $currentqueue .= "<tr>\n";
  $currentqueue .= "  <th>Shift</th>\n";
  for ($i=0;$i<5;$i++)
  $currentqueue .= "  <th>".gmdate("D n/j",$current_week[$i])."</th>\n";
  $currentqueue .= "</tr>\n";

  // Creates phone shift times
  CREATE_PHONESHIFTS($phoneshifs,$current_week[0],-8);

  for ($shift_index=0;$shift_index<=5;$shift_index++)
  {
    $currentqueue .= "<tr>\n";
    for ($col=1; $col<=6; $col++)
    {
      if ($col==1)
      {
        $currentqueue .= "  <td style=\"border:1px solid;text-align:center;width:10em;\">";
        $currentqueue .= "<div style=\"height: 3em;vertical-align: top;\">\n";
        $currentqueue .= gmdate("g:ia",$phoneshifs[$shift_index]['start'])." - ".gmdate("g:ia",$phoneshifs[$shift_index]['end']);
        if ($shift_index==2 or $shift_index==3)
        $currentqueue .= "<br />Cover";
        $currentqueue .= "</div>\n";
        $currentqueue .= "</td>\n";
      }
      else
      {
        $sql = "SELECT UserName,Users.userID 
                FROM Users,PhoneSchedule,UserSites 
                WHERE Active=1 
                  AND Users.userID=PhoneSchedule.userID
                  AND Users.userID=UserSites.userID
                  AND UserSites.siteID=".$selected_page." 
                  AND Shift='".$shift_index."' 
                  AND Date='".$current_week[$col-2]."' 
                ORDER BY UserName;";
        $users_on_shift_query = mysql_query($sql,$con);

        $onqueue = 0;
        $user_log = '';
        while ( $users_on_shift = mysql_fetch_array($users_on_shift_query) )
        {
          if ($users_on_shift['userID'] == $selected_userID)
          {
            $onqueue = 1;
            $user_log .= "        <span style=\"font-weight: bold;\">".$users_on_shift['UserName']."</span><br />\n";
          }
          else
          {
            $user_log .= "        ".$users_on_shift['UserName']."<br />\n";
          }
        }

        $currentqueue .= "  <td style=\"border:1px solid;text-align:center;width:10em;";
        if ($onqueue == 1)
        {
          $currentqueue .= "background:lightblue;";
        }
        $currentqueue .= "\">";
        $currentqueue .= $user_log;
        $currentqueue .= "  </td>\n";
      }
    }
    $currentqueue .= "</tr>\n";
  }

  $currentqueue .= "</table>\n";
}

function BUILD_QUEUE_SHIFT_TABLE_HTML(&$currentqueue,$current_week,$userID,$selected_page,&$con)
{
  $currentqueue = "<table style=\"border-collapse:collapse;width:50em;\">";

  $currentqueue .= "      <tr>\n";
  for ($i=0;$i<5;$i++)
  $currentqueue .= "        <th style=\"border:0px none;text-align:center;width:10em;\">".gmdate("D",$current_week[$i])."&nbsp;".gmdate("n/j",$current_week[$i])."</th>\n";
  $currentqueue .= "      </tr>\n";

  for ($i = 0; $i <= 4; $i++)
  {
    $shift = mysql_fetch_array(mysql_query("SELECT COUNT(Shift) FROM Users,Schedule WHERE Users.userID = Schedule.userID AND Users.Active = 1 AND Date = ".$current_week[$i]." AND siteID='".$selected_page."'",$con));
    $shiftcount[$i] = $shift['COUNT(Shift)'];
    $currentday = mysql_query("SELECT UserName,Shift,Users.userID FROM Users,Schedule WHERE Schedule.Date = ".$current_week[$i]." AND Users.userID = Schedule.userID AND Users.Active = 1 AND siteID='".$selected_page."'",$con);
    $j = 0;
    while ($getarray = mysql_fetch_array($currentday)) { $namesAndShifts[$i][$j++] = $getarray; }
  }

  rsort($shiftcount,SORT_NUMERIC);

  for ($row = 1; $row <= $shiftcount[0]; $row++)
  {
    $currentqueue .= "      <tr class='currentqueue'>\n";
    for ($col = 1; $col <= 5; $col++)
    {
      $currentqueue .= "       <td style=\"border:1px solid;text-align:center;width:10em;";
      if (($namesAndShifts[$col-1][$row-1]['userID'] == $userID ) and ($userID != ''))
      $currentqueue .= " background: lightblue;";
      $currentqueue .= "\">\n";
      $currentqueue .= $namesAndShifts[$col-1][$row-1]['UserName'];
      if ($namesAndShifts[$col-1][$row-1]['Shift'] == 1)
      $currentqueue .= "&nbsp;(.5)";
      $currentqueue .= "</td>\n";
    }
    $currentqueue .= "      </tr>\n";
  }
  $currentqueue .= "    </table>\n";
}

function BUILD_PHONE_SCHEDULE_ARRAY(&$schedule,$begin_date,$end_date,$siteID,&$con)
{
  $create_date = gmdate('Ymd\THis',$begin_date);
  
  $sql = "SELECT *
    FROM PhoneSchedule,Users,UserSites
    WHERE Date >= ".$begin_date."
      AND Date <= ".$end_date."
      AND PhoneSchedule.userID=Users.userID
      AND UserSites.userID=Users.userID
      AND UserSites.siteID=".$siteID;

  $phoneschedule = mysql_query($sql,$con);

  while ($currentschedule = mysql_fetch_array($phoneschedule))
  {
    $uid = "phoneschedule_" . $currentschedule['Date'] . "_" . $currentschedule['userID'] . "_" . $currentschedule['Shift'];
    $userID = $currentschedule['userID'];
    $useremail = $currentschedule['UserEmail'];
    $date = $currentschedule['Date'];
    $shift = $currentschedule['Shift'];
    $username = $currentschedule['UserName'];
    // Creates phone shift times
    CREATE_PHONESHIFTS($phoneshifs,$date,-7); // Create times for MST as calendar entry is MST
    $start = gmdate('Ymd\THis',$phoneshifs[$shift]['start']);
    $end = gmdate('Ymd\THis',$phoneshifs[$shift]['end']);

    // Combine the first two or last two shifts into a single event
    $match_found = 0;
    if (is_array($schedule[$date][$userID]))
    foreach ($schedule[$date][$userID] as $shift_index => $array)
    {
      if (($shift_index == 0 and $shift == 1)
      or ($shift_index == 4 and $shift == 5))
      {
        $schedule[$date][$userID][$shift_index]['end'] = $end;
        $match_found = 1;
      }
      if (($shift_index == 1 and $shift == 0)
      or ($shift_index == 5 and $shift == 4))
      {
        $schedule[$date][$userID][$shift_index]['start'] = $start;
        $match_found = 1;
      }
    }

    // If this shift isn't part of shift pair, then add the new shift to $schedule
    if (!$match_found)
    {
      $schedule[$date][$userID][$shift]['create_date'] = $create_date;
      $schedule[$date][$userID][$shift]['uid'] = $uid;
      $schedule[$date][$userID][$shift]['useremail'] = $useremail;
      $schedule[$date][$userID][$shift]['username'] = $username;
      $schedule[$date][$userID][$shift]['start'] = $start;
      $schedule[$date][$userID][$shift]['end'] = $end;
      if (($currentschedule['Shift']==2) or ($currentschedule['Shift']==3))
      {
        $schedule[$date][$userID][$shift]['cover'] = ' (Cover)';
        $schedule[$date][$userID][$shift]['category'] = 'Red Category';
      }
      else
      {
        $schedule[$date][$userID][$shift]['cover'] = '';
        $schedule[$date][$userID][$shift]['category'] = 'Red Category';
      }
    }

  }
}

function BUILD_QUEUE_SCHEDULE_ARRAY(&$schedule,$begin_date,$end_date,$siteID,&$con)
{
  $create_date = gmdate('Ymd\THis',$begin_date);
  $start = gmdate('Ymd',$begin_date);
  $end = gmdate('Ymd',$begin_date+1*24*3600); // duration of each event is one day
  
  $sql = "SELECT *
    FROM Schedule,Users,UserSites
    WHERE Date >= ".$begin_date."
      AND Date <= ".$end_date."
      AND Schedule.userID=Users.userID
      AND UserSites.userID=Users.userID
      AND UserSites.siteID=".$siteID."
      AND UserSites.siteID=Schedule.siteID
      AND Users.Active=1
    ORDER BY Date;";

  $queueschedule = mysql_query($sql,$con);

  while ($currentschedule = mysql_fetch_array($queueschedule))
  {
    $date = $currentschedule['Date'];
    $userID = $currentschedule['userID'];
    $shift = $currentschedule['Shift'];
    
    if ($shift/2 == 1)
      $schedule[$userID][$shift]['type'] = 'FULL';
    else
      $schedule[$userID][$shift]['type'] = 'HALF';
    
    $schedule[$userID][$shift]['create_date'] = $create_date;
    $schedule[$userID][$shift]['uid'] = "queueschedule_" . $begin_date . "_" . $currentschedule['userID'] . "_" . $currentschedule['Shift'];
    $schedule[$userID][$shift]['useremail'] = $currentschedule['UserEmail'];
    $schedule[$userID][$shift]['username'] = $currentschedule['UserName'];
    $schedule[$userID][$shift]['start'] = $start;
    $schedule[$userID][$shift]['end'] = $end;
    $schedule[$userID][$shift]['days'] .= strtoupper(substr(gmdate('D',$date),0,2)).",";
    $schedule[$userID][$shift]['category'] = 'Red Category';
  }
}

function SELECTSITE($selected_page,&$con)
{
	$pages_query = mysql_query("SELECT Options.siteID,OptionValue FROM Sites,Options WHERE Active='1' AND OptionName='sitename' AND Options.siteID=Sites.siteID;",$con);
	
	echo "    <form method='post' action='?' name='site_selection'>\n";
    echo "      <select name='option_page' OnChange='site_selection.submit();'>\n";
    while($pages = mysql_fetch_array($pages_query))
    {
        echo "        <option value='".$pages['siteID']."'";
        if ($selected_page == $pages['siteID'])
           echo " selected='selected'";
        echo ">".$pages['OptionValue']."</option>\n";
    }
    echo "      </select>\n";
    echo "    <input type='submit' id='site_selection_submit' value='Show' />\n";
    echo "    </form>\n";
    echo "    <script type='text/javascript'>\n";
    echo "      <!--\n";
    echo "      document.getElementById('site_selection_submit').style.display='none'; // hides button if JS is enabled-->\n";
    echo "    </script>\n";
}

function GET_OLDEST_DATE(&$oldest_date,$timezone,&$con)
{
  // Set the DST time automatically from the date
  $dst_value_from_current_time_sec = date("I")*60*60; // This is a 1*60*60 if DST is set on the time
  
  $count = 0;
  
  $sql = "SELECT MIN(Date)
          FROM Count,Users 
          WHERE Users.Active = 1 
          AND Users.userID = Count.userID";  
  $data = mysql_fetch_row(mysql_query($sql,$con));
  if ($data[0] != '')
  	$oldest_dates[$count++] = $data[0];
  
  $sql = "SELECT MIN(Date)
          FROM Schedule,Users 
          WHERE Users.Active = 1 
          AND Users.userID = Schedule.userID";  
  $data = mysql_fetch_row(mysql_query($sql,$con));
  if ($data[0] != '')
  	$oldest_dates[$count++] = $data[0];
  
  $sql = "SELECT MIN(Date) 
          FROM PhoneSchedule,Users 
          WHERE Users.Active = 1 
          AND Users.userID = PhoneSchedule.userID";  
  $data = mysql_fetch_row(mysql_query($sql,$con));
  if ($data[0] != '')
  	$oldest_dates[$count++] = $data[0];
  
  sort($oldest_dates);
  $oldest_date = $oldest_dates[0];
  
  if ($count == 0)
    return false;
  else
    return true;
}

function SELECTDATE($timezone,$shownextweek,$selecteddate,&$con)
{
    // Set the DST time automatically from the date
    $dst_value_from_current_time_sec = date("I")*60*60; // This is a 1*60*60 if DST is set on the time
    $display_week = DETERMINE_WEEK($selecteddate);

    if (!GET_OLDEST_DATE($oldest_date,$timezone,$con))
    {
        echo "    No active schedule or case history found, cannot show date selection.<br />\n";
    }
    else
    {    
        $this_week = DETERMINE_WEEK(time()+60*60*$timezone+$dst_value_from_current_time_sec); // Set last date in drop-down to todays date

        // If 'shownextweek' is set, then add next 2 weeks to the drop-down
        ($shownextweek == 1) ? $most_recent_date = $this_week['Monday']+60*60*24*14 : $most_recent_date = $this_week['Monday'];

        // Create a date for each Monday in the drop-down
        $i = 0;
        do
        {
            $mondays[$i] = $oldest_date+($i*60*60*24*7);
        }
        while ($mondays[$i++] < $most_recent_date);
        
        rsort($mondays,SORT_NUMERIC);
        echo "    <form name='dateselection'> Show:\n";
        echo "      <select name='selecteddate' OnChange='dateselection.submit();'>\n";
        foreach ( $mondays as $key => $value )
        {
            echo "        <option value='" . $value . "'";
            if ($display_week['Monday'] == $value)
            echo " selected='selected' ";
            echo ">" . gmdate("D m/d",$value) . " - " . gmdate("D m/d",$value+60*60*24*4) . "</option>\n";
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

function CREATE_PHONESHIFTS(&$phoneshifs,$date,$timezone)
{
  $phoneshifs[0]['start'] = $date+60*60*$timezone+60*60*(8+7);    // 7:00am PST -
  $phoneshifs[0]['end']   = $date+60*60*$timezone+60*60*(8+9.5);  // 9:30am PST

  $phoneshifs[1]['start'] = $date+60*60*$timezone+60*60*(8+9.5);  // 9:30am PST -
  $phoneshifs[1]['end']   = $date+60*60*$timezone+60*60*(8+12);   // 12:00pm PST

  $phoneshifs[2]['start'] = $date+60*60*$timezone+60*60*(8+9.5);  // Cover 9:30am PST -
  $phoneshifs[2]['end']   = $date+60*60*$timezone+60*60*(8+12);   // Cover 12:00pm PST

  $phoneshifs[3]['start'] = $date+60*60*$timezone+60*60*(8+12);   // Cover 12:00pm PST -
  $phoneshifs[3]['end']   = $date+60*60*$timezone+60*60*(8+14.5); // Cover 2:30pm PST

  $phoneshifs[4]['start'] = $date+60*60*$timezone+60*60*(8+12);   // 12:00pm PST -
  $phoneshifs[4]['end']   = $date+60*60*$timezone+60*60*(8+14.5); // 2:30pm PST

  $phoneshifs[5]['start'] = $date+60*60*$timezone+60*60*(8+14.5); // 2:30pm PST -
  $phoneshifs[5]['end']   = $date+60*60*$timezone+60*60*(8+17);   // 5:00pm PST
}

?>