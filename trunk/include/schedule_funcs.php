<?php

function SCHEDULE($selecteddate,&$con)
{
// Get the dates for the selected week
$current_week = DETERMINE_WEEK($selecteddate);

// Determine if any active raiders exist
$activeusers = mysql_query("SELECT userID FROM Users WHERE Active=1;",&$con);
if ( mysql_num_rows($activeusers) == 0 )
  {
  echo "      No active users found. You need to add users.<br />\n";
  }
else
  {
  UPDATE_DB_SCHEDULE($current_week,$con);
  TABLE_SCHEDULE($current_week,$con);
  }
}


function UPDATE_DB_SCHEDULE($current_week,&$con)
{
$activeusers = mysql_query("SELECT * FROM Users WHERE Active=1;",&$con);
while ( $currentuser = mysql_fetch_array($activeusers) )
  {
  for ($i=0; $i<5; $i++)
    {
    $postvariable = "sched_".$current_week[$i]."_".$currentuser['userID'];

    $currentshift = mysql_query("SELECT Shift FROM Schedule WHERE userID = '".$currentuser['userID']."' AND Date = '".$current_week[$i]."'",$con);

    if ($_POST[$postvariable] != '') 
      {
      // DB has data, but user entered blank - delete
      if ( (mysql_num_rows($currentshift) != 0) and ($_POST[$postvariable] == '0') )
        {
        $sql="DELETE FROM Schedule WHERE userID='".$currentuser['userID']."' AND Date='".$current_week[$i]."'";
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
        $sql="INSERT INTO Schedule (userID, Date, Shift) VALUES (".$currentuser['userID'].",".$current_week[$i].",".$_POST[$postvariable].")";
        RUN_QUERY($sql,"Entry was not added.",$con);
        }
      }
    }
  }
}


function TABLE_SCHEDULE($current_week,&$con)
{
$activeusers = mysql_query("SELECT * FROM Users WHERE Active=1 ORDER BY UserName;",&$con);
echo "<form method='post' name='schedule'><table>
<tr>
  <th>Name</th>\n";
  for ($i=0;$i<5;$i++)
    echo "<th>".substr(date("l",$current_week[$i]),0,3)."<br />".date("n/j",$current_week[$i])."</th>\n";
echo "  <th>Total</th>
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
      $currentshift = mysql_fetch_array(mysql_query("SELECT Shift FROM Schedule WHERE userID = '".$currentuser['userID']."' AND Date = '".$current_week[$col-2]."'",$con));
      echo "<td>
      <select name='".$postvariable."' OnChange='schedule.submit();'>
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

echo "</table> <input type='submit' id='schedule_submit' value='submit'> </form>";
echo "    <script type='text/javascript'>\n";
echo "      <!--\n";
echo "      document.getElementById('schedule_submit').style.display='none'; // hides button if JS is enabled-->\n";
echo "    </script>\n";
}

?>