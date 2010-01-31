<?php

function BUILD_TABLE_USERS(&$con)
{
  $sql = "CREATE TABLE Users
(
userID int NOT NULL AUTO_INCREMENT, 
CONSTRAINT userID PRIMARY KEY(userID),
UserName varchar(255) NOT NULL,
UserEmail varchar(255),
Active bit NOT NULL
)";
  return DB_TABLE_CREATE($sql,$con);
}

function BUILD_TABLE_COUNT(&$con)
{
  $sql = "CREATE TABLE Count
(
countID int NOT NULL AUTO_INCREMENT, 
CONSTRAINT countID PRIMARY KEY(countID),
userID int,
CONSTRAINT count_userID FOREIGN KEY (userID) REFERENCES Users(userID),
CatOnes int,
Special int,
Regular int,
Transfer int,
Date int,
UpdateDate int,
siteID int,
CONSTRAINT count_siteID FOREIGN KEY (siteID) REFERENCES Sites(siteID)
)";
  return DB_TABLE_CREATE($sql,$con);
}

function BUILD_TABLE_SCHEDULE(&$con)
{
  $sql = "CREATE TABLE Schedule
(
scheduleID int NOT NULL AUTO_INCREMENT, 
CONSTRAINT scheduleID PRIMARY KEY(scheduleID),
userID int,
CONSTRAINT schedule_userID FOREIGN KEY (userID) REFERENCES Users(userID),
siteID int,
CONSTRAINT schedule_siteID FOREIGN KEY (siteID) REFERENCES Sites(siteID),
Date int,
Shift smallint
)";
  return DB_TABLE_CREATE($sql,$con);
}

function BUILD_TABLE_PHONESCHEDULE(&$con)
{
  $sql = "CREATE TABLE PhoneSchedule
(
phonescheduleID int NOT NULL AUTO_INCREMENT, 
CONSTRAINT phonescheduleID PRIMARY KEY(phonescheduleID),
userID int,
CONSTRAINT phoneschedule_userID FOREIGN KEY (userID) REFERENCES Users(userID),
Date int,
Shift smallint,
CONSTRAINT phoneschedule_userID_Date_Shift UNIQUE (userID,Date,Shift)
)";
  return DB_TABLE_CREATE($sql,$con);
}

function BUILD_TABLE_OPTIONS(&$con)
{
  $sql = "CREATE TABLE Options
(
optionID int NOT NULL AUTO_INCREMENT, 
CONSTRAINT optionID PRIMARY KEY(optionID),
OptionName varchar(255),
OptionDesc varchar(255),
OptionValue text(200000),
siteID int,
CONSTRAINT options_OptionName_siteID UNIQUE (OptionName,siteID),
CONSTRAINT options_siteID FOREIGN KEY (siteID) REFERENCES Sites(siteID)
)";
  return DB_TABLE_CREATE($sql,$con);
}

function BUILD_TABLE_SITES(&$con)
{
  $sql = "CREATE TABLE Sites
(
siteID int NOT NULL AUTO_INCREMENT, 
CONSTRAINT siteID PRIMARY KEY(siteID),
SiteName varchar(255),
Active bit NOT NULL
)";
  return DB_TABLE_CREATE($sql,$con);
}

function BUILD_TABLE_USERSITES(&$con)
{
  $sql = "CREATE TABLE UserSites
(
usersiteID int NOT NULL AUTO_INCREMENT, 
CONSTRAINT usersiteID PRIMARY KEY(usersiteID),
userID int,
CONSTRAINT usersites_userID FOREIGN KEY (userID) REFERENCES Users(userID),
siteID int,
CONSTRAINT usersites_siteID FOREIGN KEY (siteID) REFERENCES Sites(siteID),
CONSTRAINT usersites_userID_siteID UNIQUE (userID,siteID)
)";
  return DB_TABLE_CREATE($sql,$con);
}

function BUILD_TABLE_SENTEMAILS(&$con)
{
  $sql = "CREATE TABLE SentEmails
(
sentemailID int NOT NULL AUTO_INCREMENT, 
CONSTRAINT sentemailID PRIMARY KEY(sentemailID),
Date int,
Shift smallint,
userID int,
CONSTRAINT sentemails_userID FOREIGN KEY (userID) REFERENCES Users(userID),
siteID int,
CONSTRAINT sentemails_siteID FOREIGN KEY (siteID) REFERENCES Sites(siteID),
scheduleID int
)";
  return DB_TABLE_CREATE($sql,$con);
}

function CREATE_DEFAULT_OPTIONS(&$con)
{
  $main_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites WHERE SiteName='main';",$con));
  $phone_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites WHERE SiteName='phoneshift';",$con));
  $skillset_pages = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites WHERE SiteName='skillset';",$con));

  $options_exist = mysql_num_rows(mysql_query("SELECT OptionName FROM Options WHERE siteID='".$main_page['siteID']."';",$con));
  if ($options_exist == 0)
  {
    CREATE_DEFAULT_SKILLSET($skillset_pages['siteID'],$con);

    $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('sitename','Name of this site:','General','".$main_page['siteID']."');";
    RUN_QUERY($sql,"'sitename' Default options were not set",$con);
    $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('mainnotes','Main page notes:','Intro','".$main_page['siteID']."');";
    RUN_QUERY($sql,"'queuenotes' Default options were not set",$con);

    $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('sitename','Name of this site:','Phone Shifts','".$phone_page['siteID']."');";
    RUN_QUERY($sql,"'sitename' Default options were not set",$con);
    $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('phonenotes','Phone Shift notes:','Intro','".$phone_page['siteID']."');";
    RUN_QUERY($sql,"'phonenotes' Default options were not set",$con);
    $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('phonescc','CC for Phone Shift emails:','','".$phone_page['siteID']."');";
    RUN_QUERY($sql,"'phonescc' Default options were not set",$con);
    $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('replyto','Reply-to for Phone Shift emails:','','".$phone_page['siteID']."');";
    RUN_QUERY($sql,"'replyto' Default options were not set",$con);
  }

}

function CREATE_DEFAULT_SITES(&$con)
{
  $number_sites = mysql_num_rows(mysql_query("SELECT SiteName FROM Sites WHERE SiteName='main';",$con));
  if ($number_sites == 0)
  {
    $sql="INSERT INTO Sites (SiteName, Active)
            VALUES ('main',1);";
    RUN_QUERY($sql,"Adding 'main' site failed",$con);
  }

  $number_sites = mysql_num_rows(mysql_query("SELECT SiteName FROM Sites WHERE SiteName='phoneshift';",$con));
  if ($number_sites == 0)
  {
    $sql="INSERT INTO Sites (SiteName, Active)
            VALUES ('phoneshift',1);";
    RUN_QUERY($sql,"Adding 'phoneshift' site failed",$con);
  }

  $number_sites = mysql_num_rows(mysql_query("SELECT SiteName FROM Sites WHERE SiteName='skillset';",$con));
  if ($number_sites == 0)
  {
    $sql="INSERT INTO Sites (SiteName, Active)
            VALUES ('skillset',1);";
    RUN_QUERY($sql,"Adding 'skillset' site failed",$con);
  }
}

function CREATE_DEFAULT_SKILLSET($siteID,&$con)
{
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('sitename','Name of this site:','__ Skillset','".$siteID."');";
  RUN_QUERY($sql,"'sitename' Default options were not set",$con);
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('queuecc','CC for MAX and Queue emails:','','".$siteID."');";
  RUN_QUERY($sql,"'queuecc' Default options were not set",$con);
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('replyto','Reply-to for Queue emails:','','".$siteID."');";
  RUN_QUERY($sql,"'replyto' Default options were not set",$con);
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('queuerules','Queue rules:','Follow the rules','".$siteID."');";
  RUN_QUERY($sql,"'queuerules' Default options were not set",$con);
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('queuenotes','Queue notes:','Some queue notes','".$siteID."');";
  RUN_QUERY($sql,"'queuenotes' Default options were not set",$con);
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('queuemax','Queue max:','8','".$siteID."');";
  RUN_QUERY($sql,"'queuemax' Default options were not set",$con);
}

?>