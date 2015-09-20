<?php
// Copyright 2015 Todd Knarr
// Licensed under the terms of the GPL v3.0 or any later version

// Read settings from config file
$ini_file = parse_ini_file( "/etc/email_management.ini" ) or die( "Error reading configuration." );
$org = $ini_file ["organization"];
if ( empty( $org ) )
{
    $org = $_ENV ["ORGANIZATION"];
}
$default_user_type = strtolower( trim( $ini_file["default_user_type"]) );
// Get database connection settings from config file
$db_host = $ini_file ["host"];
$db_database = $ini_file ["dbname"];
$db_user = $ini_file ["user"];
$db_password = $ini_file ["password"];

// Generate the list of admin usernames
$au_raw = trim( $ini_file ["admin_user"] );
if ( !$au_raw )
    $au_raw = "root";
$au_list = explode( ",", $au_raw );
$admin_users = array();
foreach ( $au_list as $au_item )
{
    $au_t = trim( $au_item );
    if ( $au_t )
        $admin_users [] = $au_t;
}

// Max tries for wrong password
$max_tries = 3;
$tries = 0;

// Error message starts off empty
$msg = "";

// Functions to validate passwords and to check whether a user is an admin or not

// Returns true if user's password validates, false if it doesn't.
function validate_user( $l, $u, $p, $mt )
{
    // Query the database to find the user's password
    $u_esc = mysqli_real_escape_string( $l, $u );
    $query = mysqli_query( $l, "SELECT password, change_attempts FROM mail_users WHERE username = '$u_esc'" ) or
                     die( mysqli_error( $link ) );
    // If user not found, fail
    $numrows = mysqli_num_rows( $query );
    if ( $numrows == 0 )
        return false;
    while ( $cols = mysqli_fetch_array( $query ) )
    {
        // Hashed form of correct password
        $hpassword = $cols ['password'];
        // Number of tries at authentication or changing the password
        $tries = $cols ['change_attempts'];
    }
    // Check for problems with correct password hash
    if ( !$hpassword || substr( $hpassword, 0, 3 ) != "$6$" )
        return false;
        // Hashed form of entered password
    $hp = crypt( $p, $hpassword );
    if ( substr( $hp, 0, 3 ) != "$6$" )
        return false;
        
        // If passwords don't match or maximum tries exceeded, increment the tries counter and return a fail
    if ( $hp != $hpassword || $tries >= $mt )
    {
        mysqli_query( $l, "UPDATE mail_users SET change_attempts = change_attempts + 1 where username = '$u_esc'" );
        mysqli_commit( $l ) or die( "Database error." );
        return false;
    }
    // We passed all checks
    return true;
}

// Returns true if the user is on the admin list, false otherwise
function is_admin( $al, $u )
{
    if ( !$u )
        return false;
    foreach ( $al as $au )
    {
        if ( $u == $au )
            return true;
    }
    return false;
}

// Connect to the database
$link = mysqli_connect( $db_host, $db_user, $db_password, $db_database ) or die( mysqli_connect_error() );
mysqli_autocommit( $link, FALSE );

// Run our user authentication, fail if the browser isn't sending credentials or the password validation fails
if ( !isset( $_SERVER ['PHP_AUTH_USER'] ) || !isset( $_SERVER ['PHP_AUTH_PW'] ) ||
     !validate_user( $link, $_SERVER ['PHP_AUTH_USER'], $_SERVER ['PHP_AUTH_PW'], $max_tries ) )
{
    header( 'WWW-Authenticate: Basic realm="EMail Admin System"' );
    header( 'HTTP/1.0 401 Unauthorized' );
    die( 'You must be logged in to use the EMail admin system.' );
}
// Shorthand variables for username and admin status
$logged_in_user = $_SERVER ['PHP_AUTH_USER'];
$logged_in_admin = is_admin( $admin_users, $logged_in_user );
// Shorthand variable for default user type
$default_virtual_users = ( $default_user_type == 'virtual' );
?>