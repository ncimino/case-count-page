<?php
include_once("./include/includes.php");
DB_CONNECT($con);
//mysqldump --add-drop-table -u sadmin -p pass21 Customers > custback.sql
$sql = "mysqldump --add-drop-table -u ".DB_LOGIN." -p ".DB_PASSWORD." ".DB_NAME." > ".time().".sql";
RUN_QUERY($sql,"Failed to back DB.",$con);


?>