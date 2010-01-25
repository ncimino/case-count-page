<?php
include_once("./include/includes.php");
include_once("./include/email_preview_funcs.php");
DB_CONNECT($con);
SET_COOKIES($selected_page,$showdetails,$timezone,$userID,$con);


if ( VERIFY_USER($con) )
{

  $current_week = DETERMINE_WEEK($_GET['preview_date']);
  $selected_page = $_GET['preview_page'];

  $activeusers = mysql_query("SELECT * FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."' ORDER BY UserName;",$con);
  $main_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites Where SiteName = 'main'",$con));
  $phone_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites Where SiteName = 'phoneshift'",$con));
  $site_name = SITE_NAME($main_page['siteID'],$con);
  $page_name = SITE_NAME($selected_page,$con);
  $replyto = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='replyto' AND siteID='".$selected_page."';",$con));

  echo "<h2>Email Events</h2>\n";
  // Send an event email to each on schedule
  if ($phone_page['siteID'] == $selected_page)
  {
    BUILD_PHONE_SCHEDULE_ARRAY($schedule,$current_week['Monday'],$current_week['Friday'],$selected_page,$con);
    foreach ($schedule as $date)
    {
      foreach ($date as $userID => $userID_array)
      {
        foreach ($userID_array as $shift)
        {
          echo "Email event going to " . $shift['username'] . ":<br />\n";
          EVENT_PHONE_EMAIL($replyto['OptionValue'],$site_name,$userID,$shift,$current_week,$selected_page,1,'',$con);
          $email_sent[$userID] = 1;
          echo "<br />\n";
        }
      }
    }
  }
  else
  {
    BUILD_QUEUE_SCHEDULE_ARRAY($schedule,$current_week['Monday'],$current_week['Friday'],$selected_page,$con);

    foreach ($schedule as $userID => $userID_array)
    {
      foreach ($userID_array as $shift)
      {
        echo "Email event going to " . $shift['username'] . ":<br />\n";
        EVENT_QUEUE_EMAIL($replyto['OptionValue'],$site_name,$userID,$shift,$current_week,$selected_page,1,$page_name,$con);
        $email_sent[$userID] = 1;
        echo "<br />\n";
      }
    }
  }

  echo "<h2>Regular Email</h2>\n";
  // Send regular schedule email to those not on shift
  while ( $currentuser = mysql_fetch_array($activeusers) )
  {
    if ($email_sent[$currentuser['userID']] != 1)
    {
      echo "Email going to " . $currentuser['UserName'] . ":<br />\n";
      if ($phone_page['siteID'] == $selected_page)
        PHONE_EMAIL($replyto['OptionValue'],$site_name,$currentuser['UserEmail'],$currentuser['userID'],$currentuser['UserName'],$current_week,$selected_page,1,'',&$con);
      else
        QUEUE_EMAIL($replyto['OptionValue'],$site_name,$currentuser['UserEmail'],$currentuser['userID'],$currentuser['UserName'],$current_week,$selected_page,1,$page_name,&$con);
      echo "<br />\n";
    }
  }
  
  $phonecc = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='queuecc' AND siteID='".$selected_page."';",$con));
  if ($phonecc['OptionValue'] != "") // Prevent emails from being sent to CC that doesn't have an email
  {
    echo "Email going to CC list:<br />\n";
    if ($phone_page['siteID'] == $selected_page)
      PHONE_EMAIL($replyto['OptionValue'],$site_name,$phonecc['OptionValue'],'','',$current_week,$selected_page,1,'',&$con);
    else
      QUEUE_EMAIL($replyto['OptionValue'],$site_name,$phonecc['OptionValue'],'','',$current_week,$selected_page,1,$page_name,&$con);
  }

}
else
{
  VERIFY_FAILED($selected_page,$con);
}
mysql_close($con);

?>