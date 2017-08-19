<?php
// Copyright 2015 Todd Knarr
// Licensed under the terms of the GPL v3.0 or any later version

// We don't use the standard ini.php or authentication because this is intended to
// be grabbed by a cron job on the backup MX host. We use a special token in the
// query string to control access instead. The information here isn't sensitive,
// the most an attacker could get is a list of the valid recipient e-mail addresses
// and he'd have to guess the token (and know he had to guess it) first to get even
// that.

// Read settings from config file
$ini_file = parse_ini_file( '/etc/email_management.ini' ) or die( 'Error reading configuration.' );
// Get database connection settings from config file
$db_host = $ini_file ['host'];
$db_database = $ini_file ['dbname'];
$db_user = $ini_file ['user'];
$db_password = $ini_file ['password'];
// Get the current magic token
$backup_mx_token = $ini_file ['backup_mx_token'];

$magic_token = '';
$map_name = '';

// Get token from query string
if ( $_SERVER ['REQUEST_METHOD'] == 'GET' )
{
    if ( isset( $_GET ['token'] ) )
    {
        $t = $_GET ['token'];
        $magic_token = filter_var( $t, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );
    }
    if ( isset( $_GET ['map'] ) )
    {
        $m = $_GET ['map'];
        $map_name = filter_var( $m, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );
    }
}
else
{
    header( 'HTTP/1.0 405 Method Not Allowed' );
    header( 'Allow: GET' );
    die( 'Method Not Allowed' );
}

// Verify our token is correct
if ( $backup_mx_token == '' || $magic_token == '' || $magic_token != $backup_mx_token )
{
    header( 'HTTP/1.0 403 Forbidden' );
    die( 'Access Forbidden' );
}

// Generate maps
if ( $map_name == 'relay_domains' )
{
    header( 'Content-Type: text/plain' );

    // Connect to the database
    $link = mysqli_connect( $db_host, $db_user, $db_password, $db_database ) or die( mysqli_connect_error() );

    // Scan the domains table in sorted order
    $query = mysqli_query( $link, "SELECT name FROM hosted_domains ORDER BY name" ) or die( mysqli_error( $link ) );
    // Output the body of our table of domains
    while ( $cols = mysqli_fetch_array( $query ) )
    {
        $domain = $cols ['name'];
        if ( $domain != "" )
        {
            echo $domain . "\tOK" . PHP_EOL;
        }
    }
}
elseif ( $map_name == 'relay_recipients' )
{
    header( 'Content-Type: text/plain' );

    // Connect to the database
    $link = mysqli_connect( $db_host, $db_user, $db_password, $db_database ) or die( mysqli_connect_error() );

    // Build an array of hosted domains
    $domain_list = array();
    $query = mysqli_query( $link, "SELECT name FROM hosted_domains ORDER BY name" ) or die( mysqli_error( $link ) );
    while ( $cols = mysqli_fetch_array( $query ) )
    {
        $domain = $cols ['name'];
        if ( $domain != "" )
        {
            $domain_list [] = $domain;
        }
    }

    // Scan mail routing table for user entries, outputting entries and expanding domains as needed
    $q = "SELECT address_user, address_domain FROM mail_routing WHERE address_user != '*' ORDER BY address_user, address_domain";
    $query = mysqli_query( $link, $q ) or die( mysqli_error( $link ) );
    while ( $cols = mysqli_fetch_array( $query ) )
    {
        $user = $cols ['address_user'];
        $domain = $cols ['address_domain'];
        if ( $domain == '*' )
        {
            // Expand domains, output one line per domain
            foreach ( $domain_list as $i => $d )
            {
                echo $user . '@' . $d . "\tOK" . PHP_EOL;
            }
        }
        else
        {
            echo $user . '@' . $domain . "\tOK" . PHP_EOL;
        }
    }
    // Now select domain catch-all addresses and output them
    $q = "SELECT address_user, address_domain FROM mail_routing WHERE address_user = '*' ORDER BY address_domain";
    $query = mysqli_query( $link, $q ) or die( mysqli_error( $link ) );
    while ( $cols = mysqli_fetch_array( $query ) )
    {
        $user = $cols ['address_user'];
        $domain = $cols ['address_domain'];
        if ( $domain != '*' ) // Skip the all-domains catch-all, if we have one we couldn't have unknown recipients anyway
        {
            echo '@' . $domain . "\tOK" . PHP_EOL;
        }
    }
}
else
{
    header( 'HTTP/1.0 400 Bad Request' );
    die( 'Map ' . $map_name . ' not valid' );
}
?>
