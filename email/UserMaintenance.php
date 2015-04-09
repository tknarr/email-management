<!DOCTYPE html>
<!--
    Copyright 2015 Todd Knarr
    Licensed under the terms of the GPL v3.0
 -->
<html>
<head>
<meta charset="UTF-8" />
<?php
require 'ini.php';

if ( !empty( $org ) )
{
    $title = htmlspecialchars( $org." e-mail user maintenance" );
}
else 
{
    $title = "E-mail user maintenance";
}

echo "<title>".$title."</title>".PHP_EOL;
?>
<link href="main.css" rel="stylesheet" type="text/css" title="Standard styles" />
</head>

<?php
// Administrative username, we'll escape it later
$raw_admin_username = $ini_file[ "admin_user" ];

// Connect to the database
$link = mysqli_connect( $db_host, $db_user, $db_password, $db_database ) or die( mysqli_connect_error() );
mysqli_autocommit( $link, FALSE );

// Check to see if the form has been submitted
if ( $_SERVER ['REQUEST_METHOD'] == "POST" )
{
    if ( $_POST[ 'add' ] )
    {
        // Raw new username, we'll escape it later
        $raw_username = $_POST[ 'username' ];
        $all_domains = $_POST[ 'alldomains' ];
        // Collect the old and new password fields
        $apassword = $_POST[ 'apassword' ]; // Admin password
        $npassword = $_POST[ 'npassword' ];
        $rpassword = $_POST[ 'rpassword' ];
    
        // Validate the form fields
        if ( empty( $raw_username ) || empty( $apassword ) || empty( $npassword ) || empty( $rpassword ) )
        {
            $msg = "All fields are required.";
        }
        elseif ( $npassword != $rpassword )
        {
            $msg = "Your new passwords do not match.";
        }
        else
        {
            // Query the database to find our admin user's password
            $admin_username = mysqli_real_escape_string( $link, $raw_admin_username );
            $query = mysqli_query( $link, "SELECT password, change_attempts FROM virtual_users WHERE username = '$admin_username'" ) or
                 die( mysqli_error() );
            $numrows = mysqli_num_rows( $query );
            // Gather database information
            while ( $cols = mysqli_fetch_array( $query ) )
            {
                $admin_password = $cols[ 'password' ];
                $tries = $cols[ 'change_attempts' ];
            }
    
            // Query the database to check for the new user's existence
            $username = mysqli_real_escape_string( $link, $raw_username );
            $query = mysqli_query( $link, "SELECT * FROM virtual_users WHERE username = '$username'" ) or die( mysqli_error() );
            $numrows = mysqli_num_rows( $query );
    
            // Validate that requires the database
            if ( $numrows != 0 )
            {
                $msg = "This username already exists.";
            }
            else
            {
                // Generate new SHA512 salt: 12 random bytes, base64-encoded to produce 16 characters
                $nsalt = "$6$" . base64_encode( mcrypt_create_iv( 12 ) ) . "$";
    
                // Hash the old and new passwords
                // This depends on a Linux-type crypt() implementation
                $hapassword = crypt( $apassword, $admin_password );
                $hnpassword = crypt( $npassword, $nsalt );
    
                // Checks that have to be done after hashing passwords
                if ( $hapassword != $admin_password || $tries >= $max_tries )
                {
                    $msg = "The administrative password you entered is incorrect.";
                    mysqli_query( $link, "UPDATE virtual_users SET change_attempts = change_attempts + 1 where username = '$admin_username'" );
                }
                elseif ( substr( $hnpassword, 0, 3 ) != "$6$" )
                {
                    $msg = "An error occurred when hashing the new user's password.";
                }
                else
                {
                    $msg = "The new user was successfully added.";
                    mysqli_query( $link, "INSERT INTO virtual_users ( username, password ) VALUES ( '$username', '$hnpassword' )" ) or
                         die( mysqli_error() );
                    mysqli_query( $link, "UPDATE virtual_users SET change_attempts = 0 WHERE username = '$admin_username'" );
    
                    if ( $all_domains == "yes" )
                    {
                        // When adding a new user and all-domains was checked, add a "user@* -> user" mail routing entry for them if one doesn't already exist
                        $query = mysqli_query( $link, "SELECT * FROM virtual_aliases WHERE address_user = '$username' AND address_domain = '*'" ) or
                            die( mysqli_error() );
                        $numrows = mysqli_num_rows( $query );
                        if ( $numrows == 0 )
                        {
                            $msg = "The new user was successfully added with an all-domains entry for mail.";
                            $query = mysqli_query( $link, "INSERT INTO virtual_aliases ( address_user, address_domain, recipient ) VALUES ( '$username', '*', '$username' )" ) or
                                die( mysqli_error() );
                        }
                    }
                }
            }
        }
    }
    else if ( $_POST[ 'delete' ] )
    {
        // Read database connection settings from config file
        $ini_file = parse_ini_file( "/etc/email_management.ini" ) or die( "Error reading configuration." );
        $db_host = $ini_file[ "host" ];
        $db_database = $ini_file[ "dbname" ];
        $db_user = $ini_file[ "user" ];
        $db_password = $ini_file[ "password" ];
        // Administrative username, we'll escape it later
        $raw_admin_username = $ini_file[ "admin_user" ];
    
        // Raw new username, we'll escape it later
        $raw_username = $_POST[ 'username' ];
        // Collect the admin password
        $apassword = $_POST[ 'apassword' ]; // Admin password
    
        // Validate the form fields
        if ( empty( $raw_username ) || empty( $apassword ) )
        {
            $msg = "Username and administrative password are required.";
        }
        else
        {
            // Connect to the database
            $link = mysqli_connect( $db_host, $db_user, $db_password, $db_database ) or die( mysqli_connect_error() );
    
            // Query the database to find our admin user's password
            $admin_username = mysqli_real_escape_string( $link, $raw_admin_username );
            $query = mysqli_query( $link, "SELECT password, change_attempts FROM virtual_users WHERE username = '$admin_username'" ) or
                 die( mysqli_error() );
            $numrows = mysqli_num_rows( $query );
            // Gather database information
            while ( $cols = mysqli_fetch_array( $query ) )
            {
                $admin_password = $cols[ 'password' ];
                $tries = $cols[ 'change_attempts' ];
            }
            mysqli_free_result( $query );
    
            // Query the database to check for the user's existence
            $username = mysqli_real_escape_string( $link, $raw_username );
            $query = mysqli_query( $link, "SELECT * FROM virtual_users WHERE username = '$username'" ) or die( mysqli_error() );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );
    
            // When deleting a user that's the target of a default mail routing entry for a domain, complain
            $query = mysqli_query( $link, "SELECT * FROM virtual_aliases WHERE recipient = '$username' AND address_user = '*'" );
            $numrows_domain = mysqli_num_rows( $query );
            mysqli_free_result( $query );
            
            // Validate that requires the database
            if ( $numrows == 0 )
            {
                $msg = "This username does not exist.";
            }
            else if ( $numrows_domain > 0 )
            {
                $msg = "User is the default destination for a domain.";
            }
            else
            {
                // Hash the admin password
                // This depends on a Linux-type crypt() implementation
                $hapassword = crypt( $apassword, $admin_password );
     
                // Checks that have to be done after hashing passwords
                if ( $hapassword != $admin_password || $tries >= $max_tries )
                {
                    $msg = "The administrative password you entered is incorrect.";
                    mysqli_query( $link, "UPDATE virtual_users SET change_attempts = change_attempts + 1 where username = '$admin_username'" );
                }
                else
                {
                    $msg = "The user was successfully deleted.";
                    mysqli_query( $link, "DELETE FROM virtual_users WHERE username = '$username'" ) or
                         die( mysqli_error() );
                    mysqli_query( $link, "UPDATE virtual_users SET change_attempts = 0 WHERE username = '$admin_username'" );
    
                    // When deleting a user, delete any mail routing entries that specify them
                    mysqli_query( $link, "DELETE FROM virtual_aliases WHERE recipient = '$username'" ) or die( mysqli_error() );
                }
            }
        }
    }
}
mysqli_commit( $link ) or die( "Database commit failed." );
?>

<body>
<?php echo "    <h1 class=\"page_title\">".$title."</h1>".PHP_EOL;?>

    <p>
        <table class="listing">
            <tr><th class="listing">Username</th><th class="listing">Change attempts</th></tr>
<?php
    // Scan the domains table in sorted order
    $query = mysqli_query( $link, "SELECT username, change_attempts FROM virtual_users ORDER BY username" ) or
        die( mysqli_error() );

    // Output the body of our table of domains
    while ( $cols = mysqli_fetch_array( $query ) )
    {
        $username = $cols[ 'username' ];
        $change_attempts = $cols[ 'change_attempts' ];
        if ( $username != "" )
        {
            echo "            <tr><td class=\"listing\">".htmlspecialchars( $username )."</td><td class=\"listing\">".$change_attempts."</td></tr>";
        }
    }
    mysqli_free_result( $query );
?>
        </table>
    </p>

    <p>
        <form method="POST" action="UserMaintenance.php">
            <table class="entry">
                <tr>
                    <td class="entry_label">Username:</td>
                    <td class="entry_value"><input type="text" name="username" value="" size="50" /></td>
                </tr>
                <tr>
                    <td class="entry_label">Password:</td>
                    <td class="entry_value"><input type="password" name="npassword" value="" size="50" /></td>
                </tr>
                <tr>
                    <td class="entry_label">Repeat Password:</td>
                    <td class="entry_value"><input type="password" name="rpassword" value="" size="50" /></td>
                </tr>
                <tr>
                    <td class="entry_label">All domains?</td>
                    <td class="entry_value"><input type="checkbox" name="alldomains" /></td>
                </tr>
                <tr>
                    <td class="entry_label">Administrative Password:</td>
                    <td class="entry_value"><input type="password" name="apassword" value="" size="50" /></td>
                </tr>
                <tr>
                    <td class="buttons">
                        <input type="submit" name="add" value="Add User" />
                        <input type="submit" name="delete" value="Delete User" />
                    </td>
                </tr>
            </table>
        </form>
    </p>
<?php if ( $msg != "" ) echo "    <p class=\"message\">".$msg."</p>".PHP_EOL; ?>
</body>
</html>