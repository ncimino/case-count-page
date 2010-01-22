<?php

function SEND_PHONE_EMAIL($selected_page,$current_week,&$con)
{
  echo "<form method='post'>\n";
  echo "<input type='submit' id='send_queue_email' value='Send email' onClick='return confirmSubmit(\"Are you sure you want to send out the schedule?\")' />\n";
  echo "Initial: <input type='radio' checked='checked' name='initial_email' value='1' />\n";
  echo "Updated: <input type='radio' name='initial_email' value='2' />\n";
  echo "</form>\n";

  // If it was selected to send an email or an update then continue
  if (($_POST['initial_email'] == 1) or ($_POST['initial_email'] == 2))
  {
    // Build the ICS for all when the schedule is sent
    echo "Updating ICS file for WebCal...";
    //if (UPDATE_ALL_ICS('.',$selected_page,$con))
    if (1)
    echo " ICS file for WebCal <span class='success'>successfully</span> updated.<br />\n";
    else
    echo " ICS file for WebCal <span class='error'>failed</span> to update.<br />\n";

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
          EVENT_PHONE_EMAIL($replyto['OptionValue'],$site_name['OptionValue'],$userID,$shift,$current_week,$selected_page,$con);
          $email_sent[$userID] = 1;
        }
      }
    }
    
    // Send regular schedule email to those not on shift
    while ( $currentuser = mysql_fetch_array($activeusers) )
    {
      if ($email_sent[$currentuser['userID']] != 1)
        PHONE_EMAIL($replyto['OptionValue'],$site_name['OptionValue'],$currentuser['UserEmail'],$currentuser['userID'],$current_week,$selected_page,$con);
    }

    // Send an email to the CC list with the phone schedule
    $phonecc = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='phonescc' AND siteID='".$selected_page."';",$con));
    if ($phonecc['OptionValue'] != "") // Prevent emails from being sent to CC that doesn't have an email
    {
      PHONE_EMAIL($replyto['OptionValue'],$site_name['OptionValue'],$phonecc['OptionValue'],'',$current_week,$selected_page,$con);
    }
    
    echo "    <script type='text/javascript'>\n";
    echo "      <!--\n";
    echo "      document.getElementById('sending_email_status').style.display='none'; // hides sending email status if JS is enabled-->\n";
    echo "    </script>\n";
    echo "    Finished sending emails.<br />\n";
    
  }
}

function EVENT_PHONE_EMAIL($replyto,$site_name,$userID,$shift,$current_week,$selected_page,&$con)
{
  $from = MAIN_EMAILS_FROM;
  $to = $shift['useremail'];
  
  //Create Email subject
  $subject = "Phone Schedule - ".gmdate("n/j",$current_week[0])." to ".gmdate("n/j",$current_week[4]);
  if ($_POST['initial_email'] == 2)
  $subject = "Updated: ".$subject;
  
  BUILD_PHONE_SHIFT_TABLE_HTML($currentqueue,$current_week,$userID,$selected_page,$con);
  
  //Create Calendar Event
  BUILD_VCALENDAR_HEADER($cal_file,-7,0,'');
  BUILD_VEVENT($cal_file,$from,$shift);
  BUILD_VCALENDAR_END($cal_file);
  
  CREATE_MIME_BOUNDARY($mime_boundary,$userID.$shift['start'].rand()); 

  //Create Email Body (HTML)
  CREATE_EVENT_EMAIL_BODY($message,$mime_boundary,$currentqueue,$site_name,$cal_file);
  CREATE_EVENT_EMAIL_HEADER($header,$from,$replyto,$mime_boundary);
  
  $sent_mail = @mail($to,$subject,$message,$header);
  if ($sent_mail)
  {
    echo "Email event for ".$shift['start']." to ".$shift['end']." (MST) <span class='success'>sent</span> to:".$to."<br />\n";
  }
  else
  {
    echo "Email event for ".$shift['start']." to ".$shift['end']." <span class='error'>not sent</span> to:".$to."<br />\n";
  }
}

function PHONE_EMAIL($replyto,$site_name,$to,$userID,$current_week,$selected_page,&$con)
{
  BUILD_PHONE_SHIFT_TABLE_HTML($currentqueue,$current_week,$userID,$selected_page,$con);
    
  $webcal_domain = preg_replace('/http/', '', MAIN_DOMAIN);

  $subject = "Phone Schedule - ".gmdate("n/j",$current_week[0])." to ".gmdate("n/j",$current_week[4]);
  if ($_POST['initial_email'] == 2)
  $subject = "Updated: ".$subject;
  $message = "<html>\n";
  $message .= "<body style=\"margin: 5px;min-width: 800px;font-family: 'Times New Roman', Times, serif;text-align:center;\">\n";
  $message .= "<style>\n";
  $message .= "body {margin: 5px;min-width: 800px;font-family:'Times New Roman', Times, serif;text-align:center;}\n";
  $message .= "</style>\n";
  $message .= "<h3>";
  if ($_POST['initial_email'] == 2)
  $message .= "Updated: ";
  $message .= "Phone Schedule</h3>\n";
  $message .= $currentqueue."\n";
  $message .= "<br /><br />View schedule in Outlook:<br />\n";
  $message .= "<a href='webcal".$webcal_domain."webcal/PhoneShifts.ics'>webcal".$webcal_domain."webcal/PhoneShifts.ics</a><br /><br />\n";
  $message .= "Replies will go to: ".$replyto."<br />\n";
  $message .= "<hr width='50%' />\n";
  $message .= "Sent via: ".$site_name."<br />\n";
  $message .= "<a href='".MAIN_DOMAIN."'>".MAIN_DOMAIN."</a>\n";
  $message .= "</body>\n";
  $message .= "</html>";
  $from = MAIN_EMAILS_FROM;
  $headers = "MIME-Version: 1.0" . "\r\n";
  $headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
  $headers .= 'From: '.$from."\r\n";
  $headers .= "Reply-To: ".$replyto."\r\n";
  //$headers .= "Return-Path: ".$replyto."\r\n";
  if (mail($to,$subject,$message,$headers))
  {
    echo "Email <span class='success'>sent</span> to:".$to."<br />\n";
  }
  else
  {
    echo "Email was <span class='error'>not sent</span> to:".$to."<br />\n";
  }
}

function SEND_QUEUE_EMAIL($selected_page,$current_week,&$con)
{
  echo "<form method='post'>\n";
  echo "<input type='submit' id='send_queue_email' value='Send email' onClick='return confirmSubmit(\"Are you sure you want to send out the schedule?\")' />\n";
  echo "Initial: <input type='radio' checked='checked' name='initial_email' value='1' />\n";
  echo "Updated: <input type='radio' name='initial_email' value='2' />\n";
  echo "</form>\n";

  // If it was selected to send an email or an update then continue
  if (($_POST['initial_email'] == 1) or ($_POST['initial_email'] == 2))
  {
    // Build the ICS for all when the schedule is sent
    echo "Updating ICS file for WebCal...<br />\n";
    if (UPDATE_ALL_ICS('.',$selected_page,$con))
    echo "ICS file for WebCal <span style='success'>successfully</span> updated.<br />\n";
    else
    echo "ICS file for WebCal <span style='error'>failed</span> to update.<br />\n";
      
    $activeusers = mysql_query("SELECT * FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."' ORDER BY UserName;",$con);
    $site_name = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options,Sites WHERE OptionName='sitename' AND Options.siteID=Sites.siteID AND SiteName='main';",$con));
    $replyto = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='replyto' AND siteID='".$selected_page."';",$con));
    while ( $currentuser = mysql_fetch_array($activeusers) )
    {
      if ($currentuser['UserEmail'] != "") // Prevent emails from being sent to people that don't have an email
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
            if (($namesAndShifts[$col-1][$row-1]['userID'] == $currentuser['userID'] ) and ($currentuser['userID'] != ''))
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

        $to = $currentuser['UserEmail'];

        $subject = "Queue Schedule - ".gmdate("n/j",$current_week[0])." to ".gmdate("n/j",$current_week[4]);
        if ($_POST['initial_email'] == 2)
        $subject = "Updated: ".$subject;
        $message = "<html>\n";
        $message .= "<body style=\"margin: 5px;min-width: 800px;font-family: 'Times New Roman', Times, serif;\">\n";
        $message .= "<style>\n";
        $message .= "body {margin: 5px;min-width: 800px;font-family:'Times New Roman', Times, serif;text-align:center;}\n";
        $message .= "</style>\n";
        $message .= "<h3>";
        if ($_POST['initial_email'] == 2)
        $message .= "Updated: ";
        $message .= "Queue Schedule</h3>\n";
        $message .= $currentqueue."\n";
        $message .= "<br />\n";
        $message .= "<hr width='50%' />\n";
        $message .= "Sent via: ".$site_name['OptionValue']."<br />\n";
        $message .= "<a href='".MAIN_DOMAIN."'>".MAIN_DOMAIN."</a>\n";
        $message .= "</body>\n";
        $message .= "</html>";
        $from = MAIN_EMAILS_FROM;
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
        $headers .= 'From: '.$from."\r\n";
        $headers .= "Reply-To: ".$replyto['OptionValue']."\r\n";
        //$headers .= "Return-Path: ".$replyto['OptionValue']."\r\n";
        if (mail($to,$subject,$message,$headers))
        {
          echo "Email <span class='success'>sent</span> to:".$to."<br />\n";
        }
        else
        {
          echo "Email was <span class='error'>not sent</span> to:".$to."<br />\n";
        }
      }
    }

    // Send an email to the CC list with the schedule
    $queuecc = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='queuecc' AND siteID='".$selected_page."';",$con));
    if ($queuecc['OptionValue'] != "") // Prevent emails from being sent to people that don't have an email
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
          $currentqueue .= "\">\n";
          $currentqueue .= $namesAndShifts[$col-1][$row-1]['UserName'];
          if ($namesAndShifts[$col-1][$row-1]['Shift'] == 1)
          $currentqueue .= "&nbsp;(.5)";
          $currentqueue .= "</td>\n";
        }
        $currentqueue .= "      </tr>\n";
      }
      $currentqueue .= "    </table>\n";

      $to = $queuecc['OptionValue'];

      $subject = "Queue Schedule - ".gmdate("n/j",$current_week[0])." to ".gmdate("n/j",$current_week[4]);
      if ($_POST['initial_email'] == 2)
      $subject = "Updated: ".$subject;
      $message = "<html>\n";
      $message .= "<body style=\"margin: 5px;min-width: 800px;font-family: 'Times New Roman', Times, serif;\">\n";
      $message .= "<style>\n";
      $message .= "body {margin: 5px;min-width: 800px;font-family:'Times New Roman', Times, serif;text-align:center;}\n";
      $message .= "</style>\n";
      $message .= "<h3>";
      if ($_POST['initial_email'] == 2)
      $message .= "Updated: ";
      $message .= "Queue Schedule</h3>\n";
      $message .= $currentqueue."\n";
      $message .= "<br />\n";
      $message .= "<hr width='50%' />\n";
      $message .= "Sent via: ".$site_name['OptionValue']."<br />\n";
      $message .= "<a href='".MAIN_DOMAIN."'>".MAIN_DOMAIN."</a>\n";
      $message .= "</body>\n";
      $message .= "</html>";
      $from = MAIN_EMAILS_FROM;
      $headers = "MIME-Version: 1.0" . "\r\n";
      $headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
      $headers .= 'From: '.$from."\r\n";
      $headers .= "Reply-To: ".$replyto['OptionValue']."\r\n";
      //$headers .= "Return-Path: ".$replyto['OptionValue']."\r\n";
      if (mail($to,$subject,$message,$headers))
      echo "Email <span class='success'>sent</span> to CC list:".$to."<br />\n";
      else
      echo "Email was <span class='error'>not sent</span> to CC list:".$to."<br />\n";
    }
    
    echo "    <script type='text/javascript'>\n";
    echo "      <!--\n";
    echo "      document.getElementById('sending_email_status').style.display='none'; // hides sending email status if JS is enabled-->\n";
    echo "    </script>\n";
    echo "    Finished sending emails.<br />\n";
  }
}

function BUILD_VCALENDAR_HEADER(&$cal_file,$timezone,$ics,$name)
{
  if ($ics == 1)
  {
    $refresh_timer = '
X-PUBLISHED-TTL:PT2H';
    $calendar_name = '
X-WR-CALDESC:'.$name;
  }
  else
  {
    $refresh_timer = '';
    $calendar_name = '';
  }
  
  if ($timezone == -7) // MST/MDT
  {
    $cal_file = 'BEGIN:VCALENDAR
PRODID:-//Microsoft Corporation//Outlook 11.0 MIMEDIR//EN
VERSION:2.0
METHOD:PUBLISH'.$refresh_timer.$calendar_name.'
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
END:VTIMEZONE';

    /* Old stuff
    $cal_file .=
"BEGIN:VCALENDAR
PRODID:-//Microsoft Corporation//Outlook 12.0 MIMEDIR//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:REQUEST".$refresh_timer.$calendar_name."
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
END:VTIMEZONE";*/
  }
}

function BUILD_VEVENT(&$cal_file,$from,$shift)
{
  $cal_file .='
BEGIN:VEVENT
ORGANIZER:MAILTO:'.$from.'
DTSTART:'.$shift['start'].'
DTEND:'.$shift['end'].'
LOCATION:'.$shift['cover'].'
TRANSP:OPAQUE
SEQUENCE:0
UID:'.$shift['uid'].'
DTSTAMP:'.$shift['create_date'].'
DESCRIPTION:Phone Schedule'."\n\n".'
SUMMARY:Phone Schedule
PRIORITY:5
CLASS:PUBLIC
END:VEVENT';
}

function BUILD_VCALENDAR_END(&$cal_file)
{
  $cal_file .= "
END:VCALENDAR";
}

function CREATE_MIME_BOUNDARY(&$mime_boundary,$uid)
{
  $mime_boundary = "----Meeting Booking----".$uid;
}

function CREATE_EVENT_EMAIL_BODY(&$message,$mime_boundary,$currentqueue,$site_name,$cal_file)
{
  $webcal_domain = preg_replace('/http/', '/webcal/', MAIN_DOMAIN);
  
  $message .= "--$mime_boundary\n";
  $message .= "Content-Type: text/html; charset=UTF-8\n";
  $message .= "Content-Transfer-Encoding: 8bit\n\n";
  
  $message .= "<html>\n";
  $message .= "<body style=\"margin: 5px;min-width: 800px;font-family: 'Times New Roman', Times, serif;text-align:center;\">\n";
  $message .= "<style>\n";
  $message .= "body {margin: 5px;min-width: 800px;font-family:'Times New Roman', Times, serif;text-align:center;}\n";
  $message .= "</style>\n";
  $message .= "<h3>";
  if ($_POST['initial_email'] == 2)
  $message .= "Updated: ";
  $message .= "Phone Schedule</h3>\n";
  $message .= $currentqueue."\n";
  $message .= "<br /><br />View schedule in Outlook:<br />\n";
  $message .= "<a href='".$webcal_domain."webcal/PhoneShifts.ics'>".$webcal_domain."webcal/PhoneShifts.ics</a><br /><br />\n";
  $message .= "Replies will go to: ".$replyto."<br />\n";
  $message .= "<hr width='50%' />\n";
  $message .= "Sent via: ".$site_name."<br />\n";
  $message .= "<a href='".MAIN_DOMAIN."'>".MAIN_DOMAIN."</a>\n";
  $message .= "</body>\n";
  $message .= "</html>\n";
  $message .= "--$mime_boundary\n";
  $message .= 'Content-Type: text/calendar;name="meeting.ics";method=REQUEST\n';
  $message .= "Content-Transfer-Encoding: 8bit\n\n";
  $message .= $cal_file;  
}

function CREATE_EVENT_EMAIL_HEADER(&$header,$from,$replyto,$mime_boundary)
{
  $header .= "From: <".$from.">\n";
  $header .= "Reply-To: <".$replyto.">\n";  
  $header .= "MIME-Version: 1.0\n";
  $header .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
  $header .= "Content-class: urn:content-classes:calendarmessage\n";
} 
  
?>