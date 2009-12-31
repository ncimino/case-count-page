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
    echo $site_name['OptionValue'];
}

function TOPMENU()
{ ?>
<a href='index.php'>Home</a>
-
<a href='schedule.php'>Schedule</a>
-
<a href='users.php'>Users</a>
-
<a href='manage.php'>Manage</a>
-
<a href='index.php?logout=1'>Logout</a>
<? }

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

    $current_week['Monday'] = strtotime(gmdate("d",$timestamp_zero)+$shift." ".gmdate("M",$timestamp_zero)." ".gmdate("Y",$timestamp_zero)." 00:00:00 +0000");
    $current_week['Tuesday'] = strtotime(gmdate("d",$timestamp_zero)+1+$shift." ".gmdate("M",$timestamp_zero)." ".gmdate("Y",$timestamp_zero)." 00:00:00 +0000");
    $current_week['Wednesday'] = strtotime(gmdate("d",$timestamp_zero)+2+$shift." ".gmdate("M",$timestamp_zero)." ".gmdate("Y",$timestamp_zero)." 00:00:00 +0000");
    $current_week['Thursday'] = strtotime(gmdate("d",$timestamp_zero)+3+$shift." ".gmdate("M",$timestamp_zero)." ".gmdate("Y",$timestamp_zero)." 00:00:00 +0000");
    $current_week['Friday'] = strtotime(gmdate("d",$timestamp_zero)+4+$shift." ".gmdate("M",$timestamp_zero)." ".gmdate("Y",$timestamp_zero)." 00:00:00 +0000");
    
    $current_week[0] = $current_week['Monday'];
    $current_week[1] = $current_week['Tuesday'];
    $current_week[2] = $current_week['Wednesday'];
    $current_week[3] = $current_week['Thursday'];
    $current_week[4] = $current_week['Friday'];

    return $current_week;
}

function SELECTSITE($selected_page,&$con)
{
	$pages_query = mysql_query("SELECT Options.siteID,OptionValue FROM Sites,Options WHERE Active='1' AND OptionName='sitename' AND Options.siteID=Sites.siteID;",$con);
	
	echo "    <form method='post' name='site_selection'>\n";
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

function SELECTDATE($timezone,$shownextweek,$selecteddate,&$con)
{

    // Set the DST time automatically from the date
    $dst_value_from_current_time_sec = date("I")*60*60; // This is a 1*60*60 if DST is set on the time
    $display_week = DETERMINE_WEEK($selecteddate);

    $oldest_schedule = mysql_query("SELECT MIN(Date) FROM Schedule,Users WHERE Users.Active = 1 AND Users.userID = Schedule.userID",$con);
    $oldest_case = mysql_query("SELECT MIN(Date) FROM Count,Users WHERE Users.Active = 1 AND Users.userID = Count.userID",$con);

    $oldest_schedule_date = mysql_fetch_array($oldest_schedule);
    $oldest_case_date = mysql_fetch_array($oldest_case);

    if (( $oldest_schedule_date['MIN(Date)'] == '' ) and ( $oldest_case_date['MIN(Date)'] == '' ))
    {
        echo "    No active schedule or case history found, cannot show date selection.<br />\n";
    }
    else
    {


        if ( $oldest_schedule_date['MIN(Date)'] == '' )
        {
            $schedule_date = mktime()+60*60*$timezone+$dst_value_from_current_time_sec;
        }
        else
        {
            $schedule_date = $oldest_schedule_date['MIN(Date)'];
        }

        if ( $oldest_case_date['MIN(Date)'] == '' )
        {
            $case_date = mktime()+60*60*$timezone+$dst_value_from_current_time_sec;
        }
        else
        {
            $case_date = $oldest_case_date['MIN(Date)'];
        }

        // Pick the oldest date of schedule_date and case_date and set the oldest_date equal to that date
        ($schedule_date > $case_date) ? $oldest_date = $case_date : $oldest_date = $schedule_date;
        $this_week = DETERMINE_WEEK(time()+60*60*$timezone+$dst_value_from_current_time_sec); // Set last date in drop-down to todays date

        // If 'shownextweek' is set, then add next week to the drop-down
        ($shownextweek == 1) ? $most_recent_date = $this_week['Monday']+60*60*24*7 : $most_recent_date = $this_week['Monday'];

        // Create a date for each Monday in the drop-down
        $i = 0;
        do
        {
            //$mondays[$i] = $oldest_week['Monday']+($i*60*60*24*7);
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
            echo ">" . substr(gmdate("l",$value),0,3) . " " . gmdate("m/d",$value) . " - " . substr(gmdate("l",$value+60*60*24*4),0,3) . " " . gmdate("m/d",$value+60*60*24*4) . "</option>\n";
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