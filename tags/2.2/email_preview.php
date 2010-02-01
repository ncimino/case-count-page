<?php
include_once("./include/includes.php");
DB_CONNECT($con);
SET_COOKIES($selected_page,$showdetails,$timezone,$userID,$con);


if ( VERIFY_USER($con) )
{

  $current_week = DETERMINE_WEEK($_GET['preview_date']);
  $selected_page = $_GET['preview_page'];
  $preview = 1; // Show email previews instead of sending emails
  $phone_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites Where SiteName = 'phoneshift'",$con));
  
  if ($phone_page['siteID'] == $selected_page)
    SEND_PHONE_EMAIL($selected_page,$current_week,$preview,$con);
  else
    SEND_QUEUE_EMAIL($selected_page,$current_week,$preview,$con);
}
else
{
  VERIFY_FAILED($selected_page,$con);
}
mysql_close($con);

?>