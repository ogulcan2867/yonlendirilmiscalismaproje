<?php

$x = strpos($_SERVER["SCRIPT_NAME"], "developer") > 0 ? "../" : null;
include($x . "ayar.php");

if (!$GLOBALS['DB'] = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD)) {
	die("Error: Unable to connect to database server.");
}

if (!mysql_select_db(DB_SCHEMA, $GLOBALS['DB'])) {
	mysql_close($GLOBALS['DB']);
	die("Error: Unable to select database schema.");
}

mysql_query("SET NAMES 'utf8'");
?>