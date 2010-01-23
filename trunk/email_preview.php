<?php
include_once("./include/includes.php");
include_once("./include/email_preview_funcs.php");
DB_CONNECT($con);
SET_COOKIES($selected_page,$showdetails,$timezone,$userID,$con);


if ( VERIFY_USER($con) )
{

  $current_week = DETERMINE_WEEK($_GET['preview_date']);

  $activeusers = mysql_query("SELECT * FROM Users,UserSites WHERE Active=1 AND Users.userID=UserSites.userID AND siteID='".$selected_page."' ORDER BY UserName;",$con);
  $site_name = SITE_NAME($selected_page,$con);
  $replyto = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='replyto' AND siteID='".$selected_page."';",$con));

  BUILD_PHONE_SCHEDULE_ARRAY($schedule,$current_week['Monday'],$current_week['Friday'],$selected_page,$con);

  // Send an event email to each on schedule
  foreach ($schedule as $date)
  {
    foreach ($date as $userID => $userID_array)
    {
      foreach ($userID_array as $shift)
      {
        echo "<br />\n";
        echo "Email going to " . $shift['UserName'] . ":<br />\n";
        PRINT_PHONE_EVENT_EMAIL($replyto['OptionValue'],$site_name['OptionValue'],$userID,$shift,$current_week,$selected_page,$con);
        $email_sent[$userID] = 1;
      }
    }
  }

  // Send regular schedule email to those not on shift
  while ( $currentuser = mysql_fetch_array($activeusers) )
  {
    if ($email_sent[$currentuser['userID']] != 1)
    {
      echo "Email going to " . $currentuser['UserName'] . ":<br />\n";
      PRINT_PHONE_EMAIL($replyto['OptionValue'],$site_name['OptionValue'],$currentuser['UserEmail'],$currentuser['userID'],$current_week,$selected_page,$con);
    }
  }

}
else
{
  VERIFY_FAILED($selected_page,$con);
}
mysql_close($con);

?>