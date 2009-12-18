<?php

function SITE_NAME(&$con)
{
    $site_name = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='sitename';",&$con));
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
    // Enable Debug
    $determineweek_debug = 0;
    if ($determineweek_debug == 1) echo "<br>\n";
    if ($determineweek_debug == 1) echo "  ** Debug Mode is enabled for DETERMINE_WEEK function. **<br>\n";

    if ( $timestamp == '' )
    $timestamp = mktime();
    if ($determineweek_debug == 1) echo "  **\$timestamp: ".DEBUGDATE(0,0,$timestamp)." <br>\n";

    // Get the timestamp of 00:00 for today
    $timestamp_zero = strtotime(gmdate("d",$timestamp)." ".gmdate("M",$timestamp)." ".gmdate("Y",$timestamp)." 00:00:00 +0000");

    if ($determineweek_debug == 1) echo "  **\$timestamp_zero: ".DEBUGDATE(0,0,$timestamp_zero)." <br>\n";

    // How many days away from Monday is the timestamp_zero: shift
    if ( gmdate("l",$timestamp_zero) == 'Monday' ) $shift = 0;
    else if ( gmdate("l",$timestamp_zero) == 'Tuesday' ) $shift = -1;
    else if ( gmdate("l",$timestamp_zero) == 'Wednesday' ) $shift = -2;
    else if ( gmdate("l",$timestamp_zero) == 'Thursday' ) $shift = -3;
    else if ( gmdate("l",$timestamp_zero) == 'Friday' ) $shift = -4;
    else if ( gmdate("l",$timestamp_zero) == 'Saturday' ) $shift = -5;
    else if ( gmdate("l",$timestamp_zero) == 'Sunday' ) $shift = -6;
    if ($determineweek_debug == 1) echo "  **\$shift: $shift <br>\n";

    $current_week['Monday'] = strtotime(gmdate("d",$timestamp_zero)+$shift." ".gmdate("M",$timestamp_zero)." ".gmdate("Y",$timestamp_zero)." 00:00:00 +0000");
    $current_week['Tuesday'] = strtotime(gmdate("d",$timestamp_zero)+1+$shift." ".gmdate("M",$timestamp_zero)." ".gmdate("Y",$timestamp_zero)." 00:00:00 +0000");
    $current_week['Wednesday'] = strtotime(gmdate("d",$timestamp_zero)+2+$shift." ".gmdate("M",$timestamp_zero)." ".gmdate("Y",$timestamp_zero)." 00:00:00 +0000");
    $current_week['Thursday'] = strtotime(gmdate("d",$timestamp_zero)+3+$shift." ".gmdate("M",$timestamp_zero)." ".gmdate("Y",$timestamp_zero)." 00:00:00 +0000");
    $current_week['Friday'] = strtotime(gmdate("d",$timestamp_zero)+4+$shift." ".gmdate("M",$timestamp_zero)." ".gmdate("Y",$timestamp_zero)." 00:00:00 +0000");
    if ($determineweek_debug == 1) echo "  **\$current_week['Monday']: ".DEBUGDATE(0,0,$current_week['Monday'])." <br>\n";

    $current_week[0] = $current_week['Monday'];
    $current_week[1] = $current_week['Tuesday'];
    $current_week[2] = $current_week['Wednesday'];
    $current_week[3] = $current_week['Thursday'];
    $current_week[4] = $current_week['Friday'];

    if ($determineweek_debug == 1) echo "<br>\n";

    return $current_week;
}


function DEBUGDATE($timezone,$dst_value_from_current_time_sec,$selecteddate)
{
    $return_value = $selecteddate." [G]".gmdate("r",$selecteddate);
    if (($timezone != 0) or ($dst_value_from_current_time_sec != 0))
    $return_value = $return_value." [A]".gmdate("r",$selecteddate+60*60*$timezone+$dst_value_from_current_time_sec);
    return $return_value;
}


function SELECTDATE($timezone,$shownextweek,$selecteddate,&$con)
{
    // Enable Debug
    $selectdate_debug = 0;
    if ($selectdate_debug == 1)
    {
        echo "** Debug Mode is enabled for SELECTDATE function. **<br>\n";
        echo "**&nbsp;&nbsp;&nbsp;Keep in mind:<br>\n";
        echo "**&nbsp;&nbsp;&nbsp;date('r',gmmktime()): ".date('r',gmmktime())."<br>\n";
        echo "**&nbsp;&nbsp;&nbsp;date('r',mktime()): ".date('r',mktime())."<br>\n";
        echo "**&nbsp;&nbsp;&nbsp;gmdate('r',gmmktime()): ".gmdate('r',gmmktime())."<br>\n";
        echo "**&nbsp;&nbsp;&nbsp;gmdate('r',mktime()): ".gmdate('r',mktime())." <-- This is the correct one in GMT<br>\n";
        echo "**&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;date('r',strtotime(gmdate('r',mktime()))): ".date('r',strtotime(gmdate('r',mktime())))."<br>\n";
        echo "**&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;gmdate('r',strtotime(gmdate('r',mktime()))): ".gmdate('r',strtotime(gmdate('r',mktime())))."<br>\n";
    }

    // Set the DST time automatically from the date
    $dst_value_from_current_time_sec = date("I")*60*60; // This is a 1*60*60 if DST is set on the time
    if ($selectdate_debug == 1) echo "**The DST value is currently set to: ".$dst_value_from_current_time_sec."<br>\n";

    $display_week = DETERMINE_WEEK($selecteddate);
    if ($selectdate_debug == 1) echo "**The selecteddate that was passed to SELECTDATE is: ".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$selecteddate)."<br>\n";
    if ($selectdate_debug == 1) echo "**From DETERMINE_WEEK the Monday that was returned was: ".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$display_week['Monday'])."<br>\n";

    $oldest_schedule = mysql_query("SELECT MIN(Date) FROM Schedule,Users WHERE Users.Active = 1 AND Users.userID = Schedule.userID",$con);
    $oldest_case = mysql_query("SELECT MIN(Date) FROM Count,Users WHERE Users.Active = 1 AND Users.userID = Count.userID",$con);

    $oldest_schedule_date = mysql_fetch_array($oldest_schedule);
    if ($selectdate_debug == 1) echo "**Oldest date in the Schedule table: ".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$oldest_schedule_date['MIN(Date)'])."<br>\n";
    $oldest_case_date = mysql_fetch_array($oldest_case);
    if ($selectdate_debug == 1) echo "**Oldest date in the Count table: ".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$oldest_case_date['MIN(Date)'])."<br>\n";

    if (( $oldest_schedule_date['MIN(Date)'] == '' ) and ( $oldest_case_date['MIN(Date)'] == '' ))
    {
        echo "    No active schedule or case history found, cannot show date selection.<br />\n";
    }
    else
    {


        if ( $oldest_schedule_date['MIN(Date)'] == '' )
        {
            $schedule_date = mktime()+60*60*$timezone+$dst_value_from_current_time_sec;
            if ($selectdate_debug == 1) echo "**Oldest schedule date is blank, using current time: ".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$schedule_date)."<br>\n";
        }
        else
        {
            $schedule_date = $oldest_schedule_date['MIN(Date)'];
            if ($selectdate_debug == 1) echo "**Oldest schedule date is: ".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$schedule_date)."<br>\n";
        }

        if ( $oldest_case_date['MIN(Date)'] == '' )
        {
            $case_date = mktime()+60*60*$timezone+$dst_value_from_current_time_sec;
            if ($selectdate_debug == 1) echo "**Oldest case date is blank, using current time: ".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$case_date)."<br>\n";
        }
        else
        {
            $case_date = $oldest_case_date['MIN(Date)'];
            if ($selectdate_debug == 1) echo "**Oldest case date is: ".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$case_date)."<br>\n";
        }

        // Pick the oldest date of schedule_date and case_date and set the oldest_date equal to that date
        ($schedule_date > $case_date) ? $oldest_date = $case_date : $oldest_date = $schedule_date;
        if ($selectdate_debug == 1) echo "**Of these 2 old dates, the oldest date was found to be: ".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$oldest_date)."<br>\n";

        $this_week = DETERMINE_WEEK(mktime()+60*60*$timezone+$dst_value_from_current_time_sec); // Set last date in drop-down to todays date
        if ($selectdate_debug == 1) echo "**This week is used as the last week in the drop-down: ".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$this_week['Monday'])."<br>\n";

        //$oldest_week = DETERMINE_WEEK($oldest_date+60*60*$timezone+$dst_value_from_current_time_sec); //
        //if ($selectdate_debug == 1) echo "**Based on the oldest date that corresponding Monday: ".$oldest_week['Monday']." [G]".gmdate("r",$oldest_week['Monday'])." [A]".gmdate("r",$oldest_week['Monday']-60*60*$timezone+$dst_value_from_current_time_sec)."<br>\n";

        // If 'shownextweek' is set, then add next week to the drop-down
        ($shownextweek == 1) ? $most_recent_date = $this_week['Monday']+60*60*24*7 : $most_recent_date = $this_week['Monday'];
        if ($selectdate_debug == 1) echo "**If next week is shown, then that becomes the last week in the drop-down: ".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$this_week['Monday'])."<br>\n";

        // Create a date for each Monday in the drop-down
        $i = 0;
        do
        {
            //$mondays[$i] = $oldest_week['Monday']+($i*60*60*24*7);
            $mondays[$i] = $oldest_date+($i*60*60*24*7);
            if ($selectdate_debug == 1) echo "**Building drop-down [$i]: ".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$mondays[$i])."<br>\n";
        }
        while ($mondays[$i++] < $most_recent_date);
        rsort($mondays,SORT_NUMERIC);
        echo "    <form name='dateselection'> Show:\n";
        echo "      <select name='selecteddate' OnChange='dateselection.submit();'>\n";
        foreach ( $mondays as $key => $value )
        {
            if ($selectdate_debug == 1) echo "**If \$mondays[$key]($value G".gmdate("r",$value)." [A]".gmdate("r",$value-60*60*$timezone+$dst_value_from_current_time_sec).")=\$display_week['Monday'](".DEBUGDATE($timezone,$dst_value_from_current_time_sec,$display_week['Monday']).") then select it. <br>\n";
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