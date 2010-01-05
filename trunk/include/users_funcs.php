<?php

function USERS(&$con)
{
	//UPDATE_DB_USERS($con);
	TABLE_USERS($con);
}

function UPDATE_DB_USERS(&$con)
{

	if ( $_POST['edituser'] != "" and $_POST['newusername'] != "") // Only update changes to user info if the user name is not blank
	{
		$sql="UPDATE Users SET UserName = '".$_POST['newusername']."', UserEmail = '".$_POST['newuseremail']."' WHERE userID = '".$_POST['edituser']."'";
		RUN_QUERY($sql,"User was not updated.",$con);
		$sites_query = mysql_query("SELECT siteID FROM Sites WHERE SiteName<>'main';",$con);
		while ($sites = mysql_fetch_array($sites_query))
		{
			if ( $_POST['site_'.$sites['siteID']] == 'on')
			{
				$relationship_exists = 0;
				$usersite_query = mysql_query("SELECT siteID FROM UserSites WHERE userID='".$_POST['edituser']."';",$con);
				while ($usersite = mysql_fetch_array($usersite_query))
				{
					if ( $usersite['siteID'] == $sites['siteID'] )
					{
						$relationship_exists = 1;
					}
				}
				if (!$relationship_exists)
				{
					$sql="INSERT INTO UserSites (userID, siteID) VALUES ('".$_POST['edituser']."','".$sites['siteID']."')";
					RUN_QUERY($sql,"User relationship to site was not added.",$con);
				}
			}
			if ( $_POST['site_'.$sites['siteID']] == '')
			{
				$relationship_exists = 0;
				$usersite_query = mysql_query("SELECT siteID FROM UserSites WHERE userID='".$_POST['edituser']."';",$con);
				while ($usersite = mysql_fetch_array($usersite_query))
				{
					if ( $usersite['siteID'] == $sites['siteID'] )
					{
						$relationship_exists = 1;
					}
				}
				if ($relationship_exists)
				{
					$sql="DELETE FROM UserSites WHERE userID='".$_POST['edituser']."' AND siteID='".$sites['siteID']."'";
					RUN_QUERY($sql,"User relationship to site was not removed.",$con);
				}
			}
		}
	}

	if ( $_POST['permdeleteuser'] != "" )
	{
		$sql="DELETE FROM UserSites WHERE userID='".$_POST['permdeleteuser']."'";
		RUN_QUERY($sql,"User was not deleted table UserSites.",$con);
		$sql="DELETE FROM Users WHERE userID='".$_POST['permdeleteuser']."'";
		RUN_QUERY($sql,"User was not deleted table Users.",$con);
		$sql="DELETE FROM PhoneSchedule WHERE userID='".$_POST['permdeleteuser']."'";
		RUN_QUERY($sql,"User was not deleted table PhoneSchedule.",$con);
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

	if ( $_POST['createusername'] != "" ) // Only add a user if their name is not blank
	{
		$sql="INSERT INTO Users (UserName, UserEmail, Active) VALUES ('".$_POST['createusername']."','".$_POST['createuseremail']."',1)";
		RUN_QUERY($sql,"User was not created.",$con);
		$new_userID = mysql_insert_id($con);
		$sites_query = mysql_query("SELECT siteID FROM Sites WHERE SiteName<>'main';",$con);
		while ($sites = mysql_fetch_array($sites_query))
		{
			if ( $_POST['create_site_'.$sites['siteID']] == 'on')
			{
				$sql="INSERT INTO UserSites (userID, siteID) VALUES ('".$new_userID."','".$sites['siteID']."')";
				RUN_QUERY($sql,"User relationship to site was not added.",$con);
			}
		}
	}
}

function TABLE_USERS(&$con)
{
	$activeusers = mysql_query("SELECT * FROM Users WHERE Active=1;",$con);
	$nonactiveusers = mysql_query("SELECT * FROM Users WHERE Active=0;",$con);

	if ( $_POST['edituser'] != "" and $_POST['newusername'] == "" and $_POST['newuseremail'] == "") // If we went to edit user but nothing was entered then display change form
	{
		$username = mysql_fetch_array(mysql_query("SELECT UserName,UserEmail FROM Users WHERE userID=".$_POST['edituser'].";",$con));
		echo "<h2> Change users info: </h2>\n";
		echo "<form method='post'>\n";
		echo "  <input type='hidden' name='edituser' value='".$_POST['edituser']."' />\n";
		echo "  User Name:\n";
		echo "  <input type='text' name='newusername' size='10' value='".$username['UserName']."' /><br />\n";
		echo "  User Email:\n";
		echo "  <input type='text' name='newuseremail' size='20' value='".$username['UserEmail']."' /><br />\n";

		$skillset_pages_query = mysql_query("SELECT siteID FROM Sites WHERE SiteName<>'main' AND Active='1';",$con);
		while ($skillset_pages = mysql_fetch_array($skillset_pages_query))
		{
			$sitename = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='sitename' AND siteID='".$skillset_pages['siteID']."';",$con));
			$usersite_query = mysql_query("SELECT usersiteID FROM UserSites WHERE userID='".$_POST['edituser']."' AND siteID='".$skillset_pages['siteID']."'",$con);
			if ( mysql_num_rows($usersite_query) == 1)
			{
				$user_belongs_to_site = " checked='checked'";
			}
			else
			{
				$user_belongs_to_site = '';
			}
			echo "  ".$sitename['OptionValue'].": <input type='checkbox' name='site_".$skillset_pages['siteID']."'".$user_belongs_to_site." /><br />\n";
		}

		echo "  <input type='submit' value='Change' />\n";
		echo "</form>\n";
		echo "<form action='users.php'>\n";
		echo "  <input type='submit' value='Cancel' />\n";
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
		echo "      <tr>\n";
		echo "        <th class='activeusers_cell'>User Name</th>\n";
		echo "        <th class='activeusers_cell'>User Email</th>\n";
		
		$skillset_pages_query = mysql_query("SELECT siteID FROM Sites WHERE SiteName<>'main' AND Active='1';",$con);
		while ($skillset_pages = mysql_fetch_array($skillset_pages_query))
		{
			$sitename = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='sitename' AND siteID='".$skillset_pages['siteID']."';",$con));
			echo "        <th class='activeusers_cell'>".$sitename['OptionValue']."</th>\n";
		}
		
		echo "        <th class='activeusers_cell'></th>\n";
		echo "        <th class='activeusers_cell'></th>\n";
		echo "      </tr>\n";
		while ( $currentuser = mysql_fetch_array($activeusers) )
		{
			echo "      <tr>\n";
			echo "        <td class='activeusers_cell'>".$currentuser['UserName']."</td>\n";
			echo "        <td class='activeusers_cell'>".$currentuser['UserEmail']."</td>\n";
			
			$skillset_pages_query = mysql_query("SELECT siteID FROM Sites WHERE SiteName<>'main' AND Active='1';",$con);
			while ($skillset_pages = mysql_fetch_array($skillset_pages_query))
			{
				$usersite_query = mysql_query("SELECT usersiteID FROM UserSites WHERE userID='".$currentuser['userID']."' AND siteID='".$skillset_pages['siteID']."'",$con);
				if ( mysql_num_rows($usersite_query) == 1)
				{
					$user_belongs_to_site = " checked='checked'";
				}
				else
				{
					$user_belongs_to_site = '';
				}
				echo "        <td class='activeusers_cell'><input type='checkbox' disabled='disabled' name='site_".$skillset_pages['siteID']."'".$user_belongs_to_site." /></td>\n";
			}
			
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
	echo "  User Name:\n";
	echo "      <input type='text' name='createusername' size='10' value='' /><br />\n";
	echo "  User Email:\n";
	echo "      <input type='text' name='createuseremail' size='20' value='' /><br />\n";
	$skillset_pages_query = mysql_query("SELECT siteID FROM Sites WHERE SiteName<>'main' AND Active='1';",$con);
	$phoneshift_siteID = mysql_fetch_array(mysql_query("SELECT siteID FROM Sites WHERE SiteName='phoneshift' AND Active='1';",$con));
	while ($skillset_pages = mysql_fetch_array($skillset_pages_query))
	{
		$sitename = mysql_fetch_array(mysql_query("SELECT OptionValue FROM Options WHERE OptionName='sitename' AND siteID='".$skillset_pages['siteID']."';",$con));
		$checked = "";
		if ($phoneshift_siteID['siteID'] == $skillset_pages['siteID'])
		{
			$checked = " checked='checked'";
		}
		echo "  ".$sitename['OptionValue'].": <input type='checkbox' name='create_site_".$skillset_pages['siteID']."'".$checked." /><br />\n";
	}
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
			echo "        <td class='inactiveusers_cell'>".$currentuser['UserEmail']."</td>\n";
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