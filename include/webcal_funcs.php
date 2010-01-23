<?php

function UPDATE_ALL_ICS($loc,$selected_page,&$con)
{
  if(!BUILD_PHONES_ICS($loc,$selected_page,$con))
  return 0;
  else
  return 1;
}

function BUILD_PHONES_ICS($loc,$selected_page,&$con)
{
//  $cal_file =
//"BEGIN:VCALENDAR
//PRODID:-//Microsoft Corporation//Outlook 12.0 MIMEDIR//EN
//VERSION:2.0
//CALSCALE:GREGORIAN
//METHOD:REQUEST
//X-WR-CALDESC:Phone Shifts
//X-PUBLISHED-TTL:PT2H
//BEGIN:VTIMEZONE
//TZID:America/Denver
//X-LIC-LOCATION:America/Denver
//BEGIN:DAYLIGHT
//TZOFFSETFROM:-0700
//TZOFFSETTO:-0600
//TZNAME:MDT
//DTSTART:19700308T020000
//RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
//END:DAYLIGHT
//BEGIN:STANDARD
//TZOFFSETFROM:-0600
//TZOFFSETTO:-0700
//TZNAME:MST
//DTSTART:19701101T020000
//RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
//END:STANDARD
//END:VTIMEZONE";
  
  BUILD_VCALENDAR_HEADER($cal_file,-7,'ical','Phone Shifts');

  $current_time = time(); // Time is PST, but doesn't matter as this is just used to determine the current week
  $last_week = DETERMINE_WEEK($current_time-7*24*60*60);
  $next_week = DETERMINE_WEEK($current_time+7*24*60*60);
  
  BUILD_PHONE_SCHEDULE_ARRAY($schedule,$last_week['Monday'],$next_week['Friday'],$selected_page,$con);
  
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
/*        $cal_file .= "
BEGIN:VEVENT
CATEGORIES:".$shift['category']."
DTSTART;TZID=America/Denver:".$shift['start']."
DTEND;TZID=America/Denver:".$shift['end']."
DTSTAMP:".$current."
UID:phoneschedule-".$current."
CREATED:".$current."
DESCRIPTION:Phone Schedule
LAST-MODIFIED:".$current."
LOCATION:".$shift['cover']."
SEQUENCE:0
STATUS:CONFIRMED
SUMMARY:".$shift['username']."
TRANSP:OPAQUE
END:VEVENT";*/
      }
    }
  }
  BUILD_VCALENDAR_END($cal_file);
  
  if (!($file = fopen($loc."/webcal/PhoneShifts.ics","w")))
  return 0;
  else
  {
    if(!(fwrite($file,$cal_file)))
    return 0;
    else
    {
      if(!fclose($file))
      return 0;
      else
      return 1;
    }
  }
}

?>