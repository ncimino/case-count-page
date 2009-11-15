<?php

function RUN_QUERY($sql,$msg,&$con)
{
  if ( !mysql_query($sql,$con) ) { 
    echo "*Error</span>: " . mysql_error() . " <br />\n"; 
    echo $msg. " <br />\n";
    echo "Trying to execute: " . $sql . " <br />\n"; 
    return 0;
  } else { return 1; }
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

?>