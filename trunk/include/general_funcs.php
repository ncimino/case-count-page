<?php

function TOPMENU()
{ ?>
<a href='index.php'>Home</a> -
<a href='schedule.php'>Schedule</a> -
History -
<a href='users.php'>Users</a> -
Manage -
<a href='index.php?logout=1'>Logout</a>
<? }


function DETERMINE_WEEK($timestamp)
{
  if ( $timestamp == '' ) $timestamp = mktime();
  $timestamp_zero = mktime(0,0,0,gmdate("m",$timestamp),gmdate("d",$timestamp),gmdate("Y",$timestamp)); // Get the timestamp of 00:00 for today
  if ( date("l",$timestamp_zero) == 'Monday' ) $shift = 0;
  else if ( date("l",$timestamp_zero) == 'Tuesday' ) $shift = -1;
  else if ( date("l",$timestamp_zero) == 'Wednesday' ) $shift = -2;
  else if ( date("l",$timestamp_zero) == 'Thursday' ) $shift = -3;
  else if ( date("l",$timestamp_zero) == 'Friday' ) $shift = -4;
  else if ( date("l",$timestamp_zero) == 'Saturday' ) $shift = -5;
  else if ( date("l",$timestamp_zero) == 'Sunday' ) $shift = -6;
  $current_week['Monday'] = mktime(0,0,0,gmdate("m",$timestamp_zero),gmdate("d",$timestamp_zero)+$shift,gmdate("Y",$timestamp_zero));
  $current_week['Tuesday'] = mktime(0,0,0,gmdate("m",$timestamp_zero),gmdate("d",$timestamp_zero)+$shift+1,gmdate("Y",$timestamp_zero));
  $current_week['Wednesday'] = mktime(0,0,0,gmdate("m",$timestamp_zero),gmdate("d",$timestamp_zero)+$shift+2,gmdate("Y",$timestamp_zero));
  $current_week['Thursday'] = mktime(0,0,0,gmdate("m",$timestamp_zero),gmdate("d",$timestamp_zero)+$shift+3,gmdate("Y",$timestamp_zero));
  $current_week['Friday'] = mktime(0,0,0,gmdate("m",$timestamp_zero),gmdate("d",$timestamp_zero)+$shift+4,gmdate("Y",$timestamp_zero));
  $current_week[0] = $current_week['Monday'];
  $current_week[1] = $current_week['Tuesday'];
  $current_week[2] = $current_week['Wednesday'];
  $current_week[3] = $current_week['Thursday'];
  $current_week[4] = $current_week['Friday'];
  return $current_week;
}


function HISTORY($selecteddate,&$con)
{
// Get the dates for the selected week
  $current_week = DETERMINE_WEEK($selecteddate);

// Build the table
  $activeusers = mysql_query("SELECT * FROM Users WHERE Active=1;",&$con);
  if ( mysql_num_rows($activeusers) == 0 ) { echo "Cannot display case counts until active users are added.<br />"; }
  else {
  echo "<table border='1'>
  <tr>
    <th>Name</th>
    <th width='125'>".substr(date("l",$current_week['Monday']),0,3)."&nbsp;".date("n/j",$current_week['Monday'])."</th>
    <th width='125'>".substr(date("l",$current_week['Tuesday']),0,3)."&nbsp;".date("n/j",$current_week['Tuesday'])."</th>
    <th width='125'>".substr(date("l",$current_week['Wednesday']),0,3)."&nbsp;".date("n/j",$current_week['Wednesday'])."</th>
    <th width='125'>".substr(date("l",$current_week['Thursday']),0,3)."&nbsp;".date("n/j",$current_week['Thursday'])."</th>
    <th width='125'>".substr(date("l",$current_week['Friday']),0,3)."&nbsp;".date("n/j",$current_week['Friday'])."</th>
  </tr>";
  while ( $currentuser = mysql_fetch_array($activeusers) )
    {
    echo "<tr>";
    for ($col=1; $col<=6; $col++)
      {
        if ($col==1) 
          {
          echo "<td>";
          if ($currentuser['userID'] == $_COOKIE['userID']) echo "<b><u>".$currentuser['UserName']."</u></b>";
          else echo $currentuser['UserName'];
          echo "</td>";
          }
        else 
          {
          $usercounts = mysql_fetch_array(mysql_query("SELECT Regular,CatOnes,Special,UpdateDate,Date FROM Count WHERE userID='".$currentuser['userID']."' AND Date='".$current_week[$col-2]."';",$con));
          echo "<td>";
          if ($usercounts['Regular'] == '') echo "0,";
          else echo $usercounts['Regular'].",";
          if ($usercounts['CatOnes'] == '') echo "0,";
          else echo $usercounts['CatOnes'].",";
          if ($usercounts['Special'] == '') echo "0";
          else echo $usercounts['Special'];
          
          $cellhasdata = 1;
          if (($usercounts['Regular'] == '') or ($usercounts['CatOnes'] == '') or ($usercounts['Special'] == '')) $cellhasdata = 0;
          if (($usercounts['Regular'] == 0) and ($usercounts['CatOnes'] == 0) and ($usercounts['Special'] == 0)) $cellhasdata = 0;
          if (($usercounts['UpdateDate'] != '') and ($usercounts['Date'] <= mktime()) and ($cellhasdata == 1))
            {
            echo " - ";
            $daylightsavings = 1;
            if ($usercounts['UpdateDate'] > ($usercounts['Date']+60*60*20)) echo "eob";
            else echo gmdate("g:ia",$usercounts['UpdateDate']+60*60*($_COOKIE['timezone']+$daylightsavings));
            }
          echo "</td>";
          }
      }
    echo "</tr>";
    }
  echo "</table>";
  }
}


function MYCASECOUNT($selecteddate,&$con)
{
// Get the dates for the selected week
  $current_week = DETERMINE_WEEK($selecteddate);
// If a user is not selected then we can't do anything here
if ($_COOKIE['userID'] == '' ) { echo "No user has been selected, cannot display user case count editor.<br />"; }
else {

// Commit to DB
  for ($i=0;$i<=4;$i++) {
    if (($_POST["reg_".$current_week[$i]] != '') and ($_POST["cat1_".$current_week[$i]] != '') and ($_POST["spec_".$current_week[$i]] != ''))
    {
      $checkforentry = mysql_query("SELECT * FROM Count WHERE Date = '".$current_week[$i]."' AND userID ='".$_COOKIE['userID']."'",&$con);
      if ( mysql_num_rows($checkforentry) == 0 ) $sql="INSERT INTO Count (userID, CatOnes, Special, Regular, Date, UpdateDate) VALUES ('".$_COOKIE['userID']."',".$_POST["cat1_".$current_week[$i]].",".$_POST["spec_".$current_week[$i]].",".$_POST["reg_".$current_week[$i]].",".$current_week[$i].",".mktime().")";
      else 
        {
        $checkchanges = mysql_fetch_array($checkforentry);
        if (($checkchanges['CatOnes'] == $_POST["cat1_".$current_week[$i]]) and ($checkchanges['Regular'] == $_POST["reg_".$current_week[$i]]) and ($checkchanges['Special'] == $_POST["spec_".$current_week[$i]])) $updatedate = $checkchanges['UpdateDate'];
        else $updatedate = mktime();
        $sql="UPDATE Count SET CatOnes = '".$_POST["cat1_".$current_week[$i]]."', Special = '".$_POST["spec_".$current_week[$i]]."', Regular = '".$_POST["reg_".$current_week[$i]]."', UpdateDate = '".$updatedate."' WHERE userID = '".$_COOKIE['userID']."' AND Date = '".$current_week[$i]."'";
        }
      RUN_QUERY($sql,"Values were not updates.",$con);
      }
    }
  
// Build table
  $username = mysql_fetch_array(mysql_query("SELECT UserName FROM Users WHERE Active=1 AND userID=".$_COOKIE['userID'].";",$con));
  echo "<form method='post'><input type='hidden' name='selecteddate' value='".$_GET['selecteddate']."'><table border='1'>
    <tr>
      <th><u>".$username['UserName']."</u></th>
      <th>".substr(date("l",$current_week['Monday']),0,3)."&nbsp;".date("n/j",$current_week['Monday'])."</th>
      <th>".substr(date("l",$current_week['Tuesday']),0,3)."&nbsp;".date("n/j",$current_week['Tuesday'])."</th>
      <th>".substr(date("l",$current_week['Wednesday']),0,3)."&nbsp;".date("n/j",$current_week['Wednesday'])."</th>
      <th>".substr(date("l",$current_week['Thursday']),0,3)."&nbsp;".date("n/j",$current_week['Thursday'])."</th>
      <th>".substr(date("l",$current_week['Friday']),0,3)."&nbsp;".date("n/j",$current_week['Friday'])."</th>
    </tr>
    <tr>
      <th>Regular</th>";
      for ($i=0;$i<=4;$i++) { 
        $selectedcount = mysql_fetch_array(mysql_query("SELECT * FROM Count WHERE Date = '".$current_week[$i]."' AND userID = ".$_COOKIE['userID'],&$con));
        echo "<td><input type='text' size='6' name='reg_".$current_week[$i]."'";
        $getcount= mysql_query("SELECT Regular FROM Count WHERE Date = '".$current_week[$i]."' AND userID = '".$_COOKIE['userID']."'",&$con);
        if ( mysql_num_rows($getcount) == 0 ) echo " value='0' ";
        else {
          $currentusercount = mysql_fetch_array($getcount);
          echo " value='".$currentusercount['Regular']."' ";
          }
        echo "/></td>\n";
        }
    echo "</tr>
    <tr>
      <th>Cat 1</th>";
      for ($i=0;$i<=4;$i++) {
        $selectedcount = mysql_fetch_array(mysql_query("SELECT * FROM Count WHERE Date = '".$current_week[$i]."' AND userID = ".$_COOKIE['userID'],&$con));
        echo "<td><input type='text' size='6' name='cat1_".$current_week[$i]."'";
        $getcount= mysql_query("SELECT CatOnes FROM Count WHERE Date = '".$current_week[$i]."' AND userID = '".$_COOKIE['userID']."'",&$con);
        if ( mysql_num_rows($getcount) == 0 ) echo " value='0' ";
        else {
          $currentusercount = mysql_fetch_array($getcount);
          echo " value='".$currentusercount['CatOnes']."' ";
          }
        echo "/></td>\n";
        }
    echo "</tr>
    <tr>
      <th>Special</th>";
      for ($i=0;$i<=4;$i++) { 
        $selectedcount = mysql_fetch_array(mysql_query("SELECT * FROM Count WHERE Date = '".$current_week[$i]."' AND userID = ".$_COOKIE['userID'],&$con));
        echo "<td><input type='text' size='6' name='spec_".$current_week[$i]."'";
        $getcount= mysql_query("SELECT Special FROM Count WHERE Date = '".$current_week[$i]."' AND userID = '".$_COOKIE['userID']."'",&$con);
        if ( mysql_num_rows($getcount) == 0 ) echo " value='0' ";
        else {
          $currentusercount = mysql_fetch_array($getcount);
          echo " value='".$currentusercount['Special']."' ";
          }
        echo "/></td>\n";
        }
    echo "</tr>
    </table>
    <input type='submit' name='update' /></form>";
  }
  
}


function CURRENTQUEUE($selecteddate,&$con)
{
// Build table
  $current_week = DETERMINE_WEEK($selecteddate);
  $selectedschedule = mysql_query("SELECT Date FROM Schedule,Users WHERE Date >= '".$current_week['Monday']."' AND Date <= '".$current_week['Friday']."' AND Users.userID = Schedule.userID AND Users.Active = 1",&$con);
  if ( mysql_num_rows($selectedschedule) == 0 ) { echo "No active schedule found.<br />"; }
  else {
  
  echo "<table border='1'>
  <tr>
    <th width='100'>".substr(date("l",$current_week['Monday']),0,3)."&nbsp;".date("n/j",$current_week['Monday'])."</th>
    <th width='100'>".substr(date("l",$current_week['Tuesday']),0,3)."&nbsp;".date("n/j",$current_week['Tuesday'])."</th>
    <th width='100'>".substr(date("l",$current_week['Wednesday']),0,3)."&nbsp;".date("n/j",$current_week['Wednesday'])."</th>
    <th width='100'>".substr(date("l",$current_week['Thursday']),0,3)."&nbsp;".date("n/j",$current_week['Thursday'])."</th>
    <th width='100'>".substr(date("l",$current_week['Friday']),0,3)."&nbsp;".date("n/j",$current_week['Friday'])."</th>
  </tr>";
  
  for ($i = 0; $i <= 4; $i++) {
    $shift = mysql_fetch_array(mysql_query("SELECT COUNT(Shift) FROM Schedule WHERE Date = ".$current_week[$i],$con));
    $shiftcount[$i] = $shift['COUNT(Shift)'];
    $currentday = mysql_query("SELECT UserName,Shift,Users.userID FROM Users,Schedule WHERE Schedule.Date = ".$current_week[$i]." AND Users.userID = Schedule.userID AND Users.Active = 1",$con);
    $j = 0;
    while ($getarray = mysql_fetch_array($currentday)) { $namesAndShifts[$i][$j++] = $getarray; }
    }
  rsort($shiftcount,SORT_NUMERIC);
  //print_r($shiftcount);

  for ($row = 1; $row <= $shiftcount[0]; $row++)
    {
    echo "<tr>";
    for ($col = 1; $col <= 5; $col++)
      {  
      print_r($getarray);
      echo "<td>";
      if ($namesAndShifts[$col-1][$row-1]['userID'] == $_COOKIE['userID'] ) echo "<b><u>".$namesAndShifts[$col-1][$row-1]['UserName']."</u></b>";
      else echo $namesAndShifts[$col-1][$row-1]['UserName'];
      if ($namesAndShifts[$col-1][$row-1]['Shift'] == 1) echo "&nbsp;(.5)";
      echo "</td>";
      }
    }
  echo "</table>";
  }
  
}

function SELECTDATE($shownextweek,$selecteddate,&$con)
{
  $display_week = DETERMINE_WEEK($selecteddate); 
  
  $activitydates = mysql_query("SELECT Date FROM Schedule,Users WHERE Users.Active = 1 AND Users.userID = Schedule.userID",$con);
  if ( mysql_num_rows($activitydates) == 0 ) { echo "No active schedule history found, cannot show date selection.<br />"; }
  else {
  $i = 0;
  while ( $currentdate = mysql_fetch_array($activitydates) ) { 
    $current_week = DETERMINE_WEEK($currentdate['Date']); 
    $mondays[$i++] = $current_week['Monday'];
    }
  $mondays = array_unique($mondays);
  rsort($mondays,SORT_NUMERIC);
  
  // This will add next week to the drop down if it is specified and next week doesn't already have data
  $checknextweek = DETERMINE_WEEK(mktime()+60*60*24*7+60*60*($_COOKIE['timezone']+$daylightsavings)); 
  if (($shownextweek == 1) and ($mondays[0] != $checknextweek['Monday'])){
    $mondays[$i++] = $checknextweek['Monday'];
    rsort($mondays,SORT_NUMERIC);
    }
  
    echo "<form> Show:
    <select name='selecteddate'>";
    foreach ( $mondays as $key => $value ) {
      echo "<option value='" . $value . "'";
      if ($display_week['Monday'] == $value) { echo " selected='selected' "; }
      echo ">" . substr(date("l",$value),0,3) . " " . date("n/j",$value) . " - " . substr(date("l",$value+60*60*24*4),0,3) . " " . date("n/j",$value+60*60*24*4) . "</option>";
      }
    echo "</select>
    <input type='submit' value='go'>
  </form>";
  }
}

function SCHEDULE($selecteddate,&$con)
{
// Build table
  $current_week = DETERMINE_WEEK($selecteddate);
  $activeusers = mysql_query("SELECT * FROM Users WHERE Active=1;",&$con);
  if ( mysql_num_rows($activeusers) == 0 ) { echo "No active users found. You need to add users.<br />"; }
  else {
  
  echo "<form method='post'><table>
  <tr>
    <th>Name</th>
    <th>".substr(date("l",$current_week['Monday']),0,3)."<br />".date("n/j",$current_week['Monday'])."</th>
    <th>".substr(date("l",$current_week['Tuesday']),0,3)."<br />".date("n/j",$current_week['Tuesday'])."</th>
    <th>".substr(date("l",$current_week['Wednesday']),0,3)."<br />".date("n/j",$current_week['Wednesday'])."</th>
    <th>".substr(date("l",$current_week['Thursday']),0,3)."<br />".date("n/j",$current_week['Thursday'])."</th>
    <th>".substr(date("l",$current_week['Friday']),0,3)."<br />".date("n/j",$current_week['Friday'])."</th>
    <th>Total</th>
  </tr>";
  
  while ( $currentuser = mysql_fetch_array($activeusers) )
    {
    echo "<tr>";
    for ($col=1; $col<=7; $col++)
      {  
      if ($col==1) echo "<td>".$currentuser['UserName']."</td>";
      else if ($col==7) {
        $currentshift = mysql_fetch_array(mysql_query("SELECT SUM(Shift) FROM Schedule WHERE userID = '".$currentuser['userID']."' AND Date >= '".$current_week[0]."' AND Date <= '".$current_week[4]."'",$con));
        echo "<td>" . $currentshift['SUM(Shift)'] / 2 . "</td>";
        }
      else {
        $postvariable = "sched_".$current_week[$col-2]."_".$currentuser['userID'];
        
        $currentshift = mysql_query("SELECT Shift FROM Schedule WHERE userID = '".$currentuser['userID']."' AND Date = '".$current_week[$col-2]."'",$con);
        
        if ($_POST[$postvariable] != '') {
          if ( (mysql_num_rows($currentshift) != 0) and ($_POST[$postvariable] == '0') ) { // DB has data, but user entered blank - delete
            $sql="DELETE FROM Schedule WHERE userID='".$currentuser['userID']."' AND Date='".$current_week[$col-2]."'";
            if ( !mysql_query($sql,$con) ) { echo $sql . " <br /> Entry was not deleted. <br /> <span class='error'>*Error</span>: " . mysql_error(); }
            }
          
          if ( (mysql_num_rows($currentshift) != 0) and ($_POST[$postvariable] != '0') ) { // DB has data, and user entered data - update
            $sql="UPDATE Schedule SET Shift = '".$_POST[$postvariable]."' WHERE userID = '".$currentuser['userID']."' AND Date = '".$current_week[$col-2]."'";
            if ( !mysql_query($sql,$con) ) { echo $sql . " <br /> Entry was not updated. <br /> <span class='error'>*Error</span>: " . mysql_error(); }
            }

          if ( (mysql_num_rows($currentshift) == 0) and ($_POST[$postvariable] != '0') ) { // DB doesn't have data, and user entered data - insert
            $sql="INSERT INTO Schedule (userID, Date, Shift) VALUES (".$currentuser['userID'].",".$current_week[$col-2].",".$_POST[$postvariable].")";
            if ( !mysql_query($sql,$con) ) { echo $sql . " <br /> Entry was not added. <br /> <span class='error'>*Error</span>: " . mysql_error(); }
            }
          
          }
        
        $currentshift = mysql_fetch_array(mysql_query("SELECT Shift FROM Schedule WHERE userID = '".$currentuser['userID']."' AND Date = '".$current_week[$col-2]."'",$con));
        
        echo "<td>
        <select name='".$postvariable."'>
        <option value='2' "; 
        if ( $currentshift['Shift'] == 2 ) echo "selected='selected'";
        echo ">FULL</option>
        <option value='1' "; 
        if ( $currentshift['Shift'] == 1 ) echo "selected='selected'";
        echo ">HALF</option>
        <option value='0' "; 
        if ( $currentshift['Shift'] == '' ) echo "selected='selected'";
        echo "></option>
        </select>
        </td>";
        }
      }
    echo "</tr>";
    }

  echo "</table> <input type='submit' value='submit'> </form>";
  }

}


function SELECTUSER(&$con)
{
  // Creates timezones add as necessary from GMT
  $alltimezones['GMT'] = 0;
  $alltimezones['MST'] = -7;
  $alltimezones['PST'] = -8;

  $userselected = 0;
  $activeusers = mysql_query("SELECT * FROM Users WHERE Active=1;",$con);
  if ( mysql_num_rows($activeusers) == 0 ) { echo "No active users found. You need to add users.<br />";
  } else {
    echo "<form method='get'> User: <select name='userID'>";
    while ( $currentuser = mysql_fetch_array($activeusers) ) {
      echo "<option ";
      if ( $_COOKIE['userID'] == $currentuser['userID'] ) 
      {
        echo "selected='selected'";
        $userselected = 1;
      }
      echo "value='".$currentuser['userID']."'>".$currentuser['UserName']."</option>";
    }
    if ($userselected != 1) echo "<option selected='selected' value=''>-----</option>";
    echo "</select>";
    
    echo "<select name='timezone'>";
    foreach ( $alltimezones as $key => $value ) {
      echo "<option ";
      if ( $_COOKIE['timezone'] == $value ) echo "selected='selected'";
      echo "value='".$value."'>".$key."</option>";
    }
    echo "</select> <input type='submit' value='select'> </form>";
  }
}

function USERS(&$con)
{
  if ( $_GET['edituser'] != "" and $_GET['newusername'] != "" ) {
    $sql="UP DATE Users SET UserName = '".$_GET['newusername']."' WHERE userID = '".$_GET['edituser']."'";
    RUN_QUERY($sql,"User was not updated.",$con);
  }

  if ( $_GET['edituser'] != "" and $_GET['newusername'] == "") {
    $username = mysql_fetch_array(mysql_query("SELECT UserName FROM Users WHERE userID=".$_GET['edituser'].";",&$con));
    echo "<form method='get'> <h2> Change users name: </h2> <input type='hidden' name='edituser' value='".$_GET['edituser']."'><input type='text' name='newusername' size='10' value='".$username['UserName']."'> <input type='submit' value='rename'> </form>"; 
  }

  if ( $_GET['permdeleteuser'] != "" ) {
    $sql="DELETE FROM Users WHERE userID='".$_GET['permdeleteuser']."'";
    RUN_QUERY($sql,"User was not deleted table Users.",$con);
    $sql="DELETE FROM Schedule WHERE userID='".$_GET['permdeleteuser']."'";
    RUN_QUERY($sql,"User was not deleted table Schedule.",$con);
    $sql="DELETE FROM Count WHERE userID='".$_GET['permdeleteuser']."'";
    RUN_QUERY($sql,"User was not deleted table Count.",$con);
  }

  if ( $_GET['restoreuser'] != "" ) {
    $sql="UPDATE Users SET Active = 1 WHERE userID = '".$_GET['restoreuser']."'";
    RUN_QUERY($sql,"User was not restored.",$con);
  }

  if ( $_GET['deleteuser'] != "" ) {
    $sql="UPDATE Users SET Active = 0 WHERE userID = '".$_GET['deleteuser']."'";
    RUN_QUERY($sql,"User was not deleted.",$con);
  }

  if ( $_GET['createuser'] != "" ) {
    $sql="INSERT INTO Users (UserName, Active) VALUES ('".$_GET['createuser']."',1)";
    RUN_QUERY($sql,"User was not created.",$con);
  }

  $activeusers = mysql_query("SELECT * FROM Users WHERE Active=1;",&$con);
  $nonactiveusers = mysql_query("SELECT * FROM Users WHERE Active=0;",&$con);

  echo "<h2>Active Users:</h2>\n";
  if ( mysql_num_rows($activeusers) == 0 ) { echo "No active users found. <br />\n";
  } else {
    while ( $currentuser = mysql_fetch_array($activeusers) )
      echo $currentuser['UserName']." - <a href='?edituser=".$currentuser['userID']."'>Edit</a> - <a href='?deleteuser=".$currentuser['userID']."'>Delete</a> <br />\n";
  }

  echo "<h2>Inactive Users:</h2>\n";
  if ( mysql_num_rows($nonactiveusers) == 0 ) { echo "No inactive users found. <br />\n";
  } else {
    while ( $currentuser = mysql_fetch_array($nonactiveusers) )
      echo $currentuser['UserName']." - <a href='?restoreuser=".$currentuser['userID']."'>Restore</a> - <a href='?permdeleteuser=".$currentuser['userID']."'>Permanently Delete</a> <br />\n";
  }

  echo "<form method='get'> <h2> Create new user: </h2> <input type='text' name='createuser' size='10' value=''> <input type='submit' value='create'> </form>"; 
}

?>