<?php

function USERS(&$con)
{
  UPDATE_DB_USERS($con);
  CONTENT_USERS($con);
}


function UPDATE_DB_USERS(&$con)
{
if ( $_GET['edituser'] != "" and $_GET['newusername'] != "" )
  {
  $sql="UPDATE Users SET UserName = '".$_GET['newusername']."' WHERE userID = '".$_GET['edituser']."'";
  RUN_QUERY($sql,"User was not updated.",$con);
  }

if ( $_GET['edituser'] != "" and $_GET['newusername'] == "")
  {
  $username = mysql_fetch_array(mysql_query("SELECT UserName FROM Users WHERE userID=".$_GET['edituser'].";",&$con));
  echo "<form method='get'> <h2> Change users name: </h2> <input type='hidden' name='edituser' value='".$_GET['edituser']."'><input type='text' name='newusername' size='10' value='".$username['UserName']."'> <input type='submit' value='rename'> </form>"; 
  }

if ( $_GET['permdeleteuser'] != "" )
  {
  $sql="DELETE FROM Users WHERE userID='".$_GET['permdeleteuser']."'";
  RUN_QUERY($sql,"User was not deleted table Users.",$con);
  $sql="DELETE FROM Schedule WHERE userID='".$_GET['permdeleteuser']."'";
  RUN_QUERY($sql,"User was not deleted table Schedule.",$con);
  $sql="DELETE FROM Count WHERE userID='".$_GET['permdeleteuser']."'";
  RUN_QUERY($sql,"User was not deleted table Count.",$con);
  }

if ( $_GET['restoreuser'] != "" )
  {
  $sql="UPDATE Users SET Active = 1 WHERE userID = '".$_GET['restoreuser']."'";
  RUN_QUERY($sql,"User was not restored.",$con);
  }

if ( $_GET['deleteuser'] != "" )
  {
  $sql="UPDATE Users SET Active = 0 WHERE userID = '".$_GET['deleteuser']."'";
  RUN_QUERY($sql,"User was not deleted.",$con);
  }

if ( $_GET['createuser'] != "" )
  {
  $sql="INSERT INTO Users (UserName, Active) VALUES ('".$_GET['createuser']."',1)";
  RUN_QUERY($sql,"User was not created.",$con);
  }
}


function CONTENT_USERS(&$con)
{
$activeusers = mysql_query("SELECT * FROM Users WHERE Active=1;",&$con);
$nonactiveusers = mysql_query("SELECT * FROM Users WHERE Active=0;",&$con);

echo "<h2>Active Users:</h2>\n";
if ( mysql_num_rows($activeusers) == 0 )
  {
  echo "No active users found. <br />\n";
  } 
else 
  {
  while ( $currentuser = mysql_fetch_array($activeusers) )
    echo $currentuser['UserName']." - <a href='?edituser=".$currentuser['userID']."'>Edit</a> - <a href='?deleteuser=".$currentuser['userID']."'>Delete</a> <br />\n";
  }

echo "<form method='get'> <h2> Create new user: </h2> <input type='text' name='createuser' size='10' value=''> <input type='submit' value='create'> </form>"; 

echo "<h2>Inactive Users:</h2>\n";

if ( mysql_num_rows($nonactiveusers) == 0 )
  {
  echo "No inactive users found. <br />\n";
  }
else
  {
  while ( $currentuser = mysql_fetch_array($nonactiveusers) )
    echo $currentuser['UserName']." - <a href='?restoreuser=".$currentuser['userID']."'>Restore</a> - <a href='?permdeleteuser=".$currentuser['userID']."'>Permanently Delete</a> <br />\n";
  }
}

?>