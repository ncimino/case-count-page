<?php

function CHECKCOUNTDATES($con)
{
  echo "Beginning CRC in table 'Count'...<br />\n";
  $crc_query = mysql_query("SELECT * FROM Count",$con);
  while ( $crc_row = mysql_fetch_array($crc_query) )
    {
    echo " countID: ".$crc_row['countID']." ";
    echo " Date: ".$crc_row['Date']." ";
    echo " Checking Time: ".gmdate('r',$crc_row['Date'])." "; 
    if (
        (gmdate('G',$crc_row['Date'])==0) and 
        (gmdate('i',$crc_row['Date'])==0) and 
        (gmdate('s',$crc_row['Date'])==0) and 
        (gmdate('w',$crc_row['Date'])!=0) and 
        (gmdate('w',$crc_row['Date'])!=6)
       )
      echo "- <span class='success'>Good</span><br />\n"; 
    else
      {
      echo "<br />\n";
      if ((gmdate('G',$crc_row['Date'])!=0) or (gmdate('i',$crc_row['Date'])!=0) or (gmdate('s',$crc_row['Date'])!=0))
        {
        echo "- <span class='error'>ERROR</span>: The time should be '00:00:00' not '".gmdate('H:i:s',$crc_row['Date'])."'<br />\n";
        $date_at_zero = strtotime(gmdate("d",$crc_row['Date'])." ".gmdate("M",$crc_row['Date'])." ".gmdate("Y",$crc_row['Date'])." 00:00:00 +0000");
        $sql="UPDATE Count SET Date = '".$date_at_zero."' WHERE countID = '".$crc_row['countID']."'";
        RUN_QUERY($sql,"Entry was not corrected.",$con);
        $check = mysql_fetch_array(mysql_query("SELECT Date FROM Count WHERE countID = '".$crc_row['countID']."'",$con));
        echo "...updated to: ".gmdate('r',$check['Date'])."<br />\n";
        }
      if ((gmdate('w',$crc_row['Date'])==0) or (gmdate('w',$crc_row['Date'])==6))
        {
        echo "- <span class='error'>ERROR</span>: The day should be 'Mon-Fri' not '".gmdate('D',$crc_row['Date'])."'<br />\n";
        echo "...CRC cannot automatically correct this error, because it is unsure what this date should be set to<br />\n";
        }
      }
    }
}

function CHECKSCHEDULEDATES($con)
{
  echo "Beginning CRC in table 'Schedule'...<br />\n";
  $crc_query = mysql_query("SELECT * FROM Schedule",$con);
  while ( $crc_row = mysql_fetch_array($crc_query) )
    {
    echo " scheduleID: ".$crc_row['scheduleID']." ";
    echo " Date: ".$crc_row['Date']." ";
    echo " Checking Time: ".gmdate('r',$crc_row['Date'])." "; 
    if (
        (gmdate('G',$crc_row['Date'])==0) and 
        (gmdate('i',$crc_row['Date'])==0) and 
        (gmdate('s',$crc_row['Date'])==0) and 
        (gmdate('w',$crc_row['Date'])!=0) and 
        (gmdate('w',$crc_row['Date'])!=6)
       )
      echo "- <span class='success'>Good</span><br />\n"; 
    else
      {
      echo "<br />\n";
      if ((gmdate('G',$crc_row['Date'])!=0) or (gmdate('i',$crc_row['Date'])!=0) or (gmdate('s',$crc_row['Date'])!=0))
        {
        echo "- <span class='error'>ERROR</span>: The time should be '00:00:00' not '".gmdate('H:i:s',$crc_row['Date'])."'<br />\n";
        $date_at_zero = strtotime(gmdate("d",$crc_row['Date'])." ".gmdate("M",$crc_row['Date'])." ".gmdate("Y",$crc_row['Date'])." 00:00:00 +0000");
        $sql="UPDATE Schedule SET Date = '".$date_at_zero."' WHERE scheduleID = '".$crc_row['scheduleID']."'";
        RUN_QUERY($sql,"Entry was not corrected.",$con);
        $check = mysql_fetch_array(mysql_query("SELECT Date FROM Schedule WHERE scheduleID = '".$crc_row['scheduleID']."'",$con));
        echo "...updated to: ".gmdate('r',$check['Date'])."<br />\n";
        }
      if ((gmdate('w',$crc_row['Date'])==0) or (gmdate('w',$crc_row['Date'])==6))
        {
        echo "- <span class='error'>ERROR</span>: The day should be 'Mon-Fri' not '".gmdate('D',$crc_row['Date'])."'<br />\n";
        echo "...CRC cannot automatically correct this error, because it is unsure what this date should be set to<br />\n";
        }
      }
    }
}

?>