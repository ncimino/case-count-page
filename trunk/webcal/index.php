<?php
include_once("../include/includes.php");
include_once("../include/webcal_funcs.php");
DB_CONNECT($con);
SET_COOKIES($selected_page,$showdetails,$timezone,$userID,$con);

if ( VERIFY_USER($con) )
{
	?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta http-equiv='refresh' content='300; URL=./index.php' />
<meta name="author" content="<? echo AUTHOR; ?>" />
<meta name="description" content="<? echo DESCRIPTION; ?>" />
<meta name="keywords" content="<? echo KEYWORDS; ?>" />
<title>WebCal Links</title>
<link rel="icon" href="images/bomb.png" sizes="64x64" />
<link type="text/css" rel="stylesheet" href="<? echo MAIN_CSS_FILE; ?>" />
<script type="text/javascript" src="<? echo MAIN_JS_FILE; ?>"></script>
</head>
<body>
<div id="page" class="page">

<div id="header" class="header">

<div id="selectsite" class="selectsite">
<?
SELECTSITE($selected_page,$con);
?>
</div>

<div id="title" class="title">
<h1>WebCal Links</h1>
</div>

<div id="selectuser" class="selectuser">
<? 
SELECTUSER($timezone,$userID,$con); 
?>
</div>

</div>

<div id="topmenu" class="topmenu"><? TOPMENU('../') ?></div>

<?
//UPDATE_ALL_ICS('..',$selected_page,$con);

	if ($handle = opendir('./')) 
	{
		$domain = preg_replace('/http/', '', MAIN_DOMAIN);

	    while (false !== ($file = readdir($handle))) 
	    {
	    	if ( preg_match('/^.*\.ics$/i', $file ) ) 
	    	{ 
	        	echo "<a href='webcal".$domain."webcal/".$file."'>$file</a><br />\n";
	    	}
    	}
    
	}

    closedir($handle);
    
    echo "<a href='webcal".$domain."webcal/shared_calendar.php?calendar_page=2'>Phone Shared Calendar</a><br />\n";
    echo "<a href='webcal".$domain."webcal/shared_calendar.php?calendar_page=3'>Skillset Shared Calendar</a><br />\n";
    
?>

</div>
</body>
</html>
<?
}
else
{
	VERIFY_FAILED($selected_page,$con);
}
mysql_close($con);
?>