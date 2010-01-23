<?php

function PRINT_PHONE_EVENT_EMAIL($replyto,$site_name,$userID,$shift,$current_week,$selected_page,&$con)
{
  $from = MAIN_EMAILS_FROM;
  $to = $shift['useremail'];
  $type = 'phone_event'; //Type is used in several functions to determine type of email

  BUILD_PHONE_SHIFT_TABLE_HTML($currentqueue,$current_week,$userID,$selected_page,$con);

  //Create Calendar Event
  BUILD_VCALENDAR_HEADER($cal_file,-7,$type,'');
  BUILD_VEVENT($cal_file,$from,$shift,$currentqueue,$type);
  BUILD_VCALENDAR_END($cal_file);

  CREATE_MIME_BOUNDARY($mime_boundary,$shift['uid']);

  //Create Email Body (HTML)
  CREATE_EVENT_EMAIL_BODY($message,$replyto,$mime_boundary,$currentqueue,$site_name,$cal_file);
  CREATE_EMAIL_HEADER($header,$from,$replyto,$type,$mime_boundary);
  CREATE_EMAIL_SUBJECT($subject,$current_week);

  echo "<table style='border: 1px solid black;width: 1800 px;'>";
  echo "<tr><td style='border: 1px solid black;'>To:</td>";
  echo "<td style='border: 1px solid black;'>".$to."</td></tr>\n";
  echo "<tr><td style='border: 1px solid black;'>Subject:</td>";
  echo "<td style='border: 1px solid black;'>".$subject."</td></tr>\n";
  echo "<tr><td style='border: 1px solid black;'>Header:</td>";
  echo "<td style='border: 1px solid black;'><pre>".htmlentities($header)."</pre></td></tr>\n";
  echo "<tr><td style='border: 1px solid black;'>Body:</td>";
  echo "<td  style='border: 1px solid black;'><pre>".$message."</pre></td></tr></table>\n";
}

function PRINT_PHONE_EMAIL($replyto,$site_name,$userID,$shift,$current_week,$selected_page,&$con)
{

  $from = MAIN_EMAILS_FROM;
  $to = $shift['useremail'];
  $type = 'phone_email'; //Type is used in several functions to determine type of email

  BUILD_PHONE_SHIFT_TABLE_HTML($currentqueue,$current_week,$userID,$selected_page,$con);

  //Create Email Body (HTML)
  CREATE_EMAIL_BODY($message,$replyto,$currentqueue,$site_name);
  CREATE_EMAIL_HEADER($header,$from,$replyto,$type,$mime_boundary);
  CREATE_EMAIL_SUBJECT($subject,$current_week);

  echo "<table style='border: 1px solid black;width: 1800 px;'>";
  echo "<tr><td style='border: 1px solid black;'>To:</td>";
  echo "<td style='border: 1px solid black;'>".$to."</td></tr>\n";
  echo "<tr><td style='border: 1px solid black;'>Subject:</td>";
  echo "<td style='border: 1px solid black;'>".$subject."</td></tr>\n";
  echo "<tr><td style='border: 1px solid black;'>Header:</td>";
  echo "<td style='border: 1px solid black;'><pre>".htmlentities($header)."</pre></td></tr>\n";
  echo "<tr><td style='border: 1px solid black;'>Body:</td>";
  echo "<td  style='border: 1px solid black;'><pre>".$message."</pre></td></tr></table>\n";
}

?>