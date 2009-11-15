<?php

function USER_LOGIN()
{
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
<meta name="author" content="<? echo AUTHOR ?>" />
<meta name="description" content="<? echo DESCRIPTION ?>" />
<meta name="keywords" content="<? echo SITE_NAME.", ".KEYWORDS ?>" />
<title><? echo SITE_NAME ?></title>
<link rel="stylesheet" href="<? echo MAIN_CSS_FILE ?>" />
<!--script src='http://...js'></script--> 
</head>
<body>
<div id='page' class='page'>
<div id='header' class='header'>
<h1><? echo SITE_NAME ?></h1>
</div>
<div id='topmenu' class='topmenu'>
<? TOPMENU() ?>
</div>
<div id='login' class='login'>
<form method='post' action='?logout=0'> Password: <input type='password' name='password' size='10' value=''> <input type='submit' value='Go'> </form>
<? if ($_GET["logout"]=="0") echo "<span class='error'>Error:</span> You have entered the wrong password."; ?>
</div>
</div>
</body>
</html>
<?
}


function CREATE_PASSWORD(&$con)
{
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
<meta name="author" content="<? echo AUTHOR ?>" />
<meta name="description" content="<? echo DESCRIPTION ?>" />
<meta name="keywords" content="<? echo SITE_NAME.", ".KEYWORDS ?>" />
<title><? echo SITE_NAME ?></title>
<link rel="stylesheet" href="<? echo MAIN_CSS_FILE ?>" />
<!--script src='http://...js'></script--> 
</head>
<body>
<div id='page' class='page'>
<div id='header' class='header'>
<h1><? echo SITE_NAME ?></h1>
</div>
<div id='topmenu' class='topmenu'>
<? TOPMENU() ?>
</div>
<div id='login' class='login'><?

if (($_POST["password1"] == $_POST["password2"]) and ($_POST["password1"] != ""))
{
  echo "Creating password...<br />";
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue)
        VALUES ('password','Password to login to site.','".crypt(md5($_POST["password1"]),md5(SALT))."')";
  if ( !mysql_query($sql,$con) ) { echo "Password was not stored. <br /> <span class='error'>*Error</span>:: " . mysql_error(); }
   else { echo "Password created please refresh page."; }
} else { ?>
  <form method='post'> 
  It appears that a password has not been set for this site.<br />
  <br />
  You need to create a password: <br />
  Password: <input type='password' name='password1' size='10' value=''> <br />
  Verify: <input type='password' name='password2' size='10' value=''> <br />
  <input type='submit' value='Go'> </form><?
}
// The passwords the user entered do not match
if ($_POST["password1"] != $_POST["password2"]) { echo "<span class='error'>Error</span>: Passwords do not match"; } ?>
</div>
</div>
</body>
</html>
<?
}


function VERIFY_USER(&$con)
{ 
  // If userID and timezone were passed, then save them in cookies
  if ($_GET["userID"] != "") {
    setcookie("userID", $_GET["userID"], time()+60*60*24*7);
    setcookie("timezone", $_GET["timezone"], time()+60*60*24*7);;
  }
    
  // Check to see if all DB tables exist, if not, then create them
  if (!mysql_query("SELECT * FROM Options",&$con) or
      !mysql_query("SELECT * FROM Schedule",&$con) or
      !mysql_query("SELECT * FROM Users",&$con) or
      !mysql_query("SELECT * FROM Count",&$con)
      )
    {
    echo "This is the first time you have viewed this page, or the database isn't setup correctly. <br /><br />";
    echo "This page will try to create the tables in the database: <br />";
    echo BUILD_TABLE_USERS($con);
    echo BUILD_TABLE_COUNT($con);
    echo BUILD_TABLE_SCHEDULE($con);
    echo BUILD_TABLE_OPTIONS($con);
    echo "Ignore errors below this line, and click on home. <br /><br />";
    echo "Note: If you have not set up the vars.php file for your database, or you haven't created a database, then creating the tables failed.<br />";
    echo "You will need to have the vars.php updates and database created before this page will correctly build the tables.<br /><br />";
    }

  // Get the already crypted password from the DB
  $check = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='password';",&$con));
  // Crypt the user entered password
  $password = crypt(md5($_POST['password']),md5(SALT));
  // Check if either the crypted password or cookie are the same as the password in the DB, also make sure that the DB password isn't blank
  if ((($password == $check['OptionValue']) or ($_COOKIE["password"] == $check['OptionValue'])) and ($check['OptionValue'] != "") and ($_GET["logout"] != "1")){
    setcookie("password", $check['OptionValue'], time()+60*60*24*7);
    return 1;
  } else { return 0; }
mysql_close(&$con);
}


function VERIFY_FAILED(&$con)
{
  // Verification failed, so delete user cookies
  setcookie("timezone", "", time()-3600);
  setcookie("password", "", time()-3600);
  setcookie("userID", "", time()-3600);
  // Verify failed, so check that the DB password isn't blank.  If DB password is blank, then prompt user to create site password, else ask user to login
  $check = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='password';",&$con));
  if ($check['OptionValue'] == "") { CREATE_PASSWORD($con); } 
  else { USER_LOGIN(); }
}

?>