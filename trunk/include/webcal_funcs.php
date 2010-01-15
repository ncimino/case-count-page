<?php

function BUILD_PHONES_ICS(&$con)
{	
$cal_file = 
"BEGIN:VCALENDAR
PRODID:-//Google Inc//Google Calendar 70.9054//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:REQUEST
BEGIN:VTIMEZONE
TZID:America/Denver
X-LIC-LOCATION:America/Denver
BEGIN:DAYLIGHT
TZOFFSETFROM:-0700
TZOFFSETTO:-0600
TZNAME:MDT
DTSTART:19700308T020000
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:-0600
TZOFFSETTO:-0700
TZNAME:MST
DTSTART:19701101T020000
RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
END:STANDARD
END:VTIMEZONE";

$current_time = time(); // Time is PST, but doesn't matter as this is just used to determine the current week
$last_week = DETERMINE_WEEK($current_time-7*24*60*60);
$next_week = DETERMINE_WEEK($current_time+7*24*60*60);

$sql = "SELECT *
		FROM PhoneSchedule,Users
		WHERE Date >= ".$last_week['Monday']."
		  AND Date <= ".$next_week['Friday']."
		  AND PhoneSchedule.userID=Users.userID";

$phoneschedule = mysql_query($sql,$con);

$current = preg_replace($pattern, '', gmdate("c",$current_time));

while ($currentschedule = mysql_fetch_array($phoneschedule))
{
	// Creates phone shift times
	CREATE_PHONESHIFTS($phoneshifs,$currentschedule['Date'],-7); // Create times for MST as calendar entry is MST
	
	$pattern[0] = '/-/';
	$pattern[1] = '/:/';
	$pattern[2] = '/\+.*$/';
	
	$start = preg_replace($pattern, '', gmdate("c",$phoneshifs[$currentschedule['Shift']]['start']));
	$end = preg_replace($pattern, '', gmdate("c",$phoneshifs[$currentschedule['Shift']]['end']));
	if (($currentschedule['Shift']==2) or ($currentschedule['Shift']==3))
	{
		$cover = ' (Cover)';
		$category = 'Blue Category';
	}
	else
	{
		$cover = '';
		$category = 'Red Category';
	}
	
$cal_file .= "
BEGIN:VEVENT
CATEGORIES:".$category."
DTSTART;TZID=America/Denver:".$start."
DTEND;TZID=America/Denver:".$end."
DTSTAMP:".$current."
UID:phoneschedule-".$current."
CREATED:".$current."
DESCRIPTION:Phone Schedule
LAST-MODIFIED:".$current."
LOCATION:".$cover."
SEQUENCE:0
STATUS:CONFIRMED
SUMMARY:".$currentschedule['UserName']."
TRANSP:OPAQUE
END:VEVENT";
}


$cal_file .= "
END:VCALENDAR";
	
	$file = fopen("./webcal/PhoneShifts.ics","w");
	fwrite($file,$cal_file);
	fclose($file);
}

?>