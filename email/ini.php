<?php
// Copyright 2015 Todd Knarr
// Licensed under the terms of the GPL v3.0

// Read settings from config file
$ini_file = parse_ini_file( "/etc/email_management.ini" ) or die( "Error reading configuration." );
$org = $ini_file ["organization"];
if ( empty( $org ) )
{
    $org = $_ENV ["ORGANIZATION"];
}
// Get database connection settings from config file
$db_host = $ini_file ["host"];
$db_database = $ini_file ["dbname"];
$db_user = $ini_file ["user"];
$db_password = $ini_file ["password"];

// Max tries for wrong password
$max_tries = 3;
$tries = 0;

// Error message starts off empty
$msg = "";
?>