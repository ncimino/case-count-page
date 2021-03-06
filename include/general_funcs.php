<?php

function SELECTUSER($timezone,$userID,&$con)
{
	$dst = date("I",time());
	
	// Creates timezones add as necessary from GMT
	  // The -$dst counters the DST for those timezones not affected by DST
	$alltimezones['JST'] = 9-$dst;
	$alltimezones['CCT'] = 8-$dst;
	$alltimezones['IST'] = 5.5-$dst;
	$alltimezones['GMT'] = 0-$dst;
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
		echo "    <form method='post' name='selectuser' action=''> User:\n";
		echo "      <select name='userID' onchange='selectuser.submit();'>\n";

		while ( $currentuser = mysql_fetch_array($activeusers) )
		{
			if ($currentuser['UserName'] != '-----')
			{
				echo "        <option ";
				if ( $userID == $currentuser['userID'] )
				{
					echo "selected='selected' ";
					$userselected = 1;
				}
				echo "value='".$currentuser['userID']."'>".$currentuser['UserName']."</option>\n";
			}
		}

		echo "        <option";
		if ($userselected != 1)
		echo " selected='selected'";
		echo " value='NULL'>-----</option>\n";
		echo "      </select>\n";

		echo "      <select name='timezone' onchange='selectuser.submit();'>\n";
		foreach ( $alltimezones as $key => $value )
		{
			echo "        <option ";
			if ( $timezone == $value ) echo "selected='selected' ";
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

function TOPMENU($path,$selecteddate='')
{
	// Make the Schedule link go to next week by default and Home to this week
	// If this week or a week in the future is selected, then automatically do this also
	// Otherwise, if a date is already selected in the past, then add that to these links to stay current
	$lastweek = time()+60*60*$timezone+$dst_value_from_current_time_sec - 7 * 3600 * 24;
	if (!empty($_GET['selecteddate']) and $selecteddate < $lastweek) { // Only set it if it was already set
		$date = "?selecteddate={$selecteddate}";
	} else {
		$date = "";
	}
	?>
<a href='<? echo $path?>index.php<? echo $date?>'>Home</a>
-
<a href='<? echo $path?>schedule.php<? echo $date?>'>Schedule</a>
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

		for ($shift_index=0;$shift_index<count($phoneshifs);$shift_index++)
		{
			$currentqueue .= "<tr>\n";
			for ($col=1; $col<=6; $col++)
			{
				if ($col==1)
				{
					$currentqueue .= "  <td style=\"border:1px solid;text-align:center;width:10em;\">";
					$currentqueue .= "<div style=\"height: 3em;vertical-align: top;\">\n";
					$currentqueue .= gmdate("g:ia",$phoneshifs[$shift_index]['start'])." - ".gmdate("g:ia",$phoneshifs[$shift_index]['end']);
					if (!empty($phoneshifs[$shift_index]['type'])) $currentqueue .= "<br />".$phoneshifs[$shift_index]['type']."\n";
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
			$uid = "schedule_" . $siteID . "_" . $currentschedule['Date'] . "_" . $currentschedule['userID'] . "_" . $currentschedule['Shift'];
			$userID = $currentschedule['userID'];
			$useremail = $currentschedule['UserEmail'];
			$date = $currentschedule['Date'];
			$shift = $currentschedule['Shift'];
			$username = $currentschedule['UserName'];
			// Creates phone shift times
			CREATE_PHONESHIFTS($phoneshifs,$date,0); // Create times for GMT
			$dst_value_from_current_time_sec = date("I",$phoneshifs[$shift]['start'])*60*60; // This is a 1*60*60 if DST is set on the time
			$start_timestamp = $phoneshifs[$shift]['start'] - $dst_value_from_current_time_sec;
			$end_timestamp = $phoneshifs[$shift]['end'] - $dst_value_from_current_time_sec;
			$start = gmdate('Ymd\THis\Z',$start_timestamp);
			$end = gmdate('Ymd\THis\Z',$end_timestamp);

			$schedule[$date][$userID][$shift]['alarm'] = 1;
			// Only set alarm for the first calendar event
			if (is_array($schedule[$date][$userID]))
			foreach ($schedule[$date][$userID] as $shift_index => $array)
			{
				if (($shift_index == 0 and $shift == 1)
				or ($shift_index == 2 and $shift == 3)
				or ($shift_index == 4 and $shift == 5)
				or ($shift_index == 6 and $shift == 7))
				{
					$schedule[$date][$userID][$shift]['alarm'] = 0;
				}
				if (($shift_index == 1 and $shift == 0)
				or ($shift_index == 3 and $shift == 2)
				or ($shift_index == 5 and $shift == 4)
				or ($shift_index == 6 and $shift == 7))
				{
					$schedule[$date][$userID][$shift_index]['alarm'] = 0;
				}
			}

			$schedule[$date][$userID][$shift]['create_date'] = $create_date;
			$schedule[$date][$userID][$shift]['uid'] = $uid;
			$schedule[$date][$userID][$shift]['useremail'] = $useremail;
			$schedule[$date][$userID][$shift]['username'] = $username;
			$schedule[$date][$userID][$shift]['start'] = $start;
			$schedule[$date][$userID][$shift]['end'] = $end;
			$schedule[$date][$userID][$shift]['type'] = $phoneshifs[$shift_index]['type'];
			$schedule[$date][$userID][$shift]['category'] = 'Red Category';
		}
	}

	function BUILD_QUEUE_SCHEDULE_ARRAY(&$schedule,$begin_date,$end_date,$siteID,&$con)
	{
		$create_date = gmdate('Ymd\THis',$begin_date);

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
			$schedule[$date][$userID][$shift]['type'] = 'Full';
			else
			$schedule[$date][$userID][$shift]['type'] = 'Half';

			$schedule[$date][$userID][$shift]['create_date'] = $create_date;
			$schedule[$date][$userID][$shift]['uid'] = "schedule_" . $siteID . "_" . $date . "_" . $userID . "_" . $shift;
			$schedule[$date][$userID][$shift]['useremail'] = $currentschedule['UserEmail'];
			$schedule[$date][$userID][$shift]['username'] = $currentschedule['UserName'];
			$schedule[$date][$userID][$shift]['start'] = gmdate('Ymd',$date);
			$schedule[$date][$userID][$shift]['end'] = gmdate('Ymd',$date+1*24*3600);
			$schedule[$date][$userID][$shift]['category'] = 'Red Category';
		}
	}

	function SELECTSITE($selected_page,$selecteddate,&$con)
	{
		$pages_query = mysql_query("SELECT Options.siteID,OptionValue FROM Sites,Options WHERE Active='1' AND OptionName='sitename' AND Options.siteID=Sites.siteID;",$con);

		$lastweek = time()+60*60*$timezone+$dst_value_from_current_time_sec - 7 * 3600 * 24;
		if(!empty($selecteddate) and $selecteddate < $lastweek) {
			echo "    <form method='post' action='?selecteddate={$selecteddate}' name='site_selection'>\n";
		} else {
			echo "    <form method='post' action='?' name='site_selection'>\n";
		}
		echo "      <select name='option_page' onchange='site_selection.submit();'>\n";
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
			$current_time = time()+60*60*$timezone+$dst_value_from_current_time_sec;
			$this_week = DETERMINE_WEEK($current_time); // Set last date in drop-down to todays date

			// If 'shownextweek' is set, then add next 2 weeks to the drop-down
			($shownextweek == 1) ? $most_recent_date = $this_week['Monday']+60*60*24*7*4 : $most_recent_date = $this_week['Monday'];

			// Create a date for each Monday in the drop-down
			$i = 0;
			do
			{
				$mondays[$i] = $oldest_date+($i*60*60*24*7);
			}
			while ($mondays[$i++] < $most_recent_date);

			rsort($mondays,SORT_NUMERIC);
			echo "    <form name='dateselection' action=''> Show:\n";
			echo "      <select name='selecteddate' onchange='dateselection.submit();'>\n";
			foreach ( $mondays as $key => $value )
			{
				if (($value < $current_time) and ($value+60*60*24*7 > $current_time))
				$current_week_indicator = 'This Week ';
				else
				$current_week_indicator = gmdate("D m/d",$value) . " to " . gmdate("D m/d",$value+60*60*24*4);

				echo "        <option value='" . $value . "'";
				if ($display_week['Monday'] == $value)
				echo " selected='selected' ";
				echo ">" . $current_week_indicator . "</option>\n";
			}
			echo "      </select>\n";
			echo "      <input type='submit' id='dateselection_submit' value='go' />\n";
			echo "    </form>\n";
			echo "    <script type='text/javascript'>\n";
			echo "      <!--\n";
			echo "      document.getElementById('dateselection_submit').style.display='none'; // hides button if JS is enabled-->\n";
			echo "    </script>\n";
		}
	}

	function CREATE_PHONESHIFTS(&$phoneshifs,$date,$timezone)
	{
		$phoneshifs[$i = 0]['start'] = $date+60*60*$timezone+60*60*(8+7);    // 7:00am PST -
		$phoneshifs[$i]['end']   = $date+60*60*$timezone+60*60*(8+9.5);  // 9:30am PST
		$phoneshifs[$i]['type']  = "";

		$phoneshifs[++$i]['start'] = $date+60*60*$timezone+60*60*(8+9.5);  // 9:30am PST -
		$phoneshifs[$i]['end']   = $date+60*60*$timezone+60*60*(8+12);   // 12:00pm PST
		$phoneshifs[$i]['type']  = "";

		$phoneshifs[++$i]['start'] = $date+60*60*$timezone+60*60*(8+8);  // Cover 8:00am PST -
		$phoneshifs[$i]['end']   = $date+60*60*$timezone+60*60*(8+10);   // Cover 10:00am PST
		$phoneshifs[$i]['type']  = "Cover";

		$phoneshifs[++$i]['start'] = $date+60*60*$timezone+60*60*(8+10); // Cover 10:00am PST -
		$phoneshifs[$i]['end']   = $date+60*60*$timezone+60*60*(8+12);   // Cover 12:00pm PST
		$phoneshifs[$i]['type']  = "Cover";

		$phoneshifs[++$i]['start'] = $date+60*60*$timezone+60*60*(8+12); // Cover 12:00pm PST -
		$phoneshifs[$i]['end']   = $date+60*60*$timezone+60*60*(8+14);   // Cover 2:00pm PST
		$phoneshifs[$i]['type']  = "Cover";

		$phoneshifs[++$i]['start'] = $date+60*60*$timezone+60*60*(8+14);   // Cover 2:00pm PST -
		$phoneshifs[$i]['end']   = $date+60*60*$timezone+60*60*(8+16); // Cover 4:00pm PST
		$phoneshifs[$i]['type']  = "Cover";

		$phoneshifs[++$i]['start'] = $date+60*60*$timezone+60*60*(8+12);   // 12:00pm PST -
		$phoneshifs[$i]['end']   = $date+60*60*$timezone+60*60*(8+14.5); // 2:30pm PST
		$phoneshifs[$i]['type']  = "";

		$phoneshifs[++$i]['start'] = $date+60*60*$timezone+60*60*(8+14.5); // 2:30pm PST -
		$phoneshifs[$i]['end']   = $date+60*60*$timezone+60*60*(8+17);   // 5:00pm PST
		$phoneshifs[$i]['type']  = "";
	}

	?>