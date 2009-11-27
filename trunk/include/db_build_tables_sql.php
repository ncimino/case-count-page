<?php

function BUILD_TABLE_USERS(&$con)
{
$sql = "CREATE TABLE Users
(
userID int NOT NULL AUTO_INCREMENT, 
PRIMARY KEY(userID),
UserName varchar(255) NOT NULL,
UNIQUE (UserName),
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
PRIMARY KEY(countID),
userID int,
FOREIGN KEY (userID) REFERENCES Users(userID),
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
PRIMARY KEY(scheduleID),
userID int,
FOREIGN KEY (userID) REFERENCES Users(userID),
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
PRIMARY KEY(optionID),
OptionName varchar(255),
UNIQUE (OptionName),
OptionDesc varchar(255),
OptionValue varchar(200000)
)";
return DB_TABLE_CREATE($sql,$con);
}

?>