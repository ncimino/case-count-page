<?php

function MANAGESITE($selected_page,&$con)
{
  //UPDATE_DB_OPTIONS($selected_page,$con);
  TABLE_OPTIONS($selected_page,$con);
}

function UPDATE_DB_OPTIONS($selected_page,&$con)
{
  if ( $_POST['makesiteinactive'] != '' )
  {
    $sql="UPDATE Sites SET Active = 0 WHERE siteID='".$_POST['makesiteinactive']."'";
    RUN_QUERY($sql,"Changing 'skillset' to inactive failed",$con);
  }
  
  if ( $_POST['makesiteactive'] != '' )
  {
    $sql="UPDATE Sites SET Active = 1 WHERE siteID='".$_POST['makesiteactive']."'";
    RUN_QUERY($sql,"Changing 'skillset' to active failed",$con);
  }
  
  if ( $_POST['createsite'] != '' )
  {
    $sql="INSERT INTO Sites (SiteName, Active)
          VALUES ('skillset',1);";
    RUN_QUERY($sql,"Adding 'skillset' site failed",$con);
    $siteID = mysql_insert_id($con);
    CREATE_DEFAULT_SKILLSET($siteID,$con);
  }

  if ( $_POST['skillset_datasent'] != '' )
  {
    $sql="UPDATE Options SET OptionValue = '".$_POST['sitename']."' WHERE OptionName = 'sitename' AND siteID='".$selected_page."'";
    RUN_QUERY($sql,"Site name was not updated",$con);
    $sql="UPDATE Options SET OptionValue = '".$_POST['queuecc']."' WHERE OptionName = 'queuecc' AND siteID='".$selected_page."'";
    RUN_QUERY($sql,"Queue CC was not updated",$con);
    $sql="UPDATE Options SET OptionValue = '".$_POST['queuerules']."' WHERE OptionName = 'queuerules' AND siteID='".$selected_page."'";
    RUN_QUERY($sql,"Queue rules were not updated",$con);
    $sql="UPDATE Options SET OptionValue = '".$_POST['queuenotes']."' WHERE OptionName = 'queuenotes' AND siteID='".$selected_page."'";
    RUN_QUERY($sql,"Queue notes were not updated",$con);
    $sql="UPDATE Options SET OptionValue = '".$_POST['queuemax']."' WHERE OptionName = 'queuemax' AND siteID='".$selected_page."'";
    RUN_QUERY($sql,"Queue Max was not updated",$con);
    $sql="UPDATE Options SET OptionValue = '".$_POST['replyto']."' WHERE OptionName = 'replyto' AND siteID='".$selected_page."'";
    RUN_QUERY($sql,"Queue Reply-To was not updated",$con);
  }

  if ( $_POST['phoneshift_datasent'] != '' )
  {
    $sql="UPDATE Options SET OptionValue = '".$_POST['sitename']."' WHERE OptionName = 'sitename' AND siteID='".$selected_page."'";
    RUN_QUERY($sql,"Site name was not updated",$con);
    $sql="UPDATE Options SET OptionValue = '".$_POST['phonescc']."' WHERE OptionName = 'phonescc' AND siteID='".$selected_page."'";
    RUN_QUERY($sql,"Phones CC was not updated",$con);
    $sql="UPDATE Options SET OptionValue = '".$_POST['phonenotes']."' WHERE OptionName = 'phonenotes' AND siteID='".$selected_page."'";
    RUN_QUERY($sql,"Phone notes were not updated",$con);
    $sql="UPDATE Options SET OptionValue = '".$_POST['replyto']."' WHERE OptionName = 'replyto' AND siteID='".$selected_page."'";
    RUN_QUERY($sql,"Phone Reply-To was not updated",$con);
  }

  if ( $_POST['general_datasent'] != '' )
  {
    $sql="UPDATE Options SET OptionValue = '".$_POST['sitename']."' WHERE OptionName = 'sitename' AND siteID='".$selected_page."'";
    RUN_QUERY($sql,"Site name was not updated",$con);
    $sql="UPDATE Options SET OptionValue = '".$_POST['mainnotes']."' WHERE OptionName = 'mainnotes' AND siteID='".$selected_page."'";
    RUN_QUERY($sql,"Main notes were not updated",$con);
  }

  // Get the already crypted password from the DB
  $check = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='password';",$con));

  // Crypt the user entered password
  $oldpassword = crypt(md5($_POST['oldpassword']),md5(SALT));

  if ($_POST["password1"] != "")
  {
    if ($_POST["password1"] != $_POST["password2"])
    {
      echo "The new passwords do not match.<br />";
    }
    elseif ($oldpassword != $check['OptionValue'])
    {
      echo "The old password you entered is incorrect.<br />";
    }
    else
    {
      echo "Changing password...<br />";
      $sql="UPDATE Options SET OptionValue = '".crypt(md5($_POST["password1"]),md5(SALT))."' WHERE OptionName = 'password' AND siteID='".$selected_page."'";
      if ( RUN_QUERY($sql,"Password was not updated",$con) )
      echo "Password was updated, you need to refresh the page and login.\n";
    }
  }
}

function TABLE_OPTIONS($selected_page,&$con)
{
  echo "    <h2>";
  echo SITE_NAME($selected_page,$con);
  echo " Options:</h2>\n";
  $main_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites WHERE SiteName='main';",$con));
  $phone_page = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites WHERE SiteName='phoneshift';",$con));
  $skillset_pages = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites WHERE SiteName='skillset';",$con));

  if ($selected_page == $main_page['siteID'])
  {
    GENERAL_OPTIONS_TABLE($selected_page,$con);
    CREATE_SITES_TABLE($con);
    CHANGE_PASSWORD_TABLE();
  }
  elseif ($selected_page == $phone_page['siteID'])
  {
    PHONESHIFT_OPTIONS_TABLE($selected_page,$con);
  }
  else
  {
    SKILLSET_OPTIONS_TABLE($selected_page,$con);
  }
}

function CREATE_SITES_TABLE(&$con)
{
  echo "    <h3>Site Management:</h3>\n";

  echo "    <h5>Active Sites:</h5>\n";
  $pages_query = mysql_query("SELECT Options.siteID,OptionValue FROM Options,Sites WHERE OptionName='sitename' AND Options.siteID=Sites.siteID AND Active='1' AND SiteName<>'main';",$con);
  if (mysql_num_rows($pages_query) != 0)
  { 
    echo "    <table>\n";
   	while ($pages = mysql_fetch_array($pages_query))
    {
      echo "    <tr>\n";
      echo " 	   <td>&nbsp;&nbsp;&nbsp;".$pages['OptionValue']."</td>\n";
      echo "      <td>\n";
      echo "        <form method='post' action=''>\n";
      echo "          <input type='hidden' name='makesiteinactive' value='".$pages['siteID']."' />\n";
      echo "          <input type='submit' value='Move to Inactive' />\n";
      echo "        </form>\n";
      echo " 		</td>\n";
      echo "    </tr>\n"; 
    }
    echo "    </table>\n";
  }
  else
  {
    echo "		&nbsp;&nbsp;&nbsp;No Active Sites found.";
  }
  
  echo "    <h5>Inactive Sites:</h5>\n";
  $pages_query = mysql_query("SELECT Options.siteID,OptionValue FROM Options,Sites WHERE OptionName='sitename' AND Options.siteID=Sites.siteID AND Active='0';",$con);
  if (mysql_num_rows($pages_query) != 0)
  { 
    echo "    <table>\n";
    while ($pages = mysql_fetch_array($pages_query))
    {
      echo "    <tr>\n";
      echo " 	   <td>&nbsp;&nbsp;&nbsp;".$pages['OptionValue']."</td>\n";
      echo "      <td>\n";
      echo "        <form method='post' action=''>\n";
      echo "          <input type='hidden' name='makesiteactive' value='".$pages['siteID']."' />\n";
      echo "          <input type='submit' value='Move to Active' />\n";
      echo "        </form>\n";
      echo " 		</td>\n";
      echo "    </tr>\n"; 
    }
    echo "    </table>\n";
  }
  else
  {
    echo "		&nbsp;&nbsp;&nbsp;No Inactive Sites found.";
  }
  
  echo "    <h5>Create A New Skillset:</h5>\n";
  echo "        <form method='post' action=''>\n";
  echo "          &nbsp;&nbsp;&nbsp;\n";
  echo "          <input type='hidden' name='createsite' value='1' />\n";
  echo "          <input type='submit' value='Create a New Skillset' onclick='return confirmSubmit(\"Are you sure you want to create a new site? NOTE: Once a site is created it cannot be deleted, but it can be made inactive.\")' />\n";
  echo "        </form>\n";
  
}

function GENERAL_OPTIONS_TABLE($selected_page,&$con)
{
  echo "    <h3>Main Site:</h3>\n";
  
  echo "    <form method='post'>\n";
  echo "    <table>\n";

  $sitename = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='sitename' AND siteID='".$selected_page."';",$con));
  echo "    <tr>\n";
  echo "      <td>".$sitename['OptionDesc']."</td>\n";
  echo "      <td><input type='text' name='sitename' value='".$sitename['OptionValue']."' size='80' /></td>\n";
  echo "    </tr>\n";

  $mainnotes = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='mainnotes' AND siteID='".$selected_page."';",$con));
  echo "    <tr>\n";
  echo "      <td>".$mainnotes['OptionDesc']."</td>\n";
  echo "      <td><textarea cols='80' rows='15' name='mainnotes'>".$mainnotes['OptionValue']."</textarea></td>\n";
  echo "    </tr>\n";

  echo "    </table>\n";

  echo "    <input type='hidden' id='phoneshift_datasent' name='general_datasent' value='1'>\n";
  echo "    <input type='submit' id='options_submit' value='Update'>\n";
  echo "    </form>\n";
}

function PHONESHIFT_OPTIONS_TABLE($selected_page,&$con)
{
  echo "    <form method='post'>\n";
  echo "    <table>\n";

  $sitename = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='sitename' AND siteID='".$selected_page."';",$con));
  echo "    <tr>\n";
  echo "      <td>".$sitename['OptionDesc']."</td>\n";
  echo "      <td><input type='text' name='sitename' value='".$sitename['OptionValue']."' size='80' /></td>\n";
  echo "    </tr>\n";

  $phonescc = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='phonescc' AND siteID='".$selected_page."';",$con));
  echo "    <tr>\n";
  echo "      <td>".$phonescc['OptionDesc']."</td>\n";
  echo "      <td><input type='text' name='phonescc' value='".$phonescc['OptionValue']."' size='80' /></td>\n";
  echo "    </tr>\n";

  $replyto = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='replyto' AND siteID='".$selected_page."';",$con));
  echo "    <tr>\n";
  echo "      <td>".$replyto['OptionDesc']."</td>\n";
  echo "      <td><input type='text' name='replyto' value='".$replyto['OptionValue']."' size='80' /></td>\n";
  echo "    </tr>\n";

  echo "    <tr>\n";
  echo "      <td colspan='2'>&nbsp;&nbsp;Enter the email addresses as: tom@domain.com, jane@domain.com</td>\n";
  echo "    </tr>\n";

  $phonenotes = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='phonenotes' AND siteID='".$selected_page."';",$con));
  echo "    <tr>\n";
  echo "      <td>".$phonenotes['OptionDesc']."</td>\n";
  echo "      <td><textarea cols='80' rows='15' name='phonenotes'>".$phonenotes['OptionValue']."</textarea></td>\n";
  echo "    </tr>\n";

  echo "    </table>\n";

  echo "    <input type='hidden' id='phoneshift_datasent' name='phoneshift_datasent' value='1'>\n";
  echo "    <input type='submit' id='options_submit' value='Update'>\n";
  echo "    </form>\n";
}

function SKILLSET_OPTIONS_TABLE($selected_page,&$con)
{
  echo "    <form method='post'>\n";
  echo "    <table>\n";

  $sitename = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='sitename' AND siteID='".$selected_page."';",$con));
  echo "    <tr>\n";
  echo "      <td>".$sitename['OptionDesc']."</td>\n";
  echo "      <td><input type='text' name='sitename' value='".$sitename['OptionValue']."' size='80' /></td>\n";
  echo "    </tr>\n";

  $queuecc = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuecc' AND siteID='".$selected_page."';",$con));
  echo "    <tr>\n";
  echo "      <td>".$queuecc['OptionDesc']."</td>\n";
  echo "      <td><input type='text' name='queuecc' value='".$queuecc['OptionValue']."' size='80' /></td>\n";
  echo "    </tr>\n";

  $replyto = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='replyto' AND siteID='".$selected_page."';",$con));
  echo "    <tr>\n";
  echo "      <td>".$replyto['OptionDesc']."</td>\n";
  echo "      <td><input type='text' name='replyto' value='".$replyto['OptionValue']."' size='80' /></td>\n";
  echo "    </tr>\n";

  echo "    <tr>\n";
  echo "      <td colspan='2'>&nbsp;&nbsp;Enter the email addresses as: tom@domain.com, jane@domain.com</td>\n";
  echo "    </tr>\n";

  $queuerules = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuerules' AND siteID='".$selected_page."';",$con));
  echo "    <tr>\n";
  echo "      <td>".$queuerules['OptionDesc']."</td>\n";
  echo "      <td><textarea cols='80' rows='15' name='queuerules'>".$queuerules['OptionValue']."</textarea></td>\n";
  echo "    </tr>\n";

  $queuenotes = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuenotes' AND siteID='".$selected_page."';",$con));
  echo "    <tr>\n";
  echo "      <td>".$queuenotes['OptionDesc']."</td>\n";
  echo "      <td><textarea cols='80' rows='8' name='queuenotes'>".$queuenotes['OptionValue']."</textarea></td>\n";
  echo "    </tr>\n";

  $queuemax = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuemax' AND siteID='".$selected_page."';",$con));
  echo "    <tr>\n";
  echo "      <td>".$queuemax['OptionDesc']."</td>\n";
  echo "      <td>\n";
  echo "          <select name='queuemax'>\n";

  for ($i = 1; $i < 15; $i++)
  {
    echo "          <option value='$i'";
    if ($i == $queuemax['OptionValue'])
    echo " selected='selected'";
    echo ">$i</option>\n";
  }

  echo "          </select>\n";
  echo "      </td>\n";
  echo "    </tr>\n";

  echo "    </table>\n";

  echo "    <input type='hidden' id='skillset_datasent' name='skillset_datasent' value='1'>\n";
  echo "    <input type='submit' id='options_submit' value='Update'>\n";
  echo "    </form>\n";
}

function CHANGE_PASSWORD_TABLE()
{
  echo "    <h3>Site Password:</h3>\n";
  echo "    <form method='post'>\n";
  echo "    <table>\n";

  echo "    <tr>\n";
  echo "      <td>Old Password</td>\n";
  echo "      <td><input type='password' name='oldpassword' value='' /></td>\n";
  echo "    </tr>\n";

  echo "    <tr>\n";
  echo "      <td>New Password</td>\n";
  echo "      <td><input type='password' name='password1' value='' /></td>\n";
  echo "    </tr>\n";

  echo "    <tr>\n";
  echo "      <td>Verify Password</td>\n";
  echo "      <td><input type='password' name='password2' value='' /></td>\n";
  echo "    </tr>\n";

  echo "    </table>\n";

  echo "    <input type='submit' id='password_submit' value='Change Password'>\n";
  echo "    </form>\n";
}

?>