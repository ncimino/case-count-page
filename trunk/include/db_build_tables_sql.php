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
CONSTRAINT userID FOREIGN KEY (userID) REFERENCES Users(userID),
CatOnes int,
Special int,
Regular int,
Transfer int,
Date int,
UpdateDate int
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
CONSTRAINT userID FOREIGN KEY (userID) REFERENCES Users(userID),
Date int,
Shift smallint
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
CONSTRAINT OptionName_siteID UNIQUE (OptionName,siteID),
CONSTRAINT siteID FOREIGN KEY (siteID) REFERENCES Sites(siteID)
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
CONSTRAINT userID FOREIGN KEY (userID) REFERENCES Users(userID),
siteID int,
CONSTRAINT siteID FOREIGN KEY (siteID) REFERENCES Sites(siteID),
CONSTRAINT userID_siteID UNIQUE (userID,siteID)
)";
    return DB_TABLE_CREATE($sql,$con);
}

?>