<?php

function INDEX($selected_page,$showdetails,$showdetails_cat1,$userID,$timezone,$shownextweek,$selecteddate,&$con)
{
  $main_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites WHERE SiteName='main';",$con));
  $phone_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites WHERE SiteName='phoneshift';",$con));
  if ($selected_page == $main_page['siteID'])
  {
    $sitenotes = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='mainnotes' AND siteID='".$selected_page."';",$con));
    echo "<pre>".wordwrap($sitenotes['OptionValue'],125,"\n")."</pre>\n";
    //echo "<pre>".htmlentities($sitenotes['OptionValue'],ENT_QUOTES)."</pre>\n";
  }
  else if ($selected_page == $phone_page['siteID'])
  {
    PHONE_PAGE($selected_page,$userID,$timezone,$shownextweek,$selecteddate,$con);
  }
  else
  {
    SKILLSET_PAGE($selected_page,$showdetails,$showdetails_cat1,$userID,$timezone,$shownextweek,$selecteddate,$con);
  }
}

function PHONE_PAGE($selected_page,$userID,$timezone,$shownextweek,$selecteddate,&$con)
{
  echo "<div id='selectdate' class='selectdate'>\n";
  SELECTDATE($timezone,$shownextweek,$selecteddate,$con);
  echo "</div>\n";

  echo "<div id='currentqueue' class='currentqueue'>\n";
  echo "    <br />\n";
  CURRENTPHONES($timezone,$selected_page,$userID,$selecteddate,$con);
  echo "<form method='get' action='schedule.php'>\n";
  echo "  <input type='hidden' name='selecteddate' value='{$selecteddate}' />\n";
  echo "  <input type='hidden' name='option_page' value='{$selected_page}' />\n";
  echo "  <input type='submit' value='Edit' />\n";
  echo "</form>\n";
  echo "</div>\n";

  PHONENOTES($selected_page,$con);

}

function SKILLSET_PAGE($selected_page,$showdetails,$showdetails_cat1,$userID,$timezone,$shownextweek,$selecteddate,&$con)
{
  echo "<div id='selectdate' class='selectdate'>\n";
  SELECTDATE($timezone,$shownextweek,$selecteddate,$con);
  echo "</div>\n";

  echo "<div id='mycasecount' class='mycasecount'>\n";
  echo "    <br />\n";
  MYCASECOUNT($selected_page,$userID,$selecteddate,$con);
  echo "</div>\n";

  echo "<div id='currentqueue' class='currentqueue'>\n";
  echo "    <br />\n";
  CURRENTQUEUE($selected_page,$userID,$selecteddate,$con);
  echo "</div>\n";

  NOTES($selected_page,$con);

  echo "<div id='currenthistory' class='currenthistory'>\n";
  CURRENTHISTORY($selected_page,$showdetails,$showdetails_cat1,$timezone,$userID,$selecteddate,$con);
  $dst_value_from_current_time_sec = date("I")*60*60; // This is a 1*60*60 if DST is set on the time
  echo "    <span style='font-size:75%'> Last updated: ".gmdate("n/j h:i A",time()+60*60*$timezone+$dst_value_from_current_time_sec)." - This page will refresh every 5 minutes</span>\n";
  echo "    <hr width='50%' />\n";
  echo "</div>\n";

  RULES($selected_page,$con);
}

function MYCASECOUNT($selected_page,$userID,$selecteddate,&$con)
{
  // Get the dates for the selected week
  $current_week = DETERMINE_WEEK($selecteddate);

  // If a user is not selected then we can't do anything here, if there is a cookie set but no users exist, then
  $activeusers = mysql_query("SELECT Users.userID FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."' AND Users.userID='".$userID."';",$con);

  if (( $userID == '' ) OR ( mysql_num_rows($activeusers) == 0 )) // If user is not set then show a legend to make sense of the colors
  {
    echo "    <span class='mycasecount_total'>Total</span> =\n";
    echo "    <span class='mycasecount_regular'>Regular</span>\n";
    echo "    <span class='mycasecount_catones'>Cat 1</span>\n";
    echo "    <span class='mycasecount_special'>Special</span> |\n";
    echo "    <span class='mycasecount_transfer'>Transfer</span>\n";
  }
  else
  {
    UPDATE_DB_MYCASECOUNT($selected_page,$userID,$current_week,$con);
    TABLE_MYCASECOUNT($selected_page,$userID,$current_week,$con);
  }
}

function CURRENTHISTORY($selected_page,$showdetails,$showdetails_cat1,$timezone,$userID,$selecteddate,&$con)
{
  // Get the dates for the selected week
  $current_week = DETERMINE_WEEK($selecteddate);

  // If no active user exists then we can't do anything here
  $activeusers = mysql_query("SELECT Users.userID FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."';",$con);
  if ( mysql_num_rows($activeusers) == 0 )
  echo "    Cannot display case counts until active users are added to this skillset.<br />\n";
  else
  {
    TABLE_CURRENTHISTORY($selected_page,$showdetails,$showdetails_cat1,$timezone,$userID,$current_week,$con);
  }
}

function CURRENTPHONES($timezone,$selected_page,$userID,$selecteddate,&$con)
{
  // Get the dates for the selected week
  $current_week = DETERMINE_WEEK($selecteddate);

  // If no active schedule exists then we can't do anything here
  $sql = "SELECT Date
  FROM PhoneSchedule,Users,UserSites 
  WHERE Date >= '".$current_week['Monday']."' 
    AND Date <= '".$current_week['Friday']."' 
    AND Users.userID = PhoneSchedule.userID
    AND Users.userID = UserSites.userID
    AND UserSites.siteID = ".$selected_page." 
    AND Users.Active = 1";
  $selectedschedule = mysql_query($sql,$con);
  if ( mysql_num_rows($selectedschedule) == 0 )
  {
    echo "    No active phone schedule found.<br />\n";
  }
  else
  {
    TABLE_CURRENTPHONES($userID,$timezone,$selected_page,$current_week,$con);
  }
}

function CURRENTQUEUE($selected_page,$userID,$selecteddate,&$con)
{
  // Get the dates for the selected week
  $current_week = DETERMINE_WEEK($selecteddate);

  // If no active schedule exists then we can't do anything here
  $sql = "SELECT Date FROM Schedule,Users
  WHERE Date >= '".$current_week['Monday']."' 
  AND Date <= '".$current_week['Friday']."' 
  AND Users.userID = Schedule.userID 
  AND Users.Active = 1 
  AND siteID = ".$selected_page;
  $selectedschedule = mysql_query($sql,$con);
  if ( mysql_num_rows($selectedschedule) == 0 )
  echo "    No active schedule found.<br />\n";
  else
  {
    TABLE_CURRENTQUEUE($selected_page,$userID,$current_week,$con);
  }
}

function PHONENOTES($selected_page,&$con)
{
  $phonenotes = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='phonenotes' AND siteID=".$selected_page.";",$con));
  if ($phonenotes['OptionValue'] != '')
  {
    echo "<div id='rules' class='rules'>\n";
    echo "<h3>Phone notes</h3>\n";
    echo "<pre>".wordwrap($phonenotes['OptionValue'],125,"\n")."</pre>\n";
    //echo "<pre>".htmlentities($phonenotes['OptionValue'],ENT_QUOTES)."</pre>\n";
    echo "<hr width='50%' />\n";
    echo "</div>\n";
  }
}

function NOTES($selected_page,&$con)
{
  $queuenotes = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuenotes' AND siteID=".$selected_page.";",$con));
  if ($queuenotes['OptionValue'] != '')
  {
    echo "<div id='notes' class='notes'>\n";
    echo "<pre>".wordwrap($queuenotes['OptionValue'],125,"\n")."</pre>\n";
    //echo "<pre>".htmlentities($queuenotes['OptionValue'],ENT_QUOTES)."</pre>\n";
    echo "</div>\n";
  }
  else
  {
    echo "<br />\n";
  }
}

function RULES($selected_page,&$con)
{
  $queuerules = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuerules' AND siteID=".$selected_page.";",$con));
  if ($queuerules['OptionValue'] != '')
  {
    echo "<div id='rules' class='rules'>\n";
    echo "<h3>Queue Expectations</h3>\n";
    echo "<pre>".wordwrap($queuerules['OptionValue'],125,"\n")."</pre>\n";
    //echo "<pre>".htmlentities($queuerules['OptionValue'],ENT_QUOTES)."</pre>\n";
    echo "<hr width='50%' />\n";
    echo "</div>\n";
  }
}


function UPDATE_DB_MYCASECOUNT($selected_page,$userID,$current_week,&$con)
{
  for ($i=0;$i<=4;$i++)
  {
    if (($_POST["reg_".$current_week[$i]] != '') and ($_POST["cat1_".$current_week[$i]] != '') and ($_POST["spec_".$current_week[$i]] != '') and ($_POST["tran_".$current_week[$i]] != ''))
    {
      $checkforentry = mysql_query("SELECT * FROM Count WHERE Date = '".$current_week[$i]."' AND userID ='".$userID."' AND siteID=".$selected_page,$con);
      // If there wasn't any data before, and now there is then directly insert it into the table
      if ( mysql_num_rows($checkforentry) == 0 )
      {
        CHECK_TO_SEND_EMAILS($selected_page,$userID,$current_week[$i],$checkchanges,$con);
        $sql="INSERT INTO Count (userID, CatOnes, Special, Regular, Transfer, Date, UpdateDate, siteID)
	            VALUES ('".$userID."',".$_POST["cat1_".$current_week[$i]].",".$_POST["spec_".$current_week[$i]].",".$_POST["reg_".$current_week[$i]].",".$_POST["tran_".$current_week[$i]].",".$current_week[$i].",".mktime().",".$selected_page.")";
      }
      else
      {
        $checkchanges = mysql_fetch_array($checkforentry);

        // If the data for this day ($current_week[$i]) hasn't changed, then don't change the update date
        if (($checkchanges['CatOnes'] == $_POST["cat1_".$current_week[$i]]) and
        ($checkchanges['Regular'] == $_POST["reg_".$current_week[$i]]) and
        ($checkchanges['Transfer'] == $_POST["tran_".$current_week[$i]]) and
        ($checkchanges['Special'] == $_POST["spec_".$current_week[$i]]))
        {
          $updatedate = $checkchanges['UpdateDate'];
        }
        else
        {
          CHECK_TO_SEND_EMAILS($selected_page,$userID,$current_week[$i],$checkchanges,$con);
          $updatedate = mktime();
        }
        $sql="UPDATE Count
        SET 
          CatOnes = '".$_POST["cat1_".$current_week[$i]]."', 
          Special = '".$_POST["spec_".$current_week[$i]]."', 
          Regular = '".$_POST["reg_".$current_week[$i]]."', 
          Transfer = '".$_POST["tran_".$current_week[$i]]."', 
          UpdateDate = '".$updatedate."' 
        WHERE 
          userID = '".$userID."' 
          AND Date = '".$current_week[$i]."' 
          AND siteID = '".$selected_page."'";
      }
      RUN_QUERY($sql,"Values were not updated.",$con);
    }
  }
}


function CHECK_TO_SEND_EMAILS($selected_page,$userID,$current_day,$checkchanges,&$con)
{
  $old_case_total = $checkchanges['CatOnes'] + $checkchanges['Regular'] + $checkchanges['Special'];
  $new_case_total = $_POST["cat1_".$current_day] + $_POST["reg_".$current_day] + $_POST["spec_".$current_day];
  $queuemax = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='queuemax' AND siteID='".$selected_page."';",$con));
  $get_all_on_queue_count = mysql_query("SELECT Shift,Users.userID FROM Users,Schedule WHERE Schedule.Date = ".$current_day." AND Users.userID = Schedule.userID AND Users.Active = 1 AND siteID='".$selected_page."'",$con);
  $j = 0;
  while ($current_user_count = mysql_fetch_array($get_all_on_queue_count))
  {
    $shifts[$j]['Shift']=$current_user_count['Shift'];
    $shifts[$j]['userID']=$current_user_count['userID'];
    if ($current_user_count['userID'] == $userID)
    $current_user_shifts_index = $j;
    $j++;
  }
  // Divide queuemax by 2 if user is on half shift, and round to int so if max is 7 email will be sent going from 2 to 3 as 3.5 would be the max
  $adjustedmax = intval($queuemax['OptionValue'] * $shifts[$current_user_shifts_index]['Shift'] / 2);
  // If user was less than max, but now is greater than max and they are actually on queue
  if (($old_case_total < $adjustedmax) and ($new_case_total >= $adjustedmax) and ($shifts[$current_user_shifts_index]['Shift'] > 0))
  {
    $number_of_maxed = 0;
    for ($k = 0; $k < $j; $k++)
    {
      $case_count_for_user[$k] = mysql_fetch_array(mysql_query("SELECT Regular,CatOnes,Special FROM Count WHERE Date = ".$current_day." AND userID = ".$shifts[$k]['userID']." AND siteID='".$selected_page."'",$con));
      $case_total_for_user[$k] = $case_count_for_user[$k]['Regular'] + $case_count_for_user[$k]['CatOnes'] + $case_count_for_user[$k]['Special'];
      $current_user_adjustedmax = intval($queuemax['OptionValue'] * $shifts[$k]['Shift'] / 2);
      if (($case_total_for_user[$k] < $current_user_adjustedmax) and ($k != $current_user_shifts_index))
      {
        SEND_USER_MAX_EMAIL($selected_page,$shifts[$k]['userID'],$userID,$current_day,$con);
      }
      else
      {
        $number_of_maxed++;
        if ($number_of_maxed == $j) // All on queue have maxed
        {
          SEND_ALL_MAX_EMAIL($selected_page,$current_day,$con);
        }
      }
    }
  }
}


function SEND_USER_MAX_EMAIL($selected_page,$send_email_to_userID,$userID_that_maxed,$max_date,&$con)
{
  $site_name = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options,Sites WHERE OptionName='sitename' AND Options.siteID=Sites.siteID AND SiteName='main';",$con));

  $activeusers = mysql_query("SELECT * FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID=".$selected_page.";",$con);
  while ( $currentuser = mysql_fetch_array($activeusers) )
  {
    if ($currentuser['userID'] == $send_email_to_userID)
    {
      $to = $currentuser['UserEmail'];
      $userName_of_target = $currentuser['UserName'];
    }
    if ($currentuser['userID'] == $userID_that_maxed)
    {
      $replyto = $currentuser['UserEmail'];
      $userName_that_maxed = $currentuser['UserName'];
    }
  }

  $subject = "Queue - ".gmdate("n/j",$max_date)." - ".$userName_that_maxed." maxed";

  $message = "<html>
	<body style=\"margin: 5px;min-width: 800px;font-family: 'Times New Roman', Times, serif;\">
	<style>
	body {margin: 5px;min-width: 800px;font-family:'Times New Roman', Times, serif;text-align:center;}
	</style>
	<h3>Queue</h3>
	".$userName_that_maxed." maxed on ".gmdate("n/j",$max_date)."
	<br />
	<hr width='50%' />
	Sent via: ".$site_name['OptionValue']."<br />
	<a href='".MAIN_DOMAIN."'>".MAIN_DOMAIN."</a>
	</body>
	</html>";
  $from = MAIN_EMAILS_FROM;
  $headers = "MIME-Version: 1.0" . "\r\n";
  $headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
  $headers .= 'From: '.$from."\r\n";
  $headers .= "Reply-To: ".$replyto."\r\n";
  if (mail($to,$subject,$message,$headers))
  {
    echo "Email <span class='success'>sent</span> to: ".$userName_of_target."<br />\n";
  }
  else
  {
    echo "Email was <span class='error'>not sent</span> to: ".$userName_of_target."<br />\n";
  }
}


function SEND_ALL_MAX_EMAIL($selected_page,$max_date,&$con)
{
  $site_name = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options,Sites WHERE OptionName='sitename' AND Options.siteID=Sites.siteID AND SiteName='main';",$con));

  $activeusers = mysql_query("SELECT * FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."';",$con);
  while ( $currentuser = mysql_fetch_array($activeusers) )
  {
    if ($currentuser['UserEmail'] != "") // Prevent emails from being sent to people that don't have an email
    {
      $to .= $currentuser['UserEmail'].",";
    }
  }

  $subject = "Queue - ".gmdate("n/j",$max_date)." - Everyone on queue maxed";

  $message = "<html>
	<body style=\"margin: 5px;min-width: 800px;font-family: 'Times New Roman', Times, serif;\">
	<style>
	body {margin: 5px;min-width: 800px;font-family:'Times New Roman', Times, serif;text-align:center;}
	</style>
	<h3>Queue</h3>
	Everyone on queue maxed on ".gmdate("n/j",$max_date)."
	<br />
	<hr width='50%' />
	Sent via: ".$site_name['OptionValue']."<br />
	<a href='".MAIN_DOMAIN."'>".MAIN_DOMAIN."</a>
	</body>
	</html>";

  $queuecc = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='queuecc' AND siteID=".$selected_page.";",$con));

  $from = MAIN_EMAILS_FROM;
  $headers = "MIME-Version: 1.0" . "\r\n";
  $headers .= "Content-type:text/html;charset=iso-8859-1" . "\r\n";
  $headers .= 'From: '.$from."\r\n";
  $headers .= 'Cc: '.$queuecc['OptionValue']."\r\n";
  if (mail($to,$subject,$message,$headers))
  {
    echo "Email <span class='success'>sent</span> to everyone.<br />\n";
  }
  else
  {
    echo "Email was <span class='error'>not sent</span> to everyone.<br />\n";
  }
}


function TABLE_MYCASECOUNT($selected_page,$userID,$current_week,&$con)
{
  $sql = "SELECT UserName FROM Users,UserSites
  WHERE Active=1 
  AND Users.userID=UserSites.userID 
  AND siteID='".$selected_page."'
  AND Users.userID='".$userID."';";
  $username = mysql_fetch_array(mysql_query($sql,$con));

  echo "    <form name='mycasecount' method='post' action=''>\n";
  echo "      <input type='hidden' name='selecteddate' value='".$_GET['selecteddate']."' />\n";
  echo "      <table class='mycasecount'>\n";
  echo "        <tr class='mycasecount'>\n";
  echo "          <th class='mycasecount'><span class='selecteduser'>".$username['UserName']."</span></th>\n";
  
  for ($i=0;$i<5;$i++)
  {
    $sql = "SELECT *
            FROM PhoneSchedule 
            WHERE Date = '".$current_week[$i]."' 
              AND userID = '".$userID."'";
    echo "          <th class='mycasecount";
    if (mysql_num_rows(mysql_query($sql,$con))>0)
    echo " onphones";
    echo "'>".gmdate("D n/j",$current_week[$i])."</th>\n";
  }

  echo "        </tr>\n";
  echo "        <tr class='mycasecount'>\n";
  echo "          <th class='mycasecount'><span class='mycasecount_regular'>Regular</span></th>\n";

  for ($i=0;$i<=4;$i++)
  {
    echo "          <td class='mycasecount'>\n";
    echo "          <input type='text' class='mycasecount' name='reg_".$current_week[$i]."' onclick='this.focus();this.select();' onchange='mycasecount.submit();' onkeypress='return enterSubmit(this,event);'";
    $getcount= mysql_query("SELECT Regular FROM Count WHERE Date = '".$current_week[$i]."' AND userID = '".$userID."' AND siteID='".$selected_page."'",$con);
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
    echo "          <input type='text' class='mycasecount' name='cat1_".$current_week[$i]."' onclick='this.focus();this.select();' onchange='mycasecount.submit();' onkeypress='return enterSubmit(this,event);'";
    $getcount= mysql_query("SELECT CatOnes FROM Count WHERE Date = '".$current_week[$i]."' AND userID = '".$userID."' AND siteID='".$selected_page."'",$con);
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
    echo "          <input type='text' class='mycasecount' name='spec_".$current_week[$i]."' onclick='this.focus();this.select();' onchange='mycasecount.submit();' onkeypress='return enterSubmit(this,event);'";
    $getcount= mysql_query("SELECT Special FROM Count WHERE Date = '".$current_week[$i]."' AND userID = '".$userID."' AND siteID='".$selected_page."'",$con);
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
    echo "          <input type='text' class='mycasecount' name='tran_".$current_week[$i]."' onclick='this.focus();this.select();' onchange='mycasecount.submit();' onkeypress='return enterSubmit(this,event);'";
    $getcount= mysql_query("SELECT Transfer FROM Count WHERE Date = '".$current_week[$i]."' AND userID = '".$userID."' AND siteID='".$selected_page."'",$con);
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


function TABLE_CURRENTHISTORY($selected_page,$showdetails,$showdetails_cat1,$timezone,$userID,$current_week,&$con)
{
  $activeusers = mysql_query("SELECT UserName,Users.userID FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."' ORDER BY UserName ASC",$con);

  echo "    <table class='currenthistory'>\n";
  echo "      <tr class='currenthistory'>\n";
  echo "        <th class='currenthistory'>Name</th>\n";
  for ($i=0;$i<5;$i++)
  {
    echo "        <th class='currenthistory";
    $current_local_time = time() + 60*60*($timezone) + $dst_value_from_current_time_sec;
    if (($current_local_time >= $current_week[$i]) and ($current_local_time < ($current_week[$i]+24*3600)))
    echo " currentdate";
    echo "'>".gmdate("D",$current_week[$i])."&nbsp;".gmdate("n/j",$current_week[$i])."</th>\n";
  }
  echo "      </tr>\n";
  while ( $currentuser = mysql_fetch_array($activeusers) )
  {
    echo "      <tr class='currenthistory'>\n";
    for ($col=1; $col<=6; $col++)
    {
      if ($col==1)
      {
        echo "        <td class='currenthistory";
        echo "'>";
        if ($userID == $currentuser['userID'])
        echo "<span class='selecteduser'>".$currentuser['UserName']."</span>";
        else
        echo $currentuser['UserName'];
        echo "</td>\n";
      }
      else
      {
        $sql = "SELECT Regular,CatOnes,Special,Transfer,UpdateDate,Date
        FROM Count 
        WHERE userID='".$currentuser['userID']."' 
          AND Date='".$current_week[$col-2]."' 
          AND siteID=".$selected_page.";";
        $usercounts = mysql_fetch_array(mysql_query($sql,$con));
        echo "        <td class='currenthistory";
        $currentusershift = mysql_fetch_array(mysql_query("SELECT Shift FROM Schedule WHERE Date='".$current_week[$col-2]."' AND userID='".$currentuser['userID']."' AND siteID=".$selected_page,$con));
        // This added a class to identify selected user and on shift
        if ((($currentuser['userID'] == $userID) and ($userID != '')) and ( $currentusershift['Shift'] > 0 ))
        echo " selecteduseronshiftcell";
        // This added a class to identify selected user
        else if (($currentuser['userID'] == $userID) and ($userID != ''))
        echo " selectedusercell";
        else if ( $currentusershift['Shift'] > 0 )
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
        elseif  (( $showdetails_cat1 == 'on' ) and ( $catonecases > 0 ))
        {
          echo "        (<span class='mycasecount_catones'>".$catonecases."</span>)\n";
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
        echo "        </td>\n";
      }
    }
    echo "      </tr>\n";
  }
  echo "    </table>\n";

  echo "    <div style='width:100%;position:relative;'>\n";
  echo "      <div style='width:50%;text-align:left;margin-right:auto;'>\n";
  echo "        <a href='export.php?export_page={$selected_page}&amp;export_date={$current_week[0]}' target='_blank'><img src='./images/icxls.gif' width='16' height='16' alt='Export' /></a>\n";
  //echo "        <a href='export.php?export_page={$selected_page}&amp;export_date={$current_week[0]}' target='_blank'><img src='./images/excel_file.png' alt='Export' /></a>\n";
  echo "      </div>\n";
  echo "      <div style='width:50%;position:absolute;top:0px;right:0px;text-align:right;'>\n";
  echo "      <form method='post' name='showdetailsform' action=''>\n";
  echo "        Details:\n";
  echo "        <input type='hidden' name='showdetailssent' value='1' />\n";
  echo "        <input type='checkbox' name='showdetails'";
  if ( $showdetails == 'on' )
  echo " checked='checked'";
  echo " onclick='showdetailsform.submit();' /> All\n";
  echo "        <input type='checkbox' name='showdetails_cat1'";
  if ( $showdetails_cat1 == 'on' )
  echo " checked='checked'";
  echo " onclick='showdetailsform.submit();' /> (Cat1)\n";
  echo "        <input type='submit' id='showdetails_submit' value='update' />\n";
  echo "    </form>\n";
  echo "      <script type='text/javascript'>\n";
  echo "        <!--\n";
  echo "        document.getElementById('showdetails_submit').style.display='none'; // hides button if JS is enabled-->\n";
  echo "      </script>\n";
  echo "      </div>\n";
  echo "    </div>\n";
}

function TABLE_CURRENTPHONES($userID,$timezone,$selected_page,$current_week,&$con)
{
  $sql = "SELECT UserName,Users.userID
  FROM Users,UserSites 
  WHERE Active=1 
    AND Users.userID=UserSites.userID 
    AND siteID='".$selected_page."' 
  ORDER BY UserName;";

  $activeusers = mysql_query($sql,$con);
  $dst_value_from_current_time_sec = date("I",$usercounts['Date'])*60*60; // This is a 1*60*60 if DST is set on the time
  $current_local_time = time() + 60*60*($timezone) + $dst_value_from_current_time_sec;

  echo "<table class='phoneshift'>\n";

  echo "<tr class='phoneshift'>\n";
  echo "  <th class='phoneshift'>Shift</th>\n";
  for ($i=0;$i<5;$i++)
  {
    echo "        <th class='phoneshift";
    if (($current_local_time >= $current_week[$i]) and ($current_local_time < ($current_week[$i]+24*3600)))
    echo " currentdate";
    echo "'>".gmdate("D n/j",$current_week[$i])."</th>\n";
  }
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
        echo "  <td class='phoneshift";
        $normalized_now = strtotime(gmdate("g:ia",$current_local_time));
        $normalized_start = strtotime(gmdate("g:ia",$phoneshifs[$shift_index]['start']));
        $normalized_end = strtotime(gmdate("g:ia",$phoneshifs[$shift_index]['end']));
        if (($normalized_now >= $normalized_start) and ($normalized_now < $normalized_end))
        echo " currentdate";
        echo "'><div class='phoneshift'>";
        echo gmdate("g:ia",$phoneshifs[$shift_index]['start'])." - ".gmdate("g:ia",$phoneshifs[$shift_index]['end']);
        if ($shift_index==2 or $shift_index==3) echo "<br />Cover";
        echo "</div></td>\n";
      }
      else
      {
        $sql = "SELECT UserName,Users.userID
        FROM Users,PhoneSchedule,UserSites
        WHERE Active=1 
          AND Users.userID=PhoneSchedule.userID 
          AND Users.userID=UserSites.userID
          AND UserSites.siteID=".$selected_page."
          AND Shift='".$shift_index."' 
          AND Date='".$current_week[$col-2]."' 
        ORDER BY UserName;";
        $users_on_shift_query = mysql_query($sql,$con);

        $onqueue = 0;
        $user_log = '';
        while ( $users_on_shift = mysql_fetch_array($users_on_shift_query) )
        {
          if ($users_on_shift['userID'] == $userID)
          {
            $onqueue = 1;
            $user_log .= "        <span class='selecteduser'>".$users_on_shift['UserName']."</span><br />\n";
          }
          else
          {
            $user_log .= "        ".$users_on_shift['UserName']."<br />\n";
          }
        }

        echo "  <td class='phoneshift\n";
        if ($onqueue == 1)
        {
          echo " selectedusercell_queue";
        }
        echo "'>";
        echo $user_log;
        echo "  </td>\n";
      }
    }
    echo "</tr>\n";
  }

  echo "</table>\n";
}

function TABLE_CURRENTQUEUE($selected_page,$userID,$current_week,&$con)
{
  echo "    <table  class='currentqueue'>\n";

  for ($i = 0; $i <= 4; $i++)
  {
    $shift = mysql_fetch_array(mysql_query("SELECT COUNT(Shift) FROM Users,Schedule WHERE Users.userID = Schedule.userID AND Users.Active = 1 AND siteID = ".$selected_page." AND Date = ".$current_week[$i],$con));
    $shiftcount[$i] = $shift['COUNT(Shift)'];
    $currentday = mysql_query("SELECT UserName,Shift,Users.userID FROM Users,Schedule WHERE Schedule.Date = ".$current_week[$i]." AND Users.userID = Schedule.userID AND Users.Active = 1 AND siteID = ".$selected_page,$con);
    $j = 0;
    while ($getarray = mysql_fetch_array($currentday)) { $namesAndShifts[$i][$j++] = $getarray; }
  }

  rsort($shiftcount,SORT_NUMERIC);

  $queuemax = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='queuemax' AND siteID = ".$selected_page.";",$con));

  for ($row = 1; $row <= $shiftcount[0]; $row++)
  {
    echo "      <tr class='currentqueue'>\n";
    for ($col = 1; $col <= 5; $col++)
    {
      if (($col == 1) and ($row == 1)) {
        echo "        <th class='currentqueue' rowspan='".$shiftcount[0]."'>\n";
        echo "          Queue<br />\n";
        echo "          Max: ".$queuemax['OptionValue']."\n";
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