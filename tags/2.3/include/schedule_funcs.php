<?php

function SENDING_EMAIL_STATUS()
{
  if ($_POST['initial_email'] != '')
  echo "<div id='sending_email_status' style='text-align:center;top:0px;height:50px;width:100%;background:wheat;'><h3>Sending emails, please wait...</h3></div>\n\n";
}

function SCHEDULE($timezone,$selected_page,$selecteddate,&$con)
{
  // Get the dates for the selected week
  $current_week = DETERMINE_WEEK($selecteddate);
  $preview = 0; // Actually send emails, don't preview them

  echo "<h2>Schedule for ";
  echo SITE_NAME($selected_page,$con);
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
      UPDATE_DB_PHONE_SCHEDULE($current_week,$con);
      TABLE_PHONE_SCHEDULE($timezone,$selected_page,$current_week,$con);
      echo "      <br />\n";
      MANUAL_PHONE_SCHEDULE($timezone,$selected_page,$current_week,$con);
      echo "      <br />\n";
      SEND_PHONE_EMAIL($selected_page,$current_week,$preview,$con);
    }
    else
    {
      UPDATE_DB_SCHEDULE($selected_page,$current_week,$con);
      TABLE_SCHEDULE($selected_page,$current_week,$con);
      SEND_QUEUE_EMAIL($selected_page,$current_week,$preview,$con);
    }
  }
}

function UPDATE_DB_PHONE_SCHEDULE($selected_page,&$con)
{
  $data = 0;
  if ($_POST['phonesched_user']!='')
  $data++;
  if ($_POST['phonesched_date']!='')
  $data++;
  if ($_POST['phonesched_shift']!='')
  $data++;

  if ($_POST['phonesched_clear_week']=='1')
  {
    $sql="DELETE FROM PhoneSchedule WHERE Date>='".$_POST['phonesched_clear_start']."' AND Date<='".$_POST['phonesched_clear_end']."'";
    RUN_QUERY($sql,"Week was not deleted.",$con);
  }

  if ($data == 3)
  {
    if ($_POST['phonesched_shift'] == 6)
    $currentshift = mysql_query("SELECT phonescheduleID FROM PhoneSchedule WHERE userID = '".$_POST['phonesched_user']."' AND Date = '".$_POST['phonesched_date']."' AND ( Shift = '0' OR Shift = '1')",$con);
    else if ($_POST['phonesched_shift'] == 7)
    $currentshift = mysql_query("SELECT phonescheduleID FROM PhoneSchedule WHERE userID = '".$_POST['phonesched_user']."' AND Date = '".$_POST['phonesched_date']."' AND ( Shift = '4' OR Shift = '5')",$con);
    else
    $currentshift = mysql_query("SELECT phonescheduleID FROM PhoneSchedule WHERE userID = '".$_POST['phonesched_user']."' AND Date = '".$_POST['phonesched_date']."' AND Shift = '".$_POST['phonesched_shift']."'",$con);

    // DB doesn't have data, and user entered data - insert
    if ( mysql_num_rows($currentshift) == 0 )
    {
      if ($_POST['phonesched_shift'] == 6)
      {
        $sql="INSERT INTO PhoneSchedule (userID, Date, Shift) VALUES (".$_POST['phonesched_user'].",".$_POST['phonesched_date'].",0)";
        RUN_QUERY($sql,"Entry was not added.",$con);
        $sql="INSERT INTO PhoneSchedule (userID, Date, Shift) VALUES (".$_POST['phonesched_user'].",".$_POST['phonesched_date'].",1)";
        RUN_QUERY($sql,"Entry was not added.",$con);
      }
      else if ($_POST['phonesched_shift'] == 7)
      {
        $sql="INSERT INTO PhoneSchedule (userID, Date, Shift) VALUES (".$_POST['phonesched_user'].",".$_POST['phonesched_date'].",4)";
        RUN_QUERY($sql,"Entry was not added.",$con);
        $sql="INSERT INTO PhoneSchedule (userID, Date, Shift) VALUES (".$_POST['phonesched_user'].",".$_POST['phonesched_date'].",5)";
        RUN_QUERY($sql,"Entry was not added.",$con);
      }
      else
      {
        $sql="INSERT INTO PhoneSchedule (userID, Date, Shift) VALUES (".$_POST['phonesched_user'].",".$_POST['phonesched_date'].",".$_POST['phonesched_shift'].")";
        RUN_QUERY($sql,"Entry was not added.",$con);
      }
    }
    else
    {
      echo "This user already has this shift on this day.<br />\n";
    }
  }
  else if ($data > 0)
  {
    echo "You must enter data for all of the fields.<br />\n";
  }

  if ($_POST['phonesched_del_user']!='')
  {
    $sql="DELETE FROM PhoneSchedule WHERE userID='".$_POST['phonesched_del_user']."' AND Date='".$_POST['phonesched_del_date']."' AND Shift='".$_POST['phonesched_del_shift']."'";
    RUN_QUERY($sql,"Entry was not deleted.",$con);
  }

  // Build the Phone Schedule ICS file
  //  if (($_POST['phonesched_clear_week']=='1') or ($data == 3) or ($_POST['phonesched_del_user']!=''))
  //  {
  //    BUILD_PHONES_ICS($con);
  //  }
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

function MANUAL_PHONE_SCHEDULE($timezone,$selected_page,$current_week,&$con)
{
  $activeusers = mysql_query("SELECT UserName,Users.userID FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."' ORDER BY UserName;",$con);

  echo "	Manually add a phone shift:<br />\n";
  echo "    <form method='post' name='manual_phone_shift'>\n";

  echo "      <select name='phonesched_date'>\n";
  for ($i=0;$i<5;$i++)
  echo "        <option value='".$current_week[$i]."'>".gmdate("D n/j",$current_week[$i])."</option>\n";
  echo "        <option value='NULL' disabled='disabled'></option>\n";
  echo "        <option selected='selected' value='NULL' disabled='disabled'>Date</option>\n";
  echo "      </select>\n";

  // Creates phone shift times
  CREATE_PHONESHIFTS($phoneshifs,$current_week[0],$timezone);

  echo "      <select name='phonesched_shift'>\n";
  echo "        <option value='6'>Full ".gmdate("g:ia",$phoneshifs[0]['start'])." - ".gmdate("g:ia",$phoneshifs[1]['end'])."</option>\n";
  echo "        <option value='7'>Full ".gmdate("g:ia",$phoneshifs[4]['start'])." - ".gmdate("g:ia",$phoneshifs[5]['end'])."</option>\n";
  echo "        <option value='NULL' disabled='disabled'></option>\n";
  for ($shift_index=0;$shift_index<=5;$shift_index++)
  {
    echo "        <option value='".$shift_index."'>";
    if ($shift_index==2 or $shift_index==3)
    echo "Cover ";
    echo gmdate("g:ia",$phoneshifs[$shift_index]['start'])." - ".gmdate("g:ia",$phoneshifs[$shift_index]['end']);
    echo "</option>\n";
    if ($shift_index==2)
    echo "        <option value='NULL' disabled='disabled'></option>\n";
  }
  echo "        <option value='NULL' disabled='disabled'></option>\n";
  echo "        <option selected='selected' value='NULL' disabled='disabled'>Phone Shift</option>\n";
  echo "      </select>\n";

  echo "      <select name='phonesched_user'>\n";
  echo "        <option selected='selected' value='NULL' disabled='disabled'>User</option>\n";
  echo "        <option value='NULL' disabled='disabled'></option>\n";
  while ( $currentuser = mysql_fetch_array($activeusers) )
  echo "        <option value='".$currentuser['userID']."'>".$currentuser['UserName']."</option>\n";
  echo "      </select>\n";

  echo "      <input type='submit' id='form_".$postvariable."_submit' value='select' />\n";
  echo "    </form>\n";
}

function TABLE_PHONE_SCHEDULE($timezone,$selected_page,$current_week,&$con)
{    
  $sql = "SELECT UserName,Users.userID 
          FROM Users,UserSites 
          WHERE Active=1 
            AND Users.userID=UserSites.userID 
            AND siteID='".$selected_page."' 
          ORDER BY UserName;";
    
  $activeusers = mysql_query($sql,$con);

  echo "<table class='phoneshift'>\n";

  echo "<tr class='phoneshift'>\n";
  echo "  <th class='phoneshift'>Shift</th>\n";
  for ($i=0;$i<5;$i++)
  echo "  <th class='phoneshift'>".gmdate("D n/j",$current_week[$i])."</th>\n";
  echo "</tr>\n";

  // Creates phone shift times
  CREATE_PHONESHIFTS($phoneshifs,$current_week[0],$timezone);

  for ($shift_index=0;$shift_index<=5;$shift_index++)
  {
    echo "<tr class='phoneshift'>\n";
    for ($col=1; $col<=6; $col++)
    {
      if ($col==1)
      {
        echo "  <td class='phoneshift'><div class='phoneshift'>";
        echo gmdate("g:ia",$phoneshifs[$shift_index]['start'])." - ".gmdate("g:ia",$phoneshifs[$shift_index]['end']);
        if ($shift_index==2 or $shift_index==3) echo "<br />Cover";
        echo "</div></td>\n";
      }
      else
      {
        $postvariable = "phonesched_".$current_week[$col-2]."_".$shift_index;
        echo "  <td class='phoneshift'>\n";

        echo "    <form method='post' name='form_".$postvariable."'>\n";
        echo "      <select name='phonesched_user' class='phoneshift' onchange='form_".$postvariable.".submit();'>\n";
        echo "        <option selected='selected' value='NULL'></option>\n";

        mysql_data_seek($activeusers,0);
        while ( $currentuser = mysql_fetch_array($activeusers) )
        echo "        <option value='".$currentuser['userID']."'>".$currentuser['UserName']."</option>\n";

        echo "      </select>\n";

        echo "      <input type='hidden' name='phonesched_date' value='".$current_week[$col-2]."' />\n";
        echo "      <input type='hidden' name='phonesched_shift' value='".$shift_index."' />\n";

        echo "      <input type='submit' id='form_".$postvariable."_submit' value='select' />\n";
        echo "    </form>\n";

        echo "    <script type='text/javascript'>\n";
        echo "      <!--\n";
        echo "      document.getElementById('form_".$postvariable."_submit').style.display='none'; // hides button if JS is enabled-->\n";
        echo "    </script>\n";
        
        $sql = "SELECT UserName,PhoneSchedule.userID 
                FROM Users,PhoneSchedule,UserSites 
                WHERE Active=1 
                  AND Users.userID=PhoneSchedule.userID
                  AND Users.userID=UserSites.userID
                  AND UserSites.siteID=".$selected_page." 
                  AND Shift='".$shift_index."' 
                  AND Date='".$current_week[$col-2]."' 
                ORDER BY UserName;"; 

        $users_on_shift = mysql_query($sql,$con);
        while ( $current_user_on_shift = mysql_fetch_array($users_on_shift) )
        {
          echo "    <form method='post' name='form_del_".$postvariable."_".$current_user_on_shift['userID']."'>\n";
          echo "        ".$current_user_on_shift['UserName']."\n";
          echo "      <input type='hidden' name='phonesched_del_date' value='".$current_week[$col-2]."' />\n";
          echo "      <input type='hidden' name='phonesched_del_shift' value='".$shift_index."' />\n";
          echo "      <input type='hidden' name='phonesched_del_user' value='".$current_user_on_shift['userID']."' />\n";
          echo "      <input type='submit' id='form_del_".$postvariable."_".$current_user_on_shift['userID']."' value='X' />\n";
          echo "    </form>\n";
        }

        echo "  </td>\n";
      }
    }
    echo "</tr>\n";
  }

  echo "</table>\n";

  echo "<form method='post'>\n";
  echo "	<input type='submit' value='Clear all' onclick='return confirmSubmit(\"Are you sure you want to clear this weeks schedule?\")' />\n";
  echo "	<input type='hidden' name='phonesched_clear_week' value='1' />\n";
  echo "	<input type='hidden' name='phonesched_clear_start' value='".$current_week[0]."' />\n";
  echo "	<input type='hidden' name='phonesched_clear_end' value='".$current_week[4]."' />\n";
  echo "</form>\n";
  
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
    <select name='".$postvariable."' onchange='schedule.submit();'>
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

?>