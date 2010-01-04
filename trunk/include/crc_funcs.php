<?php

function CHECKCOUNTDATES($con)
{
  echo "Beginning CRC in table 'Count'...<br />\n";
  $crc_query = mysql_query("SELECT * FROM Count",$con);
  while ( $crc_row = mysql_fetch_array($crc_query) )
  {
    echo " countID: ".$crc_row['countID']." ";
    echo " Date: ".$crc_row['Date']." ";
    echo " Checking Time: ".gmdate('r',$crc_row['Date'])." ";
    if (
    (gmdate('G',$crc_row['Date'])==0) and
    (gmdate('i',$crc_row['Date'])==0) and
    (gmdate('s',$crc_row['Date'])==0) and
    (gmdate('w',$crc_row['Date'])!=0) and
    (gmdate('w',$crc_row['Date'])!=6)
    )
    echo "- <span class='success'>Good</span><br />\n";
    else
    {
      echo "<br />\n";
      if ((gmdate('G',$crc_row['Date'])!=0) or (gmdate('i',$crc_row['Date'])!=0) or (gmdate('s',$crc_row['Date'])!=0))
      {
        echo "- <span class='error'>ERROR</span>: The time should be '00:00:00' not '".gmdate('H:i:s',$crc_row['Date'])."'<br />\n";
        $date_at_zero = strtotime(gmdate("d",$crc_row['Date'])." ".gmdate("M",$crc_row['Date'])." ".gmdate("Y",$crc_row['Date'])." 00:00:00 +0000");
        $sql="UPDATE Count SET Date = '".$date_at_zero."' WHERE countID = '".$crc_row['countID']."'";
        //RUN_QUERY($sql,"Entry was not corrected.",$con);
        $check = mysql_fetch_array(mysql_query("SELECT Date FROM Count WHERE countID = '".$crc_row['countID']."'",$con));
        echo "...updated to: ".gmdate('r',$check['Date'])."<br />\n";
      }
      if ((gmdate('w',$crc_row['Date'])==0) or (gmdate('w',$crc_row['Date'])==6))
      {
        echo "- <span class='error'>ERROR</span>: The day should be 'Mon-Fri' not '".gmdate('D',$crc_row['Date'])."'<br />\n";
        echo "...CRC cannot automatically correct this error, because it is unsure what this date should be set to<br />\n";
      }
    }
  }
}

function CHECKSCHEDULEDATES($con)
{
  echo "Beginning CRC in table 'Schedule'...<br />\n";
  $crc_query = mysql_query("SELECT * FROM Schedule",$con);
  while ( $crc_row = mysql_fetch_array($crc_query) )
  {
    echo " scheduleID: ".$crc_row['scheduleID']." ";
    echo " Date: ".$crc_row['Date']." ";
    echo " Checking Time: ".gmdate('r',$crc_row['Date'])." ";
    if (
    (gmdate('G',$crc_row['Date'])==0) and
    (gmdate('i',$crc_row['Date'])==0) and
    (gmdate('s',$crc_row['Date'])==0) and
    (gmdate('w',$crc_row['Date'])!=0) and
    (gmdate('w',$crc_row['Date'])!=6)
    )
    echo "- <span class='success'>Good</span><br />\n";
    else
    {
      echo "<br />\n";
      if ((gmdate('G',$crc_row['Date'])!=0) or (gmdate('i',$crc_row['Date'])!=0) or (gmdate('s',$crc_row['Date'])!=0))
      {
        echo "- <span class='error'>ERROR</span>: The time should be '00:00:00' not '".gmdate('H:i:s',$crc_row['Date'])."'<br />\n";
        $date_at_zero = strtotime(gmdate("d",$crc_row['Date'])." ".gmdate("M",$crc_row['Date'])." ".gmdate("Y",$crc_row['Date'])." 00:00:00 +0000");
        $sql="UPDATE Schedule SET Date = '".$date_at_zero."' WHERE scheduleID = '".$crc_row['scheduleID']."'";
        //RUN_QUERY($sql,"Entry was not corrected.",$con);
        $check = mysql_fetch_array(mysql_query("SELECT Date FROM Schedule WHERE scheduleID = '".$crc_row['scheduleID']."'",$con));
        echo "...updated to: ".gmdate('r',$check['Date'])."<br />\n";
      }
      if ((gmdate('w',$crc_row['Date'])==0) or (gmdate('w',$crc_row['Date'])==6))
      {
        echo "- <span class='error'>ERROR</span>: The day should be 'Mon-Fri' not '".gmdate('D',$crc_row['Date'])."'<br />\n";
        echo "...CRC cannot automatically correct this error, because it is unsure what this date should be set to<br />\n";
      }
    }
  }
}

function ADDQUEUECCTOOPTIONS(&$con)
{
  $number_queue_options = mysql_num_rows(mysql_query("SELECT optionID FROM Options WHERE OptionName='queuecc';",$con));
  if ($number_queue_options >= 1)
  {
    echo "The 'queuecc' option was already added.<br />\n";
  }
  else
  {
    echo "Adding Queue CC to 'Options'...<br />\n";
    $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue)
	        VALUES ('queuecc','CC for MAX and Queue emails:','');";
    if (RUN_QUERY($sql,"Adding Queue CC failed",$con))
    echo "- The column was <span class='success'>Added</span><br />\n";
  }
}

function ADDEMAILCOLUMNTOUSERS(&$con)
{
  echo "Adding column 'UserEmail' to 'Users' table...<br />\n";
  $sql="ALTER TABLE Users ADD UserEmail varchar(255)";
  if (RUN_QUERY($sql,"UserEmail column was not added.",$con))
  echo "- The column was <span class='success'>Added</span><br />\n";
}

function ADDQUEUEMAXTOOPTIONS(&$con)
{
  $number_queue_options = mysql_num_rows(mysql_query("SELECT optionID FROM Options WHERE OptionName='queuemax';",$con));
  if ($number_queue_options >= 1)
  {
    echo "The 'queuemax' option was already added.<br />\n";
  }
  else
  {
    echo "Adding Queue max to 'Options'...<br />\n";
    $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue)
	        VALUES ('queuemax','Queue max:','8');";
    if (RUN_QUERY($sql,"Adding Queue max failed",$con))
    echo "- The column was <span class='success'>Added</span><br />\n";
  }
}

function REMOVEUNIQUEFROMOPTIONNAMES(&$con)
{
  $sql="ALTER TABLE Options DROP INDEX OptionName;";
  if (RUN_QUERY($sql,"Removing UNIQUE from OptionName in Options failed",$con))
  echo "- Removing UNIQUE from OptionName in Options <span class='success'>completed</span><br />\n";
}

function ADDUNIQUETOOPTIONNAMES_PERPAGE(&$con)
{
  $sql="ALTER TABLE Options ADD CONSTRAINT OptionName_siteID UNIQUE (OptionName,siteID);";
  if (RUN_QUERY($sql,"Adding UNIQUE to OptionName and siteID in Options failed",$con))
  echo "- Adding UNIQUE to OptionName and siteID in Options <span class='success'>completed</span><br />\n";
}

function ADD_OPTIONNAME_TO_OPTIONS(&$con)
{
  $sql="ALTER TABLE Options ADD siteID int;";
  if (RUN_QUERY($sql,"Add siteID to Options failed",$con))
  echo "- Add siteID to Options <span class='success'>completed</span><br />\n";
}

function UPDATE_OPTIONS_WITH_siteID(&$con)
{
  $sql="UPDATE Options SET siteID = '3' WHERE OptionName = 'sitename';";
  if (RUN_QUERY($sql,"Updating siteID in Options failed",$con))
  echo "- Updating siteID in Options <span class='success'>completed</span><br />\n";

  $sql="UPDATE Options SET siteID = '3' WHERE OptionName = 'queuecc'";
  if (RUN_QUERY($sql,"Updating siteID in Options failed",$con))
  echo "- Updating siteID in Options <span class='success'>completed</span><br />\n";

  $sql="UPDATE Options SET siteID = '3' WHERE OptionName = 'queuerules'";
  if (RUN_QUERY($sql,"Updating siteID in Options failed",$con))
  echo "- Updating siteID in Options <span class='success'>completed</span><br />\n";

  $sql="UPDATE Options SET siteID = '3' WHERE OptionName = 'queuenotes'";
  if (RUN_QUERY($sql,"Updating siteID in Options failed",$con))
  echo "- Updating siteID in Options <span class='success'>completed</span><br />\n";

  $sql="UPDATE Options SET siteID = '3' WHERE OptionName = 'queuemax'";
  if (RUN_QUERY($sql,"Updating siteID in Options failed",$con))
  echo "- Updating siteID in Options <span class='success'>completed</span><br />\n";

  $sql="UPDATE Options SET siteID = '1' WHERE OptionName = 'password'";
  if (RUN_QUERY($sql,"Updating siteID in Options failed",$con))
  echo "- Updating siteID in Options <span class='success'>completed</span><br />\n";
}

function ADD_REPLYTO_OPTION(&$con)
{
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('replyto','Reply-to for Queue emails:','','3');";
  if (RUN_QUERY($sql,"Adding 'replyto' option failed",$con))
  echo "- Adding 'replyto' option <span class='success'>completed</span><br />\n";
}

function ADD_GENERAL_OPTIONS(&$con)
{
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('sitename','Name of this site:','General','1');";
  if (RUN_QUERY($sql,"Adding 'sitename' option failed",$con))
  echo "- Adding 'sitename' option <span class='success'>completed</span><br />\n";
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('mainnotes','Main page notes:','Intro','1');";
  if (RUN_QUERY($sql,"Adding 'mainnotes' option failed",$con))
  echo "- Adding 'mainnotes' option <span class='success'>completed</span><br />\n";
}

function ADD_PHONESHIFT_OPTIONS(&$con)
{
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('sitename','Name of this site:','Phone Shifts','2');";
  if (RUN_QUERY($sql,"Adding 'sitename' option failed",$con))
  echo "- Adding 'sitename' option <span class='success'>completed</span><br />\n";
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('phonenotes','Phone Shift notes:','Intro','2');";
  if (RUN_QUERY($sql,"Adding 'phonenotes' option failed",$con))
  echo "- Adding 'phonenotes' option <span class='success'>completed</span><br />\n";
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('phonescc','CC for Phone Shift emails:','','2');";
  if (RUN_QUERY($sql,"Adding 'phonescc' option failed",$con))
  echo "- Adding 'phonescc' option <span class='success'>completed</span><br />\n";
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('replyto','Reply-to for Phone Shift emails:','','2');";
  if (RUN_QUERY($sql,"Adding 'replyto' option failed",$con))
  echo "- Adding 'replyto' option <span class='success'>completed</span><br />\n";
}

function ADD_SITES(&$con)
{
  $number_sites = mysql_num_rows(mysql_query("SELECT SiteName FROM Sites WHERE SiteName='main';",$con));
  if ($number_sites == 0)
  {
    $sql="INSERT INTO Sites (SiteName, Active)
	        VALUES ('main',1);";
    if (RUN_QUERY($sql,"Adding 'main' site failed",$con))
    echo "- Adding 'main' site <span class='success'>completed</span><br />\n";
  }
  else
  {
    echo "The 'main' site already exists<br />\n";
  }
  $number_sites = mysql_num_rows(mysql_query("SELECT SiteName FROM Sites WHERE SiteName='phoneshift';",$con));
  if ($number_sites == 0)
  {
    $sql="INSERT INTO Sites (SiteName, Active)
	        VALUES ('phoneshift',1);";
    if (RUN_QUERY($sql,"Adding 'phoneshift' site failed",$con))
    echo "- Adding 'phoneshift' site <span class='success'>completed</span><br />\n";
  }
  else
  {
    echo "The 'phoneshift' site already exists<br />\n";
  }
  $number_sites = mysql_num_rows(mysql_query("SELECT SiteName FROM Sites WHERE SiteName='skillset';",$con));
  if ($number_sites == 0)
  {
    $sql="INSERT INTO Sites (SiteName, Active)
	        VALUES ('skillset',1);";
    if (RUN_QUERY($sql,"Adding 'skillset' site failed",$con))
    echo "- Adding 'skillset' site <span class='success'>completed</span><br />\n";
  }
  else
  {
    echo "The 'skillset' site already exists<br />\n";
  }
}

function ADDFOREIGNKEYTOOPTION_siteID(&$con)
{
  $sql="ALTER TABLE Options ADD CONSTRAINT siteID FOREIGN KEY (siteID) REFERENCES Sites(siteID);";
  if (RUN_QUERY($sql,"Adding FOREIGN KEY to siteID in Options failed",$con))
  echo "- Adding FOREIGN KEY to siteID in Options <span class='success'>completed</span><br />\n";
}

function DROP_UNIQUE_USERNAME(&$con)
{
  $sql="ALTER TABLE Users DROP INDEX UserName;";
  if (RUN_QUERY($sql,"Removing Unique from table Users on UserName failed",$con))
  echo "- Removing Unique from table Users on UserName <span class='success'>completed</span><br />\n";
}

function ADD_UNIQUE_usersites_IDs(&$con)
{
  $sql="ALTER TABLE UserSites ADD CONSTRAINT userID_siteID UNIQUE (userID,siteID);";
  if (RUN_QUERY($sql,"Adding Unique to table UserSites on userID,siteID failed",$con))
  echo "- Adding Unique to table UserSites on userID,siteID <span class='success'>completed</span><br />\n";
}

function ADD_siteID_TO_SCHEDULE(&$con)
{
  $sql="ALTER TABLE Schedule ADD siteID int;";
  if (RUN_QUERY($sql,"Add siteID to Schedule failed",$con))
  echo "- Add siteID to Schedule <span class='success'>completed</span><br />\n";
  
  $number_sites = mysql_query("SELECT siteID FROM Schedule WHERE siteID='';",$con);
  if (mysql_num_rows($number_sites) > 0)
  {
    $sql="UPDATE Schedule SET siteID = '3'";
    if (RUN_QUERY($sql,"Updating siteID in Schedule failed",$con))
    echo "- Updating siteID in Schedule <span class='success'>completed</span><br />\n";
  }
  else
  {
    echo "All Schedule entries have a 'siteID'<br />\n";
  }
  
  $sql="ALTER TABLE Schedule ADD CONSTRAINT siteID FOREIGN KEY (siteID) REFERENCES Sites(siteID)";
  if (RUN_QUERY($sql,"Adding FOREIGN KEY (siteID) to table Schedule failed",$con))
  echo "- Adding FOREIGN KEY (siteID) to table Schedule <span class='success'>completed</span><br />\n";
}

function ADD_siteID_TO_COUNT(&$con)
{
  $sql="ALTER TABLE Count ADD siteID int;";
  if (RUN_QUERY($sql,"Add siteID to Count failed",$con))
  echo "- Add siteID to Count <span class='success'>completed</span><br />\n";
  
  $number_sites = mysql_query("SELECT siteID FROM Count WHERE siteID='';",$con);
  if (mysql_num_rows($number_sites) > 0)
  {
    $sql="UPDATE Schedule SET siteID = '3'";
    if (RUN_QUERY($sql,"Updating siteID in Count failed",$con))
    echo "- Updating siteID in Count <span class='success'>completed</span><br />\n";
  }
  else
  {
    echo "All Schedule entries have a 'siteID'<br />\n";
  }
  
  $sql="ALTER TABLE Count ADD CONSTRAINT count_siteID FOREIGN KEY (siteID) REFERENCES Sites(siteID)";
  if (RUN_QUERY($sql,"Adding FOREIGN KEY (siteID) to table Count failed",$con))
  echo "- Adding FOREIGN KEY (siteID) to table Count <span class='success'>completed</span><br />\n";
}

?>