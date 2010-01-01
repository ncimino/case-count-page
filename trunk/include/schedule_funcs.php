<?php

function SCHEDULE($timezone,$selected_page,$selecteddate,&$con)
{
  // Get the dates for the selected week
  $current_week = DETERMINE_WEEK($selecteddate);

  echo "<h2>Schedule for ";
  SITE_NAME($selected_page,$con);
  echo "</h2>\n";

  // Determine if any active users exist
  $activeusers = mysql_query("SELECT userID FROM Users WHERE Active=1;",$con);
  if ( mysql_num_rows($activeusers) == 0 )
  {
    echo "      No active users found. You need to add users.<br />\n";
  }
  else
  {
    $phone_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites WHERE SiteName='phoneshift';",$con));
    if ($selected_page == $phone_page['siteID'])
    {
      //UPDATE_DB_PHONE_SCHEDULE($current_week,$con);
      TABLE_PHONE_SCHEDULE($timezone,$selected_page,$current_week,$con);
      //SEND_PHONE_EMAIL($current_week,$con);
    }
    else
    {
      UPDATE_DB_SCHEDULE($selected_page,$current_week,$con);
      TABLE_SCHEDULE($selected_page,$current_week,$con);
      SEND_QUEUE_EMAIL($selected_page,$current_week,$con);
    }
  }
}

function UPDATE_DB_SCHEDULE($selected_page,$current_week,&$con)
{
  $activeusers = mysql_query("SELECT Users.userID FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."'",$con);
  while ( $currentuser = mysql_fetch_array($activeusers) )
  {
    for ($i=0; $i<5; $i++)
    {
      $postvariable = "sched_".$current_week[$i]."_".$currentuser['userID'];

      $currentshift = mysql_query("SELECT Shift FROM Schedule WHERE userID = '".$currentuser['userID']."' AND Date = '".$current_week[$i]."' AND siteID='".$selected_page."'",$con);

      if ($_POST[$postvariable] != '')
      {
        // DB has data, but user entered blank - delete
        if ( (mysql_num_rows($currentshift) != 0) and ($_POST[$postvariable] == '0') )
        {
          $sql="DELETE FROM Schedule WHERE userID='".$currentuser['userID']."' AND Date='".$current_week[$i]."' AND siteID='".$selected_page."'";
          RUN_QUERY($sql,"Entry was not deleted.",$con);
        }
        // DB has data, and user entered data - update
        if ( (mysql_num_rows($currentshift) != 0) and ($_POST[$postvariable] != '0') )
        {
          $sql="UPDATE Schedule SET Shift = '".$_POST[$postvariable]."' WHERE userID = '".$currentuser['userID']."' AND Date = '".$current_week[$i]."'";
          RUN_QUERY($sql,"Entry was not updated.",$con);
        }
        // DB doesn't have data, and user entered data - insert
        if ( (mysql_num_rows($currentshift) == 0) and ($_POST[$postvariable] != '0') )
        {
          $sql="INSERT INTO Schedule (userID, Date, Shift, siteID) VALUES (".$currentuser['userID'].",".$current_week[$i].",".$_POST[$postvariable].",".$selected_page.")";
          RUN_QUERY($sql,"Entry was not added.",$con);
        }
      }
    }
  }
}

function TABLE_PHONE_SCHEDULE($timezone,$selected_page,$current_week,&$con)
{
  $activeusers = mysql_query("SELECT UserName,Users.userID FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."' ORDER BY UserName;",$con);
  echo "<table>\n";
  echo "<tr>\n";
  echo "  <th>Shift</th>\n";
  for ($i=0;$i<5;$i++)
  echo "  <th>".gmdate("D",$current_week[$i])."<br />".gmdate("n/j",$current_week[$i])."</th>\n";
  echo "</tr>\n";

  // Creates phone shift times
  $phoneshifs[0]['start'] = date("h:ia",$current_week[0]+60*60*$timezone-60*60*8);
  $phoneshifs[1]['start'] = date("h:ia",$current_week[0]);
  echo "pho=".$phoneshifs[0]['start'];
  echo "pho=".$phoneshifs[1]['start'];
  $phoneshifs['PST'] = -8;
  
  while ( $currentuser = mysql_fetch_array($activeusers) )
  {
    echo "<tr>\n";
    for ($col=1; $col<=7; $col++)
    {
      if ($col==1) echo "  <td>".$currentuser['UserName']."</td>";
      else if ($col==7) {
        $currentshift = mysql_fetch_array(mysql_query("SELECT SUM(Shift) FROM Schedule WHERE userID = '".$currentuser['userID']."' AND Date >= '".$current_week[0]."' AND Date <= '".$current_week[4]."'",$con));
        echo "  <td>" . $currentshift['SUM(Shift)'] / 2 . "</td>\n";
      }
      else {
        $postvariable = "sched_".$current_week[$col-2]."_".$currentuser['userID'];
        $currentshift = mysql_fetch_array(mysql_query("SELECT Shift FROM Schedule WHERE userID = '".$currentuser['userID']."' AND Date = '".$current_week[$col-2]."'",$con));
        echo "  <td>
      <select name='".$postvariable."' OnChange='schedule.submit();'>
      <option value='2' "; 
        if ( $currentshift['Shift'] == 2 ) echo "selected='selected'";
        echo ">FULL</option>
      <option value='1' "; 
        if ( $currentshift['Shift'] == 1 ) echo "selected='selected'";
        echo ">HALF</option>
      <option value='0' "; 
        if ( $currentshift['Shift'] == '' ) echo "selected='selected'";
        echo "></option></select>\n";
        echo "  </td>\n";
      }
    }
    echo "</tr>\n";
  }

  echo "</table>\n";
  echo "<input type='submit' id='schedule_submit' value='submit'>\n";
  echo "</form>\n";
  echo "    <script type='text/javascript'>\n";
  echo "      <!--\n";
  echo "      document.getElementById('schedule_submit').style.display='none'; // hides button if JS is enabled-->\n";
  echo "    </script>\n";
}

function TABLE_SCHEDULE($selected_page,$current_week,&$con)
{
  $activeusers = mysql_query("SELECT UserName,Users.userID FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."' ORDER BY UserName;",$con);
  echo "<form method='post' name='schedule'><table>\n";
  echo "<tr>\n";
  echo "  <th>Name</th>\n";
  for ($i=0;$i<5;$i++)
  echo "  <th>".gmdate("D",$current_week[$i])."<br />".gmdate("n/j",$current_week[$i])."</th>\n";
  echo "  <th>Total</th>";
  echo "</tr>\n";

  while ( $currentuser = mysql_fetch_array($activeusers) )
  {
    echo "<tr>\n";
    for ($col=1; $col<=7; $col++)
    {
      if ($col==1) echo "  <td>".$currentuser['UserName']."</td>";
      else if ($col==7) {
        $currentshift = mysql_fetch_array(mysql_query("SELECT SUM(Shift) FROM Schedule WHERE userID = '".$currentuser['userID']."' AND Date >= '".$current_week[0]."' AND Date <= '".$current_week[4]."' AND siteID='".$selected_page."'",$con));
        echo "  <td>" . $currentshift['SUM(Shift)'] / 2 . "</td>\n";
      }
      else {
        $postvariable = "sched_".$current_week[$col-2]."_".$currentuser['userID'];
        $currentshift = mysql_fetch_array(mysql_query("SELECT Shift FROM Schedule WHERE userID = '".$currentuser['userID']."' AND Date = '".$current_week[$col-2]."' AND siteID='".$selected_page."'",$con));
        echo "  <td>
    <select name='".$postvariable."' OnChange='schedule.submit();'>
    <option value='2' "; 
        if ( $currentshift['Shift'] == 2 ) echo "selected='selected'";
        echo ">FULL</option>
    <option value='1' "; 
        if ( $currentshift['Shift'] == 1 ) echo "selected='selected'";
        echo ">HALF</option>
    <option value='0' "; 
        if ( $currentshift['Shift'] == '' ) echo "selected='selected'";
        echo "></option></select>\n";
        echo "  </td>\n";
      }
    }
    echo "</tr>\n";
  }

  echo "</table>\n";
  echo "<input type='submit' id='schedule_submit' value='submit'>\n";
  echo "</form>\n";
  echo "    <script type='text/javascript'>\n";
  echo "      <!--\n";
  echo "      document.getElementById('schedule_submit').style.display='none'; // hides button if JS is enabled-->\n";
  echo "    </script>\n";
}

function SEND_QUEUE_EMAIL($current_week,&$con)
{
  echo "<form method='post'>\n";
  echo "<input type='submit' id='send_queue_email' value='Send email' onClick='return confirmSubmit(\"Are you sure you want to send out the schedule?\")' />\n";
  echo "Initial: <input type='radio' checked='checked' name='initial_email' value='1' />\n";
  echo "Updated: <input type='radio' name='initial_email' value='2' />\n";
  echo "</form>\n";

  // If it was selected to send an email or an update then continue
  if (($_POST['initial_email'] == 1) or ($_POST['initial_email'] == 2))
  {
    $activeusers = mysql_query("SELECT UserEmail,userID FROM Users WHERE Active=1;",$con);
    $site_name = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='sitename';",$con));
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
          $shift = mysql_fetch_array(mysql_query("SELECT COUNT(Shift) FROM Users,Schedule WHERE Users.userID = Schedule.userID AND Users.Active = 1 AND Date = ".$current_week[$i],$con));
          $shiftcount[$i] = $shift['COUNT(Shift)'];
          $currentday = mysql_query("SELECT UserName,Shift,Users.userID FROM Users,Schedule WHERE Schedule.Date = ".$current_week[$i]." AND Users.userID = Schedule.userID AND Users.Active = 1",$con);
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
    $queuecc = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='queuecc';",$con));
    if ($queuecc['OptionValue'] != "") // Prevent emails from being sent to people that don't have an email
    {
      $currentqueue = "<table style=\"border-collapse:collapse;width:50em;\">";

      $currentqueue .= "      <tr>\n";
      for ($i=0;$i<5;$i++)
      $currentqueue .= "        <th style=\"border:0px none;text-align:center;width:10em;\">".gmdate("D",$current_week[$i])."&nbsp;".gmdate("n/j",$current_week[$i])."</th>\n";
      $currentqueue .= "      </tr>\n";

      for ($i = 0; $i <= 4; $i++)
      {
        $shift = mysql_fetch_array(mysql_query("SELECT COUNT(Shift) FROM Users,Schedule WHERE Users.userID = Schedule.userID AND Users.Active = 1 AND Date = ".$current_week[$i],$con));
        $shiftcount[$i] = $shift['COUNT(Shift)'];
        $currentday = mysql_query("SELECT UserName,Shift,Users.userID FROM Users,Schedule WHERE Schedule.Date = ".$current_week[$i]." AND Users.userID = Schedule.userID AND Users.Active = 1",$con);
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
      if (mail($to,$subject,$message,$headers))
      echo "Email <span class='success'>sent</span> to CC list:".$to."<br />\n";
      else
      echo "Email was <span class='error'>not sent</span> to CC list:".$to."<br />\n";
    }
  }
}

?>