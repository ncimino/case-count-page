<?php

function MYCASECOUNT($userID,$selecteddate,&$con)
{
  // Get the dates for the selected week
  $current_week = DETERMINE_WEEK($selecteddate);

  // If a user is not selected then we can't do anything here, if there is a cookie set but no users exist, then
  $activeusers = mysql_query("SELECT * FROM Users WHERE Active=1;",&$con);
  
  if ( mysql_num_rows($activeusers) == 0 )
    echo "    No user has been selected, cannot display user case count editor.<br />\n";
  else
    {
    if ($userID == '') // If user is not set then show a legend to make sense of the colors
      {
      echo "    <span class='mycasecount_total'>Total</span> =\n";
      echo "    <span class='mycasecount_regular'>Regular</span>\n";
      echo "    <span class='mycasecount_catones'>Cat 1</span>\n";
      echo "    <span class='mycasecount_special'>Special</span> |\n";
      echo "    <span class='mycasecount_transfer'>Transfer</span>\n";
      }
    else
      {
      UPDATE_DB_MYCASECOUNT($userID,$current_week,$con);
      TABLE_MYCASECOUNT($userID,$current_week,$con);
      }
    }
}


function CURRENTHISTORY($showdetails,$timezone,$userID,$selecteddate,&$con)
{
  // Get the dates for the selected week
  $current_week = DETERMINE_WEEK($selecteddate);

  // If no active user exists then we can't do anything here
  $activeusers = mysql_query("SELECT * FROM Users WHERE Active=1;",&$con);
  if ( mysql_num_rows($activeusers) == 0 )
    echo "    Cannot display case counts until active users are added.<br />\n";
  else 
    {
    TABLE_CURRENTHISTORY($showdetails,$timezone,$userID,$current_week,$con);
    }
}


function CURRENTQUEUE($userID,$selecteddate,&$con)
{
  // Get the dates for the selected week
  $current_week = DETERMINE_WEEK($selecteddate);
  
  // If no active schedule exists then we can't do anything here
  $selectedschedule = mysql_query("SELECT Date FROM Schedule,Users WHERE Date >= '".$current_week['Monday']."' AND Date <= '".$current_week['Friday']."' AND Users.userID = Schedule.userID AND Users.Active = 1",&$con);
  if ( mysql_num_rows($selectedschedule) == 0 )
    echo "    No active schedule found.<br />\n"; 
  else
    {
    TABLE_CURRENTQUEUE($userID,$current_week,$con);
    }
}


function NOTES(&$con)
{
  $queuenotes = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuenotes';",&$con));
  echo "<pre>".htmlentities($queuenotes['OptionValue'],ENT_QUOTES)."</pre>\n";
}


function RULES(&$con)
{
  $queuerules = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuerules';",&$con));
  echo "<pre>".htmlentities($queuerules['OptionValue'],ENT_QUOTES)."</pre>\n";
}


function SELECTUSER($timezone,$userID,&$con)
{
// Creates timezones add as necessary from GMT
$alltimezones['MST'] = -7;
$alltimezones['PST'] = -8;

// This variable is set to 1 if a user that exists is found
$userselected = 0;

// First determine if there are any active users
$activeusers = mysql_query("SELECT * FROM Users WHERE Active=1 ORDER BY UserName;",$con);
if ( mysql_num_rows($activeusers) == 0 ) 
  {
  echo "    No active users found.<br />\n";
  }
else
  {
  echo "    <form method='post' name='selectuser'> User:\n";
  echo "      <select name='userID' OnChange='selectuser.submit();'>\n";
  
  while ( $currentuser = mysql_fetch_array($activeusers) ) 
    {
    echo "        <option ";
    if ( $userID == $currentuser['userID'] ) 
      {
      echo "selected='selected' ";
      $userselected = 1;
      }
    echo "value='".$currentuser['userID']."'>".$currentuser['UserName']."</option>\n";
    }
  
  echo "        <option";
  if ($userselected != 1)
    echo " selected='selected'";
  echo " value='NULL'>-----</option>\n";
  echo "      </select>\n";
  
  echo "      <select name='timezone' OnChange='selectuser.submit();'>\n";
  foreach ( $alltimezones as $key => $value ) 
    {
    echo "        <option ";
    if ( $timezone == $value ) echo "selected='selected'";
    echo "value='".$value."'>".$key."</option>\n";
    }
  echo "      </select>\n";
  echo "      <input type='submit' id='selectuser_submit' value='select'>\n";
  echo "    </form>\n";
  echo "    <script type='text/javascript'>\n";
  echo "      <!--\n";
  echo "      document.getElementById('selectuser_submit').style.display='none'; // hides button if JS is enabled-->\n";
  echo "    </script>\n";
  }
}


function UPDATE_DB_MYCASECOUNT($userID,$current_week,&$con)
{
for ($i=0;$i<=4;$i++) 
  {
  if (($_POST["reg_".$current_week[$i]] != '') and ($_POST["cat1_".$current_week[$i]] != '') and ($_POST["spec_".$current_week[$i]] != '') and ($_POST["tran_".$current_week[$i]] != ''))
    {
    $checkforentry = mysql_query("SELECT * FROM Count WHERE Date = '".$current_week[$i]."' AND userID ='".$userID."'",&$con);
    if ( mysql_num_rows($checkforentry) == 0 ) 
      $sql="INSERT INTO Count (userID, CatOnes, Special, Regular, Transfer, Date, UpdateDate) 
            VALUES ('".$userID."',".$_POST["cat1_".$current_week[$i]].",".$_POST["spec_".$current_week[$i]].",".$_POST["reg_".$current_week[$i]].",".$_POST["tran_".$current_week[$i]].",".$current_week[$i].",".mktime().")";
    else 
      {
      $checkchanges = mysql_fetch_array($checkforentry);
      if (($checkchanges['CatOnes'] == $_POST["cat1_".$current_week[$i]]) and 
          ($checkchanges['Regular'] == $_POST["reg_".$current_week[$i]]) and 
          ($checkchanges['Transfer'] == $_POST["tran_".$current_week[$i]]) and 
          ($checkchanges['Special'] == $_POST["spec_".$current_week[$i]])) 
        $updatedate = $checkchanges['UpdateDate'];
      else
        $updatedate = mktime();
      $sql="UPDATE Count SET CatOnes = '".$_POST["cat1_".$current_week[$i]]."', Special = '".$_POST["spec_".$current_week[$i]]."', Regular = '".$_POST["reg_".$current_week[$i]]."', Transfer = '".$_POST["tran_".$current_week[$i]]."', UpdateDate = '".$updatedate."' WHERE userID = '".$userID."' AND Date = '".$current_week[$i]."'";
      }
    RUN_QUERY($sql,"Values were not updated.",$con);
    }
  }
}


function TABLE_MYCASECOUNT($userID,$current_week,&$con)
{
$username = mysql_fetch_array(mysql_query("SELECT UserName FROM Users WHERE Active=1 AND userID=".$userID.";",$con));
echo "    <form name='mycasecount' method='post'>\n";
echo "      <input type='hidden' name='selecteddate' value='".$_GET['selecteddate']."'>\n";
echo "      <table class='mycasecount'>\n";
echo "        <tr class='mycasecount'>\n";
echo "          <th class='mycasecount'><span class='selecteduser'>".$username['UserName']."</span></th>\n";

for ($i=0;$i<5;$i++)
  echo "          <th class='mycasecount'>".substr(gmdate("l",$current_week[$i]),0,3)."&nbsp;".gmdate("n/j",$current_week[$i])."</th>\n";

echo "        </tr>\n";
echo "        <tr class='mycasecount'>\n";
echo "          <th class='mycasecount'><span class='mycasecount_regular'>Regular</span></th>\n";

for ($i=0;$i<=4;$i++) 
  {
  echo "          <td class='mycasecount'>\n";
  echo "          <input type='text' class='mycasecount' name='reg_".$current_week[$i]."' OnChange='mycasecount.submit();' OnKeyPress='return enterSubmit(this,event);'";
  $getcount= mysql_query("SELECT Regular FROM Count WHERE Date = '".$current_week[$i]."' AND userID = '".$userID."'",&$con);
  if ( mysql_num_rows($getcount) == 0 ) 
    echo " value='0' ";
  else 
    {
    $currentusercount = mysql_fetch_array($getcount);
    echo " value='".$currentusercount['Regular']."' ";
    }
  echo "/>\n";
  echo "          </td>\n";
  }

echo "        </tr>\n";
echo "        <tr class='mycasecount'>\n";
echo "          <th class='mycasecount'><span class='mycasecount_catones'>Cat 1</span></th>\n";

for ($i=0;$i<=4;$i++)
  {
  echo "          <td class='mycasecount'>\n";
  echo "          <input type='text' class='mycasecount' name='cat1_".$current_week[$i]."' OnChange='mycasecount.submit();' OnKeyPress='return enterSubmit(this,event);'";
  $getcount= mysql_query("SELECT CatOnes FROM Count WHERE Date = '".$current_week[$i]."' AND userID = '".$userID."'",&$con);
  if ( mysql_num_rows($getcount) == 0 ) 
    echo " value='0' ";
  else 
    {
    $currentusercount = mysql_fetch_array($getcount);
    echo " value='".$currentusercount['CatOnes']."' ";
    }
  echo "/>\n";
  echo "          </td>\n";
  }

echo "        </tr>\n";
echo "        <tr class='mycasecount'>\n";
echo "          <th class='mycasecount'><span class='mycasecount_special'>Special</span></th>\n";

for ($i=0;$i<=4;$i++)
  { 
  echo "          <td class='mycasecount'>\n";
  echo "          <input type='text' class='mycasecount' name='spec_".$current_week[$i]."' OnChange='mycasecount.submit();' OnKeyPress='return enterSubmit(this,event);'";
  $getcount= mysql_query("SELECT Special FROM Count WHERE Date = '".$current_week[$i]."' AND userID = '".$userID."'",&$con);
  if ( mysql_num_rows($getcount) == 0 ) echo " value='0' ";
  else {
    $currentusercount = mysql_fetch_array($getcount);
    echo " value='".$currentusercount['Special']."' ";
    }
  echo "/>\n";
  echo "          </td>\n";
  }

echo "        </tr>\n";
echo "        <tr class='mycasecount'>\n";
echo "          <th class='mycasecount'><span class='mycasecount_transfer'>Transfer Out (-)</span></th>\n";

for ($i=0;$i<=4;$i++)
  { 
  echo "          <td class='mycasecount'>\n";
  echo "          <input type='text' class='mycasecount' name='tran_".$current_week[$i]."' OnChange='mycasecount.submit();' OnKeyPress='return enterSubmit(this,event);'";
  $getcount= mysql_query("SELECT Transfer FROM Count WHERE Date = '".$current_week[$i]."' AND userID = '".$userID."'",&$con);
  if ( mysql_num_rows($getcount) == 0 ) echo " value='0' ";
  else {
    $currentusercount = mysql_fetch_array($getcount);
    echo " value='".$currentusercount['Transfer']."' ";
    }
  echo "/>\n";
  echo "          </td>\n";
  }

echo "        </tr>\n";
echo "      </table>\n";
echo "      <input type='submit' id='mycasecount_submit' value='update' />\n";
echo "    </form>\n";
echo "    <script type='text/javascript'>\n";
echo "      <!--\n";
echo "      document.getElementById('mycasecount_submit').style.display='none'; // hides button if JS is enabled-->\n";
echo "    </script>\n";
}


function TABLE_CURRENTHISTORY($showdetails,$timezone,$userID,$current_week,&$con)
{
  // Enable Debug 
  $determineweek_debug = 0;
  if ($determineweek_debug == 1) echo "<br>\n";
  if ($determineweek_debug == 1) echo "  ** Debug Mode is enabled for TABLE_CURRENTHISTORY function. **<br>\n";
  
$activeusers = mysql_query("SELECT * FROM Users WHERE Active=1 ORDER BY UserName ASC;",&$con);
echo "    <table class='table_currenthistory'>\n";
echo "      <tr class='table_currenthistory'>\n";
echo "        <th class='table_currenthistory'>Name</th>\n";
for ($i=0;$i<5;$i++)
  echo "        <th class='table_currenthistory'>".substr(gmdate("l",$current_week[$i]),0,3)."&nbsp;".gmdate("n/j",$current_week[$i])."</th>\n";
echo "      </tr>\n";
while ( $currentuser = mysql_fetch_array($activeusers) )
  {
  echo "      <tr class='table_currenthistory'>\n";
  for ($col=1; $col<=6; $col++)
    {
      if ($col==1) 
        {
        echo "        <td class='table_currenthistory";
        echo "'>";
        if ($userID == $currentuser['userID'])
          echo "<span class='selecteduser'>".$currentuser['UserName']."</span>";
        else
          echo $currentuser['UserName'];
        echo "</td>\n";
        }
      else 
        {
        $usercounts = mysql_fetch_array(mysql_query("SELECT Regular,CatOnes,Special,Transfer,UpdateDate,Date FROM Count WHERE userID='".$currentuser['userID']."' AND Date='".$current_week[$col-2]."';",$con));
        echo "        <td class='table_currenthistory";
        $currentusershift = mysql_fetch_array(mysql_query("SELECT Shift FROM Schedule WHERE Date='".$current_week[$col-2]."' AND userID='".$currentuser['userID']."'",&$con));
        // This added a class to identify selected user and on shift
        if ((($currentuser['userID'] == $userID) and ($userID != '')) and ( $currentusershift['Shift'] > 0 ))
          echo " selecteduseronshiftcell";
        else 
        // This added a class to identify selected user
        if (($currentuser['userID'] == $userID) and ($userID != '')) 
          echo " selectedusercell";
        else 
        if ( $currentusershift['Shift'] > 0 ) 
          echo " onshiftcell";
        echo "'>\n";
        
        if ($usercounts['Regular'] == '')
          $regularcases = 0;
        else
          $regularcases = $usercounts['Regular'];
          
        if ($usercounts['CatOnes'] == '')
          $catonecases = 0;
        else
          $catonecases = $usercounts['CatOnes'];
          
        if ($usercounts['Special'] == '')
          $specialcases = 0;
        else
          $specialcases = $usercounts['Special'];
          
        if ($usercounts['Transfer'] == '')
          $transfercases = 0;
        else
          $transfercases = $usercounts['Transfer'];
        
        $total = $regularcases + $catonecases + $specialcases;
        echo "        <span class='mycasecount_total'>".$total."</span>\n";
        
        if ( $showdetails == 'on')
          {
          echo "        =\n";
          echo "        <span class='mycasecount_regular'>".$regularcases."</span>\n";
          echo "        <span class='mycasecount_catones'>".$catonecases."</span>\n";
          echo "        <span class='mycasecount_special'>".$specialcases."</span>\n";
          echo "        |\n";
          echo "        <span class='mycasecount_transfer'>".$transfercases."</span>\n";
          }
        
        $cellhasdata = 1;
        if (($usercounts['Regular'] == '') or ($usercounts['CatOnes'] == '') or ($usercounts['Special'] == ''))
          $cellhasdata = 0;
        if (($usercounts['Regular'] == 0) and ($usercounts['CatOnes'] == 0) and ($usercounts['Special'] == 0))
          $cellhasdata = 0;
        
        
        $dst_value_from_current_time_sec = date("I",$usercounts['Date'])*60*60; // This is a 1*60*60 if DST is set on the time
        
        $current_date_at_six_pm = $usercounts['Date'] + 60*60*18;
        $update_date = $usercounts['UpdateDate']+60*60*$timezone+$dst_value_from_current_time_sec;
        
        if (($usercounts['UpdateDate'] != '') and ($update_date >= $usercounts['Date']) and ($cellhasdata == 1))
          {
          echo "        - ";
          if ($update_date >= $current_date_at_six_pm)
            {
            echo "eob\n";
            }
          else echo gmdate("g:ia",$usercounts['UpdateDate'] + 60*60*($timezone) + $dst_value_from_current_time_sec)."\n";
          }
        if ($determineweek_debug == 1) echo "<br />(".gmdate("g:ia n/j",$usercounts['Date']).") >=";
        if ($determineweek_debug == 1) echo "(".gmdate("g:ia n/j",$update_date).") >=";
        if ($determineweek_debug == 1) echo "(".gmdate("g:ia n/j",$current_date_at_six_pm).")";
        echo "        </td>\n";
        }
    }
  echo "      </tr>\n";
  }
echo "    </table>\n";
echo "    <form method='post' name='showdetailsform'>\n";
echo "      <div class='showdetails'>\n";
echo "      Details:\n";
echo "      <input type='hidden' name='showdetailssent' value='1' />\n";
echo "      <input type='checkbox' name='showdetails'";
if ( $showdetails == 'on' )
  echo " checked='checked'";
echo " OnClick='showdetailsform.submit();' />\n";
echo "      <input type='submit' id='showdetails_submit' value='update' />\n";
echo "     </div>\n";
echo "    </form>\n";
echo "    <script type='text/javascript'>\n";
echo "      <!--\n";
echo "      document.getElementById('showdetails_submit').style.display='none'; // hides button if JS is enabled-->\n";
echo "    </script>\n";
}


function TABLE_CURRENTQUEUE($userID,$current_week,&$con)
{
echo "    <table  class='currentqueue'>\n";
// echo "      <tr class='currentqueue_row'>\n";
// for ($i=0;$i<5;$i++)
  // echo "        <th class='currentqueue_header'>".substr(gmdate("l",$current_week[$i]),0,3)."&nbsp;".gmdate("n/j",$current_week[$i])."</th>\n";
// echo "      </tr>\n";

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
  echo "      <tr class='currentqueue'>\n";
  for ($col = 1; $col <= 5; $col++)
    {  
		if (($col == 1) and ($row == 1)) {
			echo "        <th class='currentqueue' rowspan='".$shiftcount[0]."'>\n";
			echo "          Queue\n";
			echo "        </th>\n";
		}
    echo "       <td class='currentqueue";
    if (($namesAndShifts[$col-1][$row-1]['userID'] == $userID ) and ($userID != ''))
      echo " selectedusercell_queue";
    echo "'>";
    if ($namesAndShifts[$col-1][$row-1]['userID'] == $userID )
      echo "<span class='selecteduser'>".$namesAndShifts[$col-1][$row-1]['UserName']."</span>";
    else 
      echo $namesAndShifts[$col-1][$row-1]['UserName'];
    if ($namesAndShifts[$col-1][$row-1]['Shift'] == 1)
      echo "&nbsp;(.5)";
    echo "</td>\n";
    }
  echo "      </tr>\n";
  }
echo "    </table>\n";
}

?>