<?php

function REPORTS(&$con)
{
  // Get the dates for this week
  $current_week = DETERMINE_WEEK(mktime());

  // If no active user exists then we can't do anything here
  $activeusers = mysql_query("SELECT * FROM Users WHERE Active=1;",&$con);
  if ( mysql_num_rows($activeusers) == 0 )
    echo "    Cannot display case counts until active users are added.<br />\n";
  else 
    {
    TABLE_REPORTS($current_week,$con);
    }
}


function TABLE_REPORTS($current_week,&$con)
//function TABLE_CURRENTHISTORY($showdetails,$timezone,$userID,$current_week,&$con)
{
$activeusers = mysql_query("SELECT * FROM Users WHERE Active=1 ORDER BY UserName ASC;",&$con);
echo "    <table class='table_currenthistory'>\n";
echo "      <tr class='table_currenthistory_row'>\n";
echo "        <th class='table_currenthistory_header'>Name</th>\n";
for ($i=0;$i<5;$i++)
  echo "        <th class='table_currenthistory_header'>".substr(gmdate("l",$current_week[$i]),0,3)."&nbsp;".gmdate("n/j",$current_week[$i])."</th>\n";
echo "      </tr>\n";
while ( $currentuser = mysql_fetch_array($activeusers) )
  {
  echo "      <tr class='table_currenthistory_row'>\n";
  for ($col=1; $col<=6; $col++)
    {
      if ($col==1) 
        {
        echo "        <td class='table_currenthistory_cell";
        echo "'>";
        // if ($userID == $currentuser['userID'])
          // echo "<span class='selecteduser'>".$currentuser['UserName']."</span>";
        // else
          echo $currentuser['UserName'];
        echo "</td>\n";
        }
      else 
        {
        $usercounts = mysql_fetch_array(mysql_query("SELECT Regular,CatOnes,Special,Transfer,UpdateDate,Date FROM Count WHERE userID='".$currentuser['userID']."' AND Date='".$current_week[$col-2]."';",$con));
        echo "        <td class='table_currenthistory_cell";
        // $currentusershift = mysql_fetch_array(mysql_query("SELECT Shift FROM Schedule WHERE Date='".$current_week[$col-2]."' AND userID='".$currentuser['userID']."'",&$con));
        // // This added a class to identify selected user and on shift
        // if ((($currentuser['userID'] == $userID) and ($userID != '')) and ( $currentusershift['Shift'] > 0 ))
          // echo " selecteduseronshiftcell";
        // else 
        // // This added a class to identify selected user
        // if (($currentuser['userID'] == $userID) and ($userID != '')) 
          // echo " selectedusercell";
        // else 
        // if ( $currentusershift['Shift'] > 0 ) 
          // echo " onshiftcell";
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
        echo "        <span class='table_mycasecount_total'>".$total."</span>\n";
        
        // if ( $showdetails == 'on')
          // {
          // echo "        =\n";
          // echo "        <span class='table_mycasecount_regular'>".$regularcases."</span>\n";
          // echo "        <span class='table_mycasecount_catones'>".$catonecases."</span>\n";
          // echo "        <span class='table_mycasecount_special'>".$specialcases."</span>\n";
          // echo "        |\n";
          // echo "        <span class='table_mycasecount_transfer'>".$transfercases."</span>\n";
          // }
        
        // $cellhasdata = 1;
        // if (($usercounts['Regular'] == '') or ($usercounts['CatOnes'] == '') or ($usercounts['Special'] == ''))
          // $cellhasdata = 0;
        // if (($usercounts['Regular'] == 0) and ($usercounts['CatOnes'] == 0) and ($usercounts['Special'] == 0))
          // $cellhasdata = 0;
          
        // $dst_value_from_current_time_sec = date("I",$usercounts['Date'])*60*60; // This is a 1*60*60 if DST is set on the time
        // $current_date_at_six_pm = $usercounts['Date'] + 60 * 60 * ( 12 - 18 - $timezone) + $dst_value_from_current_time_sec;
        // if (($usercounts['UpdateDate'] != '') and ($current_date_at_six_pm <= $usercounts['UpdateDate']) and ($cellhasdata == 1))
          // {
          // echo "        - ";
          // if ($usercounts['UpdateDate'] > ($usercounts['Date'] + 60 * 60 * ( 12 - $timezone) + $dst_value_from_current_time_sec )) 
            // {
            // echo "eob\n";
            // }
          // else echo gmdate("g:ia",$usercounts['UpdateDate'] + 60*60*($timezone) + $dst_value_from_current_time_sec)."\n";
          // }
        echo "        </td>\n";
        }
    }
  echo "      </tr>\n";
  }
echo "    </table>\n";
echo "    <form method='post' name='showdetailsform'>\n";
// echo "      <div class='showdetails'>\n";
// echo "      Details:\n";
// echo "      <input type='hidden' name='showdetailssent' value='1' />\n";
// echo "      <input type='checkbox' name='showdetails'";
// if ( $showdetails == 'on' )
  // echo " checked='checked'";
// echo " OnClick='showdetailsform.submit();' />\n";
// echo "      <input type='submit' id='showdetails_submit' value='update' />\n";
// echo "     </div>\n";
echo "    </form>\n";
echo "    <script type='text/javascript'>\n";
echo "      <!--\n";
echo "      document.getElementById('showdetails_submit').style.display='none'; // hides button if JS is enabled-->\n";
echo "    </script>\n";
}

?>