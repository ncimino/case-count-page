<?php

function USERS(&$con)
{
  UPDATE_DB_USERS($con);
  TABLE_USERS($con);
}


function UPDATE_DB_USERS(&$con)
{
if ( $_POST['edituser'] != "" and $_POST['newusername'] != "" )
  {
  $sql="UPDATE Users SET UserName = '".$_POST['newusername']."' WHERE userID = '".$_POST['edituser']."'";
  RUN_QUERY($sql,"User was not updated.",$con);
  }

if ( $_POST['permdeleteuser'] != "" )
  {
  $sql="DELETE FROM Users WHERE userID='".$_POST['permdeleteuser']."'";
  RUN_QUERY($sql,"User was not deleted table Users.",$con);
  $sql="DELETE FROM Schedule WHERE userID='".$_POST['permdeleteuser']."'";
  RUN_QUERY($sql,"User was not deleted table Schedule.",$con);
  $sql="DELETE FROM Count WHERE userID='".$_POST['permdeleteuser']."'";
  RUN_QUERY($sql,"User was not deleted table Count.",$con);
  }

if ( $_POST['restoreuser'] != "" )
  {
  $sql="UPDATE Users SET Active = 1 WHERE userID = '".$_POST['restoreuser']."'";
  RUN_QUERY($sql,"User was not restored.",$con);
  }

if ( $_POST['makeinactive'] != "" )
  {
  $sql="UPDATE Users SET Active = 0 WHERE userID = '".$_POST['makeinactive']."'";
  RUN_QUERY($sql,"User was not deleted.",$con);
  }

if ( $_POST['createuser'] != "" )
  {
  $sql="INSERT INTO Users (UserName, Active) VALUES ('".$_POST['createuser']."',1)";
  RUN_QUERY($sql,"User was not created.",$con);
  }
}


function TABLE_USERS(&$con)
{
$activeusers = mysql_query("SELECT * FROM Users WHERE Active=1;",&$con);
$nonactiveusers = mysql_query("SELECT * FROM Users WHERE Active=0;",&$con);

if ( $_POST['edituser'] != "" and $_POST['newusername'] == "")
  {
  $username = mysql_fetch_array(mysql_query("SELECT UserName FROM Users WHERE userID=".$_POST['edituser'].";",&$con));
  echo "<h2> Change users name: </h2>\n";
  echo "<form method='post'>\n";
  echo "  <input type='hidden' name='edituser' value='".$_POST['edituser']."' />\n";
  echo "  <input type='text' name='newusername' size='10' value='".$username['UserName']."' />\n";
  echo "  <input type='submit' value='Rename' />\n";
  echo "</form>\n"; 
  }

echo "    <h2>Active Users:</h2>\n";
if ( mysql_num_rows($activeusers) == 0 )
  {
  echo "    No active users found. <br />\n";
  } 
else 
  {
  echo "    <table class='activeusers'>\n";
  while ( $currentuser = mysql_fetch_array($activeusers) )
    {
    echo "      <tr>\n";
    echo "        <td class='activeusers_cell'>".$currentuser['UserName']."</td>\n";
    echo "        <td class='activeusers_cell'>\n";
    echo "          <form method='post'>\n";
    echo "            <input type='hidden' name='edituser' value='".$currentuser['userID']."' />\n";
    echo "            <input type='submit' value='Edit' />\n";
    echo "          </form>\n";
    echo "        </td>\n";
    echo "        <td class='activeusers_cell'>\n";
    echo "          <form method='post'>\n";
    echo "            <input type='hidden' name='makeinactive' value='".$currentuser['userID']."' />\n";
    echo "            <input type='submit' value='Move to Inactive' />\n";
    echo "          </form>\n";
    echo "        </td>\n";
    echo "      </tr>\n";
    }
  echo "    </table>\n";
  }

echo "    <h2> Create new user: </h2>\n";
echo "    <form method='post'>\n";
echo "      <input type='text' name='createuser' size='10' value='' />\n";
echo "      <input type='submit' value='Create' />\n";
echo "    </form>\n"; 

echo "    <h2>Inactive Users:</h2>\n";
if ( mysql_num_rows($nonactiveusers) == 0 )
  {
  echo "    No inactive users found. <br />\n";
  }
else
  {
  echo "    <table class='inactiveusers'>\n";
  while ( $currentuser = mysql_fetch_array($nonactiveusers) )
    {
    echo "      <tr>\n";
    echo "        <td class='inactiveusers_cell'>".$currentuser['UserName']."</td>\n";
    echo "        <td class='inactiveusers_cell'>\n";
    echo "          <form method='post'>\n";
    echo "            <input type='hidden' name='restoreuser' value='".$currentuser['userID']."' />\n";
    echo "            <input type='submit' value='Make user active' />\n";
    echo "          </form>\n";
    echo "        </td>\n";
    echo "        <td class='inactiveusers_cell'>\n";
    echo "          <form method='post' id='permdeleteform' name='permdeleteform'>\n";
    echo "            <input type='hidden' name='permdeleteuser' value='".$currentuser['userID']."' />\n";
    echo "            <input type='submit' value='Permanently Delete' onClick='return confirmSubmit(\"Are you sure you want to permanently delete this user?\")' />\n";
    echo "          </form>\n";
    echo "        </td>\n";
    echo "      </tr>\n";
    }
  echo "    </table>\n";
  }
}

?>