<?php

function SEND_PHONE_EMAIL($selected_page,$current_week,$preview,&$con)
{
  if ($preview == 0)
  {
  echo "<form method='post'>\n";
  echo "<input type='submit' id='send_queue_email' value='Send email' onClick='return confirmSubmit(\"Are you sure you want to send out the schedule?\")' />\n";
  echo "Initial: <input type='radio' checked='checked' name='initial_email' value='1' />\n";
  echo "Updated: <input type='radio' name='initial_email' value='2' />\n";
  echo "</form>\n";

  echo "<form method='get' action='email_preview.php' target='_blank'>\n";
  echo "  <input type='hidden' name='preview_date' value=".$current_week[0]." />\n";
  echo "  <input type='hidden' name='preview_page' value=".$selected_page." />\n";
  echo "  <input type='submit' value='Preview' />\n";
  echo "</form>\n";
  }

  // If it was selected to send an email or an update then continue
  if (($_POST['initial_email'] == 1) or ($_POST['initial_email'] == 2) or ($preview == 1))
  {
    $activeusers = mysql_query("SELECT * FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."' ORDER BY UserName;",$con);
    $main_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites Where SiteName = 'main'",$con));
    $site_name = SITE_NAME($main_page['siteID'],$con);
    $page_name = SITE_NAME($selected_page,$con);
    $replyto = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='replyto' AND siteID='".$selected_page."';",$con));

    BUILD_PHONE_SCHEDULE_ARRAY($schedule,$current_week['Monday'],$current_week['Friday'],$selected_page,$con);

    // Send an event email to each on schedule
    if ($preview == 1)
      echo "<h2>Event Emails</h2>\n";
    if (is_array($schedule))
    {
      foreach ($schedule as $date)
      {
        foreach ($date as $userID => $userID_array)
        {
          foreach ($userID_array as $shift)
          {
            EVENT_PHONE_EMAIL($replyto['OptionValue'],$site_name,$userID,$shift,$current_week,$selected_page,$preview,$page_name,$con);
            $email_sent[$userID] = 1;
          }
        }
      }
    }

    // Send regular schedule email to those not on shift
    if ($preview == 1)
      echo "<hr />\n<h2>Regular Emails</h2>\n";
    while ( $currentuser = mysql_fetch_array($activeusers) )
    {
      if ($email_sent[$currentuser['userID']] != 1)
      PHONE_EMAIL($replyto['OptionValue'],$site_name,$currentuser['UserEmail'],$currentuser['userID'],$currentuser['UserName'],$current_week,$selected_page,$preview,$page_name,$con);
    }

    // Send an email to the CC list with the phone schedule
    if ($preview == 1)
      echo "<hr />\n<h2>CC Email</h2>\n";
    $phonecc = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='phonescc' AND siteID='".$selected_page."';",$con));
    if ($phonecc['OptionValue'] != "") // Prevent emails from being sent to CC that doesn't have an email
    {
      PHONE_EMAIL($replyto['OptionValue'],$site_name,$phonecc['OptionValue'],'','',$current_week,$selected_page,$preview,$page_name,$con);
    }

    if ($preview == 0)
    {
    echo "    <script type='text/javascript'>\n";
    echo "      <!--\n";
    echo "      document.getElementById('sending_email_status').style.display='none'; // hides sending email status if JS is enabled-->\n";
    echo "    </script>\n";
    echo "    **Finished sending emails.**<br />\n";
    }

  }
}

function EVENT_PHONE_EMAIL($replyto,$site_name,$userID,$shift,$current_week,$selected_page,$preview,$page_name,&$con)
{
  $from = MAIN_EMAILS_FROM;
  $to = $shift['useremail'];
  $type = 'phone_event'; //Type is used in several functions to determine type of email

  BUILD_PHONE_SHIFT_TABLE_HTML($currentqueue,$current_week,$userID,$selected_page,$con);
  CREATE_EMAIL_BODY($html_message,$replyto,$currentqueue,$site_name,$selected_page,$page_name,$con);

  //Create Calendar Event
  BUILD_VCALENDAR_HEADER($cal_file,-7,$type,'');
  BUILD_VEVENT($cal_file,$from,$shift,$html_message,$type);
  BUILD_VCALENDAR_END($cal_file);

  CREATE_MIME_BOUNDARY($mime_boundary,$shift['uid']);

  //Create Email Body (HTML)
  CREATE_EVENT_EMAIL_BODY($message,$replyto,$mime_boundary,$currentqueue,$site_name,$cal_file,$selected_page,$page_name,$con);
  CREATE_EMAIL_HEADER($header,$from,$replyto,$type,$mime_boundary);
  CREATE_EMAIL_SUBJECT($subject,$current_week,$type,$page_name);

  if ( $preview != 1)
  {
    $sent_mail = @mail($to,$subject,$message,$header);
    if ($sent_mail)
    {
      echo "Email event for ".$shift['start']." to ".$shift['end']." (MST) <span class='success'>sent</span> to: ".$shift['username']." &lt;".$to."&gt; <br />\n";
    }
    else
    {
      echo "Email event for ".$shift['start']." to ".$shift['end']." (MST) <span class='error'>not sent</span> to: ".$shift['username']." &lt;".$to."&gt; <br />\n";
    }
  }
  else
  {
    echo "<table style='border: 1px solid black;width: 1800 px;'>";
    echo "<tr><td style='border: 1px solid black;'>To:</td>";
    echo "<td style='border: 1px solid black;'>".$to."</td></tr>\n";
    echo "<tr><td style='border: 1px solid black;'>Subject:</td>";
    echo "<td style='border: 1px solid black;'>".$subject."</td></tr>\n";
    echo "<tr><td style='border: 1px solid black;'>Header:</td>";
    echo "<td style='border: 1px solid black;'><pre>".htmlentities($header)."</pre></td></tr>\n";
    echo "<tr><td style='border: 1px solid black;'>Body:</td>";
    echo "<td  style='border: 1px solid black;'><pre>".$message."</pre></td></tr></table>\n";
    //echo "<td  style='border: 1px solid black;'><pre>".htmlentities($message)."</pre></td></tr></table>\n";
  }
}

function PHONE_EMAIL($replyto,$site_name,$to,$userID,$user_name,$current_week,$selected_page,$preview,$page_name,&$con)
{
  $from = MAIN_EMAILS_FROM;
  $type = 'phone_email'; //Type is used in several functions to determine type of email

  BUILD_PHONE_SHIFT_TABLE_HTML($currentqueue,$current_week,$userID,$selected_page,$con);

  //Create Email Body (HTML)
  CREATE_EMAIL_BODY($message,$replyto,$currentqueue,$site_name,$selected_page,$page_name,$con);
  CREATE_EMAIL_HEADER($header,$from,$replyto,$type,$mime_boundary);
  CREATE_EMAIL_SUBJECT($subject,$current_week,$type,'');

  if ( $preview != 1)
  {
    $sent_mail = @mail($to,$subject,$message,$header);
    if ($sent_mail)
    {
      echo "Email <span class='success'>sent</span> to: ";
    }
    else
    {
      echo "Email was <span class='error'>not sent</span> to: ";
    }

    if ($user_name != '')
    echo $user_name ." &lt;";
    else
    echo " CC list &lt;";
    echo $to;
    echo "&gt;";
    echo "<br />\n";
  }
  else
  {
    echo "<table style='border: 1px solid black;width: 1800 px;'>";
    echo "<tr><td style='border: 1px solid black;'>To:</td>";
    echo "<td style='border: 1px solid black;'>".$to."</td></tr>\n";
    echo "<tr><td style='border: 1px solid black;'>Subject:</td>";
    echo "<td style='border: 1px solid black;'>".$subject."</td></tr>\n";
    echo "<tr><td style='border: 1px solid black;'>Header:</td>";
    echo "<td style='border: 1px solid black;'><pre>".htmlentities($header)."</pre></td></tr>\n";
    echo "<tr><td style='border: 1px solid black;'>Body:</td>";
    echo "<td  style='border: 1px solid black;'><pre>".$message."</pre></td></tr></table>\n";
    //echo "<td  style='border: 1px solid black;'><pre>".htmlentities($message)."</pre></td></tr></table>\n";
  }
}

function SEND_QUEUE_EMAIL($selected_page,$current_week,$preview,&$con)
{
  if ($preview == 0)
  {
  echo "<form method='post'>\n";
  echo "<input type='submit' id='send_queue_email' value='Send email' onClick='return confirmSubmit(\"Are you sure you want to send out the schedule?\")' />\n";
  echo "Initial: <input type='radio' checked='checked' name='initial_email' value='1' />\n";
  echo "Updated: <input type='radio' name='initial_email' value='2' />\n";
  echo "</form>\n";

  echo "<form method='get' action='email_preview.php' target='_blank'>\n";
  echo "  <input type='hidden' name='preview_date' value=".$current_week[0]." />\n";
  echo "  <input type='hidden' name='preview_page' value=".$selected_page." />\n";
  echo "  <input type='submit' value='Preview' />\n";
  echo "</form>\n";
  }

  // If it was selected to send an email or an update then continue
  if (($_POST['initial_email'] == 1) or ($_POST['initial_email'] == 2) or ($preview == 1))
  {
    $activeusers = mysql_query("SELECT * FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."' ORDER BY UserName;",$con);
    $main_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites Where SiteName = 'main'",$con));
    $site_name = SITE_NAME($main_page['siteID'],$con);
    $page_name = SITE_NAME($selected_page,$con);
    $replyto = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='replyto' AND siteID='".$selected_page."';",$con));

    BUILD_QUEUE_SCHEDULE_ARRAY($schedule,$current_week['Monday'],$current_week['Friday'],$selected_page,$con);

    // Send an event email to each on schedule
    if ($preview == 1)
      echo "<h2>Event Emails</h2>\n";
    foreach ($schedule as $date)
    {
      foreach ($date as $userID => $userID_array)
      {
        foreach ($userID_array as $shift)
        {
        EVENT_QUEUE_EMAIL($replyto['OptionValue'],$site_name,$userID,$shift,$current_week,$selected_page,$preview,$page_name,$con);
        $email_sent[$userID] = 1;
        }
      }
    }
    
    // Send regular schedule email to those not on shift
    if ($preview == 1)
      echo "<hr />\n<h2>Regular Emails</h2>\n";    
    while ( $currentuser = mysql_fetch_array($activeusers) )
    {
      if (($currentuser['UserEmail'] != "") and ($email_sent[$currentuser['userID']] != 1)) // Prevent emails from being sent to people that don't have an email
      {
        QUEUE_EMAIL($replyto['OptionValue'],$site_name,$currentuser['UserEmail'],$currentuser['userID'],$currentuser['UserName'],$current_week,$selected_page,$preview,$page_name,&$con);
      }
    }

    // Send an email to the CC list with the schedule
    if ($preview == 1)
      echo "<hr />\n<h2>CC Email</h2>\n";
    $queuecc = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='queuecc' AND siteID='".$selected_page."';",$con));
    if ($queuecc['OptionValue'] != "") // Prevent emails from being sent if the CC field is blank
    {
      QUEUE_EMAIL($replyto['OptionValue'],$site_name,$queuecc['OptionValue'],'','',$current_week,$selected_page,$preview,$page_name,&$con);
    }

    if ($preview == 0)
    {
    echo "    <script type='text/javascript'>\n";
    echo "      <!--\n";
    echo "      document.getElementById('sending_email_status').style.display='none'; // hides sending email status if JS is enabled-->\n";
    echo "    </script>\n";
    echo "    Finished sending emails.<br />\n";
    }
  }
}

function EVENT_QUEUE_EMAIL($replyto,$site_name,$userID,$shift,$current_week,$selected_page,$preview,$page_name,&$con)
{
  $from = MAIN_EMAILS_FROM;
  $to = $shift['useremail'];
  $type = 'queue_event'; //Type is used in several functions to determine type of email
  
  BUILD_QUEUE_SHIFT_TABLE_HTML($currentqueue,$current_week,$userID,$selected_page,$con);
  
  //Create Calendar Event
  BUILD_VCALENDAR_HEADER($cal_file,-7,$type,'');
  BUILD_VEVENT($cal_file,$from,$shift,$currentqueue,$type);
  BUILD_VCALENDAR_END($cal_file);

  CREATE_MIME_BOUNDARY($mime_boundary,$shift['uid']);

  //Create Email Body (HTML)
  CREATE_EVENT_EMAIL_BODY($message,$replyto,$mime_boundary,$currentqueue,$site_name,$cal_file,$selected_page,$page_name,$con);
  CREATE_EMAIL_HEADER($header,$from,$replyto,$type,$mime_boundary);
  CREATE_EMAIL_SUBJECT($subject,$current_week,$type,$page_name);

  if ( $preview != 1)
  {
    $sent_mail = @mail($to,$subject,$message,$header);
    if ($sent_mail)
    {
      echo "Email event for ".$shift['type']." shift was <span class='success'>sent</span> to: ";
    }
    else
    {
      echo "Email event for ".$shift['type']." shift was <span class='error'>not sent</span> to: ";
    }

    if ($shift['username'] != '')
    echo $shift['username'] ." &lt;".$to."&gt;<br />\n";
    else
    echo " CC list &lt;".$to."&gt;<br />\n";
  }
  else
  {
    echo "<table style='border: 1px solid black;width: 1800 px;'>";
    echo "<tr><td style='border: 1px solid black;'>To:</td>";
    echo "<td style='border: 1px solid black;'>".$to."</td></tr>\n";
    echo "<tr><td style='border: 1px solid black;'>Subject:</td>";
    echo "<td style='border: 1px solid black;'>".$subject."</td></tr>\n";
    echo "<tr><td style='border: 1px solid black;'>Header:</td>";
    echo "<td style='border: 1px solid black;'><pre>".htmlentities($header)."</pre></td></tr>\n";
    echo "<tr><td style='border: 1px solid black;'>Body:</td>";
    echo "<td  style='border: 1px solid black;'><pre>".$message."</pre></td></tr></table>\n";
    //echo "<td  style='border: 1px solid black;'><pre>".htmlentities($message)."</pre></td></tr></table>\n";
  }
}

function QUEUE_EMAIL($replyto,$site_name,$to,$userID,$user_name,$current_week,$selected_page,$preview,$page_name,&$con)
{
  $from = MAIN_EMAILS_FROM;
  $type = 'queue_email'; //Type is used in several functions to determine type of email
  
  BUILD_QUEUE_SHIFT_TABLE_HTML($currentqueue,$current_week,$userID,$selected_page,$con);

  //Create Email Body (HTML)
  CREATE_EMAIL_BODY($message,$replyto,$currentqueue,$site_name,$selected_page,$page_name,$con);
  CREATE_EMAIL_HEADER($header,$from,$replyto,$type,$mime_boundary);
  CREATE_EMAIL_SUBJECT($subject,$current_week,$type,$page_name);

  if ( $preview != 1)
  {
    $sent_mail = @mail($to,$subject,$message,$header);
    if ($sent_mail)
    {
      echo "Email <span class='success'>sent</span> to: ";
    }
    else
    {
      echo "Email was <span class='error'>not sent</span> to: ";
    }

    if ($user_name != '')
    echo $user_name ." &lt;";
    else
    echo " CC list &lt;";
    echo $to;
    echo "&gt;";
    echo "<br />\n";
  }
  else
  {
    echo "<table style='border: 1px solid black;width: 1800 px;'>";
    echo "<tr><td style='border: 1px solid black;'>To:</td>";
    echo "<td style='border: 1px solid black;'>".$to."</td></tr>\n";
    echo "<tr><td style='border: 1px solid black;'>Subject:</td>";
    echo "<td style='border: 1px solid black;'>".$subject."</td></tr>\n";
    echo "<tr><td style='border: 1px solid black;'>Header:</td>";
    echo "<td style='border: 1px solid black;'><pre>".htmlentities($header)."</pre></td></tr>\n";
    echo "<tr><td style='border: 1px solid black;'>Body:</td>";
    echo "<td  style='border: 1px solid black;'><pre>".$message."</pre></td></tr></table>\n";
    //echo "<td  style='border: 1px solid black;'><pre>".htmlentities($message)."</pre></td></tr></table>\n";
  }
}

function BUILD_VCALENDAR_HEADER(&$cal_file,$timezone,$type,$cal_name)
{
  if ($type == 'ical')
  {
    $refresh_timer = '
X-PUBLISHED-TTL:PT2H';
    $calendar_name = '
X-WR-CALDESC:'.$cal_name;

  }
  else //if (($type == 'phone_event') or ($type == 'queue_event'))
  {
    $refresh_timer = '';
    $calendar_name = '';
  }

  if ($timezone == -7) // MST/MDT
  {
    $cal_file = 'BEGIN:VCALENDAR
PRODID:-//Microsoft Corporation//Outlook 11.0 MIMEDIR//EN
VERSION:2.0
METHOD:PUBLISH'.$refresh_timer.$calendar_name;
    if ($type != 'queue_event') //if ($type == 'phone_event/[phone/queue_ical]')
    $cal_file .= '
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
  }
}

function BUILD_VEVENT(&$cal_file,$from,$shift,$html_message,$type)
{      
  $cal_file .='
BEGIN:VEVENT
CATEGORIES:'.$shift['category'].'
DTSTART:'.$shift['start'].'
DTEND:'.$shift['end'].'
LOCATION:'.$shift['type'].'
SEQUENCE:0
UID:'.$shift['uid'].'
DTSTAMP:'.$shift['create_date'];/*.'
X-ALT-DESC;FMTTYPE=text/html:'.$html_message;*/
  
  if ($type == 'queue_ical')
  {
  $cal_file .= '
TRANSP:TRANSPARENT
DESCRIPTION:'.$shift['username'].'
SUMMARY:'.$shift['username'].'
X-MICROSOFT-CDO-BUSYSTATUS:FREE
X-MICROSOFT-CDO-IMPORTANCE:1';
  }
  else if ($type == 'phone_ical')
  {
  $cal_file .= '
TRANSP:OPAQUE
DESCRIPTION:'.$shift['username'].'
SUMMARY:'.$shift['username'].'
X-MICROSOFT-CDO-BUSYSTATUS:BUSY
X-MICROSOFT-CDO-IMPORTANCE:1';
  }
  else if ($type == 'phone_event')
  {
  $cal_file .= '
TRANSP:OPAQUE
DESCRIPTION:Phone Shift
SUMMARY:Phone Shift
X-MICROSOFT-CDO-BUSYSTATUS:BUSY
X-MICROSOFT-CDO-IMPORTANCE:1';
  }
  else if ($type == 'queue_event')
  {
  $cal_file .= '
TRANSP:TRANSPARENT
DESCRIPTION:Queue Shift
SUMMARY:Queue Shift
X-MICROSOFT-CDO-BUSYSTATUS:FREE
X-MICROSOFT-CDO-IMPORTANCE:1';
  }
  else
  {
  $cal_file .= '
TRANSP:TRANSPARENT
DESCRIPTION:Unidentified \$type:$type
SUMMARY:Unidentified \$type:$type';
  }

  $cal_file .='
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
  $mime_boundary = "----MIME Boundary----".$uid;
}

function CREATE_EMAIL_BODY(&$message,$replyto,$currentqueue,$site_name,$siteID,$page_name,&$con)
{
  $webcal_domain = preg_replace('/http/', 'webcal', MAIN_DOMAIN);
  $phone_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites Where SiteName = 'phoneshift'",$con));
  
  
  $message .= "<html>\n";
  $message .= "<head>\n";
  $message .= "</head>\n";
  $message .= "<body style=\"margin: 5px;min-width: 800px;font-family: 'Times New Roman', Times, serif;text-align:center;\">\n";
  $message .= "<style>\n";
  $message .= "body {margin: 5px;min-width: 800px;font-family:'Times New Roman', Times, serif;text-align:center;}\n";
  $message .= "</style>\n";
  $message .= "<h3>";
  if ($_POST['initial_email'] == 2)
    $message .= "Updated: ";
  
  if ($siteID == $phone_page['siteID'])
  {
    $message .= "Phone Schedule</h3>\n";
    $message .= $currentqueue."\n";
  }
  else
  {
    $message .= "Queue Schedule for ".$page_name."</h3>\n";
    $message .= $currentqueue."<br />\n";
  }
  
  $message .= "<br /><br />View schedule in Outlook: \n";
  $message .= "<a href='".$webcal_domain."shared_calendar.php?calendar_page=".$siteID."'>Shared ".$page_name." Calendar</a><br />\n";
  $message .= "Replies will go to: ".$replyto."<br />\n";
  $message .= "<hr width='50%' />\n";
  $message .= "Sent via: ".$site_name."<br />\n";
  $message .= "<a href='".MAIN_DOMAIN."'>".MAIN_DOMAIN."</a>\n";
  $message .= "</body>\n";
  $message .= "</html>\n";
}

function CREATE_EVENT_EMAIL_BODY(&$message,$replyto,$mime_boundary,$currentqueue,$site_name,$cal_file,$selected_page,$page_name,&$con)
{
  $webcal_domain = preg_replace('/http/', 'webcal', MAIN_DOMAIN);

  $message .= "--$mime_boundary\n";
  $message .= "Content-Type: text/html; charset=UTF-8\n";
  $message .= "Content-Transfer-Encoding: 8bit\n\n";

  // This should be used but the HTML table doesn't work right.
  //CREATE_EMAIL_BODY($message,$replyto,$currentqueue,$site_name,$selected_page,$page_name,$con);
  // This is the replacement for the HTML table
  $message .= "<html>\n";
  $message .= "<head>\n";
  $message .= "</head>\n";
  $message .= "<body style=\"margin: 5px;min-width: 800px;font-family: 'Times New Roman', Times, serif;text-align:center;\">\n";
  $message .= "<style>\n";
  $message .= "body {margin: 5px;min-width: 800px;font-family:'Times New Roman', Times, serif;text-align:center;}\n";
  $message .= "</style>\n";
  $message .= "<h3>";
  if ($_POST['initial_email'] == 2)
    $message .= "Updated: ";
  
  if ($siteID == $phone_page['siteID'])
  {
    $message .= "Phone Schedule</h3>\n";
  }
  else
  {
    $message .= "Queue Schedule for ".$page_name."</h3>\n";
  }
  
  $message .= "Use the link below to view the schedule. \n";
  $message .= "<br /><br />View schedule in Outlook: \n";
  $message .= "<a href='".$webcal_domain."shared_calendar.php?calendar_page=".$siteID."'>Shared ".$page_name." Calendar</a><br />\n";
  $message .= "Replies will go to: ".$replyto."<br />\n";
  $message .= "<hr width='50%' />\n";
  $message .= "Sent via: ".$site_name."<br />\n";
  $message .= "<a href='".MAIN_DOMAIN."'>".MAIN_DOMAIN."</a>\n";
  $message .= "</body>\n";
  $message .= "</html>\n";

  $message .= "--$mime_boundary\n";
  $message .= "Content-Type: text/calendar;name=\"meeting.ics\";method=REQUEST\n";
  $message .= "Content-Transfer-Encoding: 8bit\n\n";
  $message .= $cal_file;
}

function CREATE_EMAIL_HEADER(&$header,$from,$replyto,$type,$mime_boundary)
{
  $header .= "From: <".$from.">\n";
  $header .= "Reply-To: <".$replyto.">\n";
  $header .= "MIME-Version: 1.0\n";

  if (($type == 'phone_event') or ($type == 'queue_event'))
  {
    $header .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
    $header .= "Content-class: urn:content-classes:calendarmessage\n";
  }
  else if (($type == 'phone_email') or ($type == 'queue_email'))
  {
    $header .= "Content-type:text/html;charset=iso-8859-1\r\n";
  }
}

function CREATE_EMAIL_SUBJECT(&$subject,$current_week,$type,$page_name)
{
  if (($type == 'phone_email') or ($type == 'phone_event'))
  {
    $subject = "Phone Schedule - ".gmdate("n/j",$current_week[0])." to ".gmdate("n/j",$current_week[4]);
    if ($_POST['initial_email'] == 2)
    $subject = "Updated: ".$subject;
  }
  else if (($type == 'queue_email') or ($type == 'queue_event'))
  {
    $subject = "Queue Schedule - ".$page_name." - ".gmdate("n/j",$current_week[0])." to ".gmdate("n/j",$current_week[4]);
    if ($_POST['initial_email'] == 2)
    $subject = "Updated: ".$subject;
  }
}

?>