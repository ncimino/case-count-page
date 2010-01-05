<?php

function SET_COOKIES(&$selected_page,&$showdetails,&$timezone,&$userID,&$con)
{
    // If a userID is passed, then set the cookie for 365 days
    if ($_POST["userID"] != "") setcookie("userID", $_POST["userID"], time()+60*60*24*365);

    // If a TimeZone is passed, then set the cookie for 365 days
    if ($_POST["timezone"] != "") setcookie("timezone", $_POST["timezone"], time()+60*60*24*365);
    if ($_GET["timezone"] != "") setcookie("timezone", $_GET["timezone"], time()+60*60*24*365);
    
    // $_POST["showdetails"] is a checkbox - checkboxes only send data if checked
    // $_POST["showdetailssent"] is a hidden type submitted with showdetails to differentiate between a submit and a random page load
    // If a showdetails is passed, then set the cookie for 365 days
    if ($_POST["showdetailssent"] != "") setcookie("showdetails", $_POST["showdetails"], time()+60*60*24*365);
    
    // If option_page is passed, then set the cookie for 365 days
    if ($_POST["option_page"] != "") setcookie("option_page", $_POST["option_page"], time()+60*60*24*365);
    
    // If timezone cookie isn't set, then set the cookie to -7 (MST) for 365 days
    if ($_COOKIE["timezone"] == "") setcookie("timezone", "-7", time()+60*60*24*365);

    if ($_POST['userID'] == '') // userID was not passed
    {
        if ($_COOKIE['userID'] == '') // userID cookie is not set
        {
            $userID = '';
        }
        else // userID cookie is set
        {
            $userID = $_COOKIE['userID'];
            // Check to see if the user set in the cookie is an active user
            $useractive = mysql_fetch_array(mysql_query("SELECT Active FROM Users WHERE userID=".$userID.";",$con));
            if ($useractive['Active'] == 0)
            $userID = '';
        }
    }
    else // userID was passed
    {
        if ($_POST['userID'] == 'NULL') // userID is NULL, this means the user selected '----' Need to remove cookie
        {
            $userID = '';
            setcookie("userID", "", time()-3600);
        }
        else
        {
            $userID = $_POST['userID'];
        }
    }

    // If timezone was passed then we need to use that, else check for a cookie - the order of these statements is important - cookies take a refresh to update
    ($_POST['timezone'] == '') ?  $settimezone = $_GET['timezone'] : $settimezone = $_POST['timezone'];
    ($settimezone == '') ? (($_COOKIE['timezone'] == '') ? $timezone = '' : $timezone = $_COOKIE['timezone']) : $timezone = $settimezone;

    // If showdetails was passed then we need to use that, else check for a cookie - the order of these statements is important - cookies take a refresh to update
    ($_POST["showdetailssent"] == '') ? (($_COOKIE['showdetails'] == '') ? $showdetails = '' : $showdetails = $_COOKIE['showdetails']) : $showdetails = $_POST['showdetails'];

    // If option_page was passed then we need to use that, else check for a cookie - the order of these statements is important - cookies take a refresh to update
    ($_POST["option_page"] == '') ? (($_COOKIE['option_page'] == '') ? $selected_page = '' : $selected_page = $_COOKIE['option_page']) : $selected_page = $_POST['option_page'];
}

function USER_LOGIN(&$con)
{
  $mainsite = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites WHERE SiteName='main';",$con));
  echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
  echo "<html xmlns='http://www.w3.org/1999/xhtml'>\n";
  ?>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
<meta name="author" content="<? echo AUTHOR ?>" />
<meta name="description" content="<? echo DESCRIPTION ?>" />
<meta name="keywords" content="<? echo KEYWORDS ?>" />
<title><? SITE_NAME($mainsite['siteID'],$con) ?></title>
<link rel="stylesheet" href="<? echo MAIN_CSS_FILE ?>" />
<script src="<? echo MAIN_JS_FILE ?>"></script>
</head>
<body>
<div id='page' class='page'>
<div id='header' class='header'>
<h1><? SITE_NAME($mainsite['siteID'],$con) ?></h1>
</div>
<div id='topmenu' class='topmenu'><? TOPMENU() ?></div>
<div id='login' class='login'>
<form method='post' action='?logout=0&timezone=-7'>Password: <input type='password'
    name='password' size='10' value='' /> <input type='submit' value='Go' /></form>
    <? if ($_GET["logout"]=="0") echo "<span class='error'>Error:</span> You have entered the wrong password.\n"; ?>
</div>
</div>
</body>
  <?
  echo "</html>\n";
}

function CREATE_PASSWORD(&$con)
{
  echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>\n";
  echo "<html xmlns='http://www.w3.org/1999/xhtml'>\n";
  ?>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
<meta name="author" content="<? echo AUTHOR ?>" />
<meta name="description" content="<? echo DESCRIPTION ?>" />
<meta name="keywords" content="<? echo KEYWORDS ?>" />
<link rel="stylesheet" href="<? echo MAIN_CSS_FILE ?>" />
<script src="<? echo MAIN_JS_FILE ?>"></script>
</head>
<body>
<div id='page' class='page'>
<div id='login' class='login'><?

if (($_POST["password1"] == $_POST["password2"]) and ($_POST["password1"] != ""))
{
	$main_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites WHERE SiteName='main';",$con));
    echo "Creating password...<br />";
    $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue, siteID)
        VALUES ('password','Password to login to site','".crypt(md5($_POST["password1"]),md5(SALT))."','".$main_page['siteID']."')";
    if ( RUN_QUERY($sql,"Password was not stored",$con) )
    echo "Password created please <a href='index.php'>refresh</a> page.\n";
}
else
{ ?>
<form method='post'>It appears that a password has not been set for this
site.<br />
<br />
You need to create a password: <br />
Password: <input type='password' name='password1' size='10' value='' /> <br />
Verify: <input type='password' name='password2' size='10' value='' /> <br />
<input type='submit' value='Go' /></form>
<?
}
// The passwords the user entered do not match
if ($_POST["password1"] != $_POST["password2"]) { echo "<span class='error'>Error</span>: Passwords do not match\n"; } ?>
</div>
</div>
</body>
  <?
  echo "</html>\n";
}

function VERIFY_USER(&$con)
{
    // Check to see if all DB tables exist, if not, then create them
    BUILD_ALL_DB_TABLES($con);

    // Get the already crypted password from the DB
    $check_query = mysql_query("SELECT OptionValue FROM Options,Sites WHERE OptionName='password' AND Options.siteID=Sites.siteID AND SiteName='main';",$con);
    $check = mysql_fetch_array($check_query);

    // Crypt the user entered password
    $password = crypt(md5($_POST['password']),md5(SALT));

    // Check if either the crypted password or cookie are the same as the password in the DB, also make sure that the DB password isn't blank
    if ((($password == $check['OptionValue']) or ($_COOKIE["password"] == $check['OptionValue'])) and ($check['OptionValue'] != "") and ($_GET["logout"] != "1"))
    {
        setcookie("password", $check['OptionValue'], time()+60*60*24*7);
        return 1;
    }
    else
    return 0;
}

function VERIFY_FAILED($selected_page,&$con)
{
    // Verification failed, so delete user cookies
    setcookie("timezone", "", time()-3600);
    setcookie("password", "", time()-3600);
    setcookie("userID", "", time()-3600);
    // Verify failed, so check that the DB password isn't blank.  If DB password is blank, then prompt user to create site password, else ask user to login
    $check_query = mysql_query("SELECT OptionValue FROM Options,Sites WHERE OptionName='password' AND Options.siteID=Sites.siteID AND SiteName='main';",$con);
    $check = mysql_fetch_array($check_query);
    ($check['OptionValue'] == "") ? CREATE_PASSWORD($con) : USER_LOGIN($con);
}

?>