<?php

function MANAGESITE(&$con)
{
  UPDATE_DB_OPTIONS($con);
  TABLE_OPTIONS($con);
}


// The below function is called after the Options table is created.
function CREATE_DEFAULT_OPTIONS(&$con)
{
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue)
        VALUES ('sitename','Name of this site:','_skillset_ Case Count Page');";
  RUN_QUERY($sql,"'sitename' Default options were not set",$con);
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue)
        VALUES ('queuerules','Queue rules:','Follow the rules');";
  RUN_QUERY($sql,"'queuerules' Default options were not set",$con);
  $sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue)
        VALUES ('queuenotes','Queue notes:','Some queue notes');";
  RUN_QUERY($sql,"'queuenotes' Default options were not set",$con);
}


function UPDATE_DB_OPTIONS(&$con)
{
  
  // Update the 'sitename' if it was changed
  $sitename = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='sitename';",&$con));
  if (( $_POST['sitename'] != $sitename['OptionValue'] ) and ( $_POST['options_datasent'] != '' ))
    {
    $sql="UPDATE Options SET OptionValue = '".$_POST['sitename']."' WHERE OptionName = 'sitename'";
    RUN_QUERY($sql,"Site name was not updated",$con);
    }
    
  // Update the 'queuerules' if it was changed
  $queuerules = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuerules';",&$con));
  if (( $_POST['queuerules'] != $queuerules['OptionValue'] ) and ( $_POST['options_datasent'] != '' ))
    {
    $sql="UPDATE Options SET OptionValue = '".$_POST['queuerules']."' WHERE OptionName = 'queuerules'";
    RUN_QUERY($sql,"Queue rules were not updated",$con);
    }
    
  // Update the 'queuenotes' if it was changed
  $queuenotes = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuenotes';",&$con));
  if (( $_POST['queuenotes'] != $queuenotes['OptionValue'] ) and ( $_POST['options_datasent'] != '' ))
    {
    $sql="UPDATE Options SET OptionValue = '".$_POST['queuenotes']."' WHERE OptionName = 'queuenotes'";
    RUN_QUERY($sql,"Queue notes were not updated",$con);
    }

  // Get the already crypted password from the DB
  $check = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='password';",&$con));

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
      $sql="UPDATE Options SET OptionValue = '".crypt(md5($_POST["password1"]),md5(SALT))."' WHERE OptionName = 'password'";
      if ( RUN_QUERY($sql,"Password was not updated",$con) )
        echo "Password was updated, you need to refresh the page and login.\n";
      }
    }
}


function TABLE_OPTIONS(&$con)
{
  echo "    <form method='post' name='options'>\n";
  echo "    <table>\n";
  
  echo "    <tr>\n";
  echo "      <th>Option</th>\n";
  echo "      <th>Value</th>\n";
  echo "    </tr>\n";

  $sitename = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='sitename';",&$con));
  echo "    <tr>\n";
  echo "      <td>".$sitename['OptionDesc']."</td>\n";  
  echo "      <td><input type='text' name='sitename' OnChange='options.submit();' value='".$sitename['OptionValue']."' width='80' /></td>\n";  
  echo "    </tr>\n";
  
  $queuerules = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuerules';",&$con));
  echo "    <tr>\n";
  echo "      <td>".$queuerules['OptionDesc']."</td>\n";  
  //echo "      <td><input type='text' name='queuerules' OnChange='options.submit();' value='".$queuerules['OptionValue']."' /></td>\n";  
  echo "      <td><textarea cols='80' rows='15' name='queuerules' OnChange='options.submit();'>".$queuerules['OptionValue']."</textarea></td>\n";  
  echo "    </tr>\n";
  
  $queuenotes = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuenotes';",&$con));
  echo "    <tr>\n";
  echo "      <td>".$queuenotes['OptionDesc']."</td>\n";  
  //echo "      <td><input type='text' name='queuenotes' OnChange='options.submit();' value='".$queuenotes['OptionValue']."' /></td>\n";  
  echo "      <td><textarea cols='80' rows='8' name='queuenotes' OnChange='options.submit();'>".$queuenotes['OptionValue']."</textarea></td>\n";  
  echo "    </tr>\n";
  
  echo "    </table>\n";
  
  echo "    <input type='hidden' id='options_datasent' name='options_datasent' value='1'>\n";
  echo "    <input type='submit' id='options_submit' value='Update'>\n";
  echo "    </form>\n";
  echo "    <script type='text/javascript'>\n";
  echo "      <!--\n";
  echo "      document.getElementById('options_submit').style.display='none'; // hides button if JS is enabled-->\n";
  echo "    </script>\n\n";
  
  echo "    <form method='post'>\n";
  echo "    <table>\n";
  
  echo "    <tr>\n";
  echo "      <th>Password</th>\n";
  echo "      <th>Value</th>\n";
  echo "    </tr>\n";

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