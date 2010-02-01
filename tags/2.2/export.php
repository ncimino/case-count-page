<?php
include_once("./include/includes.php");
DB_CONNECT($con);

header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"case_data_".gmdate('Y\_m\_d',$_GET['export_date']).".csv\"");

$current_week = DETERMINE_WEEK($_GET['export_date']);

$sql = "SELECT UserName,Users.userID
        FROM Users,UserSites 
        WHERE Active=1 
          AND Users.userID=UserSites.userID 
          AND siteID='".$_GET['export_page']."' 
        ORDER BY UserName ASC";
$activeusers = mysql_query($sql,$con);

$csv_file .= SITE_NAME($_GET['export_page'],$con).",\n\n";
$csv_file .= "Week Totals,\n";
$csv_file .= "Name,";
for ($i=0;$i<5;$i++)
$csv_file .= gmdate("D n/j",$current_week[$i]).",";
$csv_file .= "Week Total,\n";
while ( $currentuser = mysql_fetch_array($activeusers) )
{
  $week_total = 0;
  for ($col=1; $col<=6; $col++)
  {
    if ($col==1)
    {
      $csv_file .= $currentuser['UserName'].",";
    }
    else
    {
      $sql = "SELECT Regular+CatOnes+Special,Date
        FROM Count 
        WHERE userID='".$currentuser['userID']."' 
          AND Date='".$current_week[$col-2]."' 
          AND siteID=".$_GET['export_page'].";";
      $usercounts = mysql_fetch_array(mysql_query($sql,$con));

      if ($usercounts['Regular+CatOnes+Special'] == '')
      $total = 0;
      else
      $total = $usercounts['Regular+CatOnes+Special'];

      $week_total += $total;

      $csv_file .= $total.",";
    }
  }
  $csv_file .= $week_total.",\n";
}

$csv_file .= "Totals,";
$total = 0;
for ($i=0;$i<5;$i++)
{
  $sql = "SELECT SUM(Regular+CatOnes+Special)
          FROM Count 
          WHERE Date='".$current_week[$i]."' 
            AND siteID=".$_GET['export_page'].";";
  $totalcounts = mysql_fetch_array(mysql_query($sql,$con));
  
  if ($totalcounts['SUM(Regular+CatOnes+Special)'] == '')
  $csv_file .= "0,";
  else
  $csv_file .= $totalcounts['SUM(Regular+CatOnes+Special)'].",";

  $total += $totalcounts['SUM(Regular+CatOnes+Special)'];
}
$csv_file .= "Total:,{$total},\n";

$csv_file .= "\n";

$csv_file .= "Detail Totals,,";
$csv_file .= gmdate("n/j",$current_week[0])."-".gmdate("n/j",$current_week[4]).",\n";
$csv_file .= "Name,Regular,Cat 1,Special,Transfer Out\n";
mysql_data_seek($activeusers,0);
while ( $currentuser = mysql_fetch_array($activeusers) )
{
  $csv_file .= $currentuser['UserName'].",";
  $sql = "SELECT SUM(Regular),SUM(CatOnes),SUM(Special),SUM(Transfer),Date
        FROM Count 
        WHERE userID='".$currentuser['userID']."' 
          AND Date>='".$current_week[0]."' 
          AND Date<='".$current_week[4]."' 
          AND siteID=".$_GET['export_page']."
        GROUP BY siteID;";
  $usercounts = mysql_fetch_array(mysql_query($sql,$con));
  mysql_error($con);

  if ($usercounts['SUM(Regular)'] == '')
  $csv_file .= "0,";
  else
  $csv_file .= $usercounts['SUM(Regular)'].",";

  if ($usercounts['SUM(CatOnes)'] == '')
  $csv_file .= "0,";
  else
  $csv_file .= $usercounts['SUM(CatOnes)'].",";

  if ($usercounts['SUM(Special)'] == '')
  $csv_file .= "0,";
  else
  $csv_file .= $usercounts['SUM(Special)'].",";

  if ($usercounts['SUM(Transfer)'] == '')
  $csv_file .= "0,";
  else
  $csv_file .= $usercounts['SUM(Transfer)'].",";

  $csv_file .= "\n";
}

$csv_file .= "Totals,";
$sql = "SELECT SUM(Regular),SUM(CatOnes),SUM(Special),SUM(Transfer),Date
      FROM Count 
      WHERE Date>='".$current_week[0]."' 
        AND Date<='".$current_week[4]."' 
        AND siteID=".$_GET['export_page']."
      GROUP BY siteID;";
$usercounts = mysql_fetch_array(mysql_query($sql,$con));
mysql_error($con);

if ($usercounts['SUM(Regular)'] == '')
$csv_file .= "0,";
else
$csv_file .= $usercounts['SUM(Regular)'].",";

if ($usercounts['SUM(CatOnes)'] == '')
$csv_file .= "0,";
else
$csv_file .= $usercounts['SUM(CatOnes)'].",";

if ($usercounts['SUM(Special)'] == '')
$csv_file .= "0,";
else
$csv_file .= $usercounts['SUM(Special)'].",";

if ($usercounts['SUM(Transfer)'] == '')
$csv_file .= "0,";
else
$csv_file .= $usercounts['SUM(Transfer)'].",";

$csv_file .= "\n";

echo $csv_file;

?>