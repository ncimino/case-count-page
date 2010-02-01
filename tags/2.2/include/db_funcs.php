<?php

function RUN_QUERY($sql,$msg,&$con)
{
    if ( !mysql_query($sql,$con) )
    {
        echo "<span class='error'>*Error</span>: " . mysql_error() . " <br />\n";
        echo $msg. " <br />\n";
        echo "Trying to execute: " . $sql . " <br />\n";
        return 0;
    }
    else
    return 1;
}

function DB_TABLE_CREATE($sql,&$con)
{
    $log = substr($sql,0,strpos($sql,"("));
    if(!mysql_query($sql,$con))
    $log .= "<br /><span class='error'>*MySQL Error</span>: ".mysql_error()."<br />";
    else
    $log .= "<br /><span class='success'>MySQL</span>: Success<br />";
    return $log;
}

function DB_CONNECT(&$con)
{
    $con = mysql_connect(DB_SERVER,DB_LOGIN,DB_PASSWORD);
    if (!$con) { die('Could not connect: ' . mysql_error()); }
    mysql_select_db(DB_NAME,$con);
}

function BUILD_ALL_DB_TABLES(&$con)
{
    if (!mysql_query("SELECT * FROM Options",$con) or
    !mysql_query("SELECT * FROM Schedule",$con) or
    !mysql_query("SELECT * FROM PhoneSchedule",$con) or
    !mysql_query("SELECT * FROM Users",$con) or
    !mysql_query("SELECT * FROM Count",$con) or
    !mysql_query("SELECT * FROM Sites",$con) or
    !mysql_query("SELECT * FROM UserSites",$con) or
    !mysql_query("SELECT * FROM SentEmails",$con)
    )
    {
        echo "This is the first time you have viewed this page, or the database isn't setup correctly. <br /><br />";
        echo "This page will try to create the tables in the database: <br />";
        echo BUILD_TABLE_USERS($con);
        echo BUILD_TABLE_SITES($con);
        echo BUILD_TABLE_COUNT($con);
        echo BUILD_TABLE_SCHEDULE($con);
        echo BUILD_TABLE_PHONESCHEDULE($con);
        CREATE_DEFAULT_SITES($con);
        echo BUILD_TABLE_OPTIONS($con);
        CREATE_DEFAULT_OPTIONS($con);
        echo BUILD_TABLE_USERSITES($con);
        echo BUILD_TABLE_SENTEMAILS($con);
        echo "Ignore errors below this line. <br /><br />";
        echo "Note: If you have not set up the vars.php file for your database, or you haven't created a database, then creating the tables failed.<br />";
        echo "You will need to have the vars.php updates and database created before this page will correctly build the tables.<br /><br />";
    }
}

?>