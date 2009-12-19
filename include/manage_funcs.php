<?php

function MANAGESITE($option_page,&$con)
{
	UPDATE_DB_OPTIONS($con);
	TABLE_OPTIONS($option_page,$con);
}

// The below function is called after the Options table is created.
function CREATE_DEFAULT_OPTIONS(&$con)
{
	$sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue)
        VALUES ('sitename','Name of this site:','_skillset_ Case Count Page');";
	RUN_QUERY($sql,"'sitename' Default options were not set",$con);
	$sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue)
        VALUES ('queuecc','CC for MAX and Queue emails:','');";
	RUN_QUERY($sql,"'queuecc' Default options were not set",$con);
	$sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue)
        VALUES ('queuerules','Queue rules:','Follow the rules');";
	RUN_QUERY($sql,"'queuerules' Default options were not set",$con);
	$sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue)
        VALUES ('queuenotes','Queue notes:','Some queue notes');";
	RUN_QUERY($sql,"'queuenotes' Default options were not set",$con);
	$sql="INSERT INTO Options (OptionName, OptionDesc, OptionValue)
        VALUES ('queuemax','Queue max:','8');";
	RUN_QUERY($sql,"'queuemax' Default options were not set",$con);
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

	// Update the 'queuecc' if it was changed
	$queuecc = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuecc';",&$con));
	if (( $_POST['queuecc'] != $queuecc['OptionValue'] ) and ( $_POST['options_datasent'] != '' ))
	{
		$sql="UPDATE Options SET OptionValue = '".$_POST['queuecc']."' WHERE OptionName = 'queuecc'";
		RUN_QUERY($sql,"Queue CC was not updated",$con);
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

	// Update the 'queuemax' if it was changed
	$queuemax = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuemax';",&$con));
	if (( $_POST['queuemax'] != $queuemax['OptionValue'] ) and ( $_POST['options_datasent'] != '' ))
	{
		$sql="UPDATE Options SET OptionValue = '".$_POST['queuemax']."' WHERE OptionName = 'queuemax'";
		RUN_QUERY($sql,"Queue Max was not updated",$con);
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

function TABLE_OPTIONS($option_page,&$con)
{
	echo "    <h2>Options:</h2>\n";

	echo "    <form method='post' name='option_selection'>\n";
	echo "      <select name='option_page' OnChange='option_selection.submit();'>\n";
	echo "        <option value='0'";
	if ($option_page == 0)
	   echo " selected='selected'";
	echo ">Site Wide</optiom>\n";
	echo "        <option value='1'";
    if ($option_page == 1)
       echo " selected='selected'";
    echo ">Phone Shift</optiom>\n";
	echo "        <option value='2'";
    if ($option_page == 2)
       echo " selected='selected'";
    echo ">Software Case Count Page</optiom>\n";
	echo "        <option value='3'";
    if ($option_page == 3)
       echo " selected='selected'";
    echo ">Hardware Case Count Page</optiom>\n";
	echo "      </select>\n";
	echo "    <input type='submit' id='option_page_submit' value='Show'>\n";
	echo "    </form>\n";
	echo "    <script type='text/javascript'>\n";
	echo "      <!--\n";
	echo "      document.getElementById('option_page_submit').style.display='none'; // hides button if JS is enabled-->\n";
	echo "    </script>\n";

	echo "    <form method='post' name='options'>\n";
	echo "    <table>\n";

	SKILLSET_OPTIONS_TABLE($con);
	CHANGE_PASSWORD_TABLE();

}

function SKILLSET_OPTIONS_TABLE(&$con)
{
	
    $sitename = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='sitename';",&$con));
    echo "    <tr>\n";
    echo "      <td>".$sitename['OptionDesc']."</td>\n";
    echo "      <td><input type='text' name='sitename' value='".$sitename['OptionValue']."' size='80' /></td>\n";
    echo "    </tr>\n";

    $queuecc = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuecc';",&$con));
    echo "    <tr>\n";
    echo "      <td rowspan='2'>".$queuecc['OptionDesc']."</td>\n";
    echo "      <td><input type='text' name='queuecc' value='".$queuecc['OptionValue']."' size='80' /></td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "      <td>Enter the email addresses as: tom@domain.com, jane@domain.com,</td>\n";
    echo "    </tr>\n";

    $queuerules = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuerules';",&$con));
    echo "    <tr>\n";
    echo "      <td>".$queuerules['OptionDesc']."</td>\n";
    echo "      <td><textarea cols='80' rows='15' name='queuerules'>".$queuerules['OptionValue']."</textarea></td>\n";
    echo "    </tr>\n";

    $queuenotes = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuenotes';",&$con));
    echo "    <tr>\n";
    echo "      <td>".$queuenotes['OptionDesc']."</td>\n";
    echo "      <td><textarea cols='80' rows='8' name='queuenotes'>".$queuenotes['OptionValue']."</textarea></td>\n";
    echo "    </tr>\n";

    $queuemax = mysql_fetch_array(mysql_query("SELECT * FROM Options WHERE OptionName='queuemax';",&$con));
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

    echo "    <input type='hidden' id='options_datasent' name='options_datasent' value='1'>\n";
    echo "    <input type='submit' id='options_submit' value='Update'>\n";
    echo "    </form>\n";
}

function CHANGE_PASSWORD_TABLE()
{
    echo "    <h2>Passwords:</h2>\n";
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