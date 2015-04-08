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
    $title = htmlspecialchars( $org." e-mail virtual domains" );
}
else 
{
    $title = "E-mail virtual domains";
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
        $raw_domain = $_POST[ 'domain' ];
        $raw_default_user = $_POST[ 'defaultuser' ];
        // Collect the password field
        $apassword = $_POST[ 'apassword' ]; // Admin password
    
        // Validate the form fields
        if ( empty( $raw_domain ) || empty( $apassword ) )
        {
            $msg = "All fields are required.";
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
            mysqli_free_result( $query );
    
            // Query the database to check for the new user's existence
            $domain = mysqli_real_escape_string( $link, $raw_domain );
            $query = mysqli_query( $link, "SELECT * FROM virtual_domains WHERE name = '$domain'" ) or die( mysqli_error() );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );
    
            // Validate that requires the database
            if ( $numrows != 0 )
            {
                $msg = "This domain already exists.";
            }
            else
            {
                // Hash the old and new passwords
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
                    $msg = "The new domain was successfully added.";
                    mysqli_query( $link, "INSERT INTO virtual_domains ( name ) VALUES ( '$domain' )" ) or
                         die( mysqli_error() );
                    mysqli_query( $link, "UPDATE virtual_users SET change_attempts = 0 WHERE username = '$admin_username'" );
    
                    if ( !empty( $raw_default_user ) )
                    {
                        // If a default user was given and no default mail routing exists for the domain, add one to that user
                        $default_user =  mysqli_real_escape_string( $link, $raw_default_user );
                        $query = mysqli_query( $link, "SELECT * FROM virtual_aliases WHERE address_user = '*' AND address_domain = '$domain'" ) or
                            die( mysqli_error() );
                        $numrows = mysqli_num_rows( $query );
                        mysqli_free_result( $query );
                        if ( $numrows == 0 )
                        {
                            $msg = "The new domain was successfully added with a catch-all recipient for mail.";
                            $query = mysqli_query( $link, "INSERT INTO virtual_aliases ( address_user, address_domain, recipient ) VALUES ( '*', '$domain', '$default_user' )" ) or
                                die( mysqli_error() );
                        }
                    }
                }
            }
        }
    }
    elseif ( $_POST[ 'delete' ] )
    {
        // Raw new username, we'll escape it later
        $raw_domain = $_POST[ 'domain' ];
        // Collect the password field
        $apassword = $_POST[ 'apassword' ]; // Admin password
    
        // Validate the form fields
        if ( empty( $raw_domain ) || empty( $apassword ) )
        {
            $msg = "All fields are required.";
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
            mysqli_free_result( $query );
    
            // Query the database to check for the new user's existence
            $domain = mysqli_real_escape_string( $link, $raw_domain );
            $query = mysqli_query( $link, "SELECT * FROM virtual_domains WHERE name = '$domain'" ) or die( mysqli_error() );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );
    
            // Validate that requires the database
            if ( $numrows == 0 )
            {
                $msg = "This domain does not exist.";
            }
            else
            {
                // Hash the old and new passwords
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
                    $msg = "The new domain was successfully deleted.";
                    mysqli_query( $link, "DELETE FROM virtual_domains WHERE name = '$domain'" ) or
                         die( mysqli_error() );
                    mysqli_query( $link, "UPDATE virtual_users SET change_attempts = 0 WHERE username = '$admin_username'" );
    
                    // Delete all mail routing entries for the domain, if any
                    mysqli_query( $link, "DELETE FROM virtual_aliases WHERE address_domain = '$domain'" );
                }
            }
        }
    }
}
mysqli_commit( $link ) or die( "Database commit failed." );
?>

<body>
<?php echo "    <h1>".$title."</h1>".PHP_EOL;?>

    <p>
        <table class="listing">
            <tr><th class="listing">Domain name</th><th class="listing">Default user</th></tr>
<?php
    $wildcard_user = '-';
    $query3 = mysqli_query( $link, "SELECT recipient FROM virtual_aliases WHERE address_user = '*' AND address_domain = '*'" ) or
        die( mysqli_error() );
    if ( $cols3 = mysqli_fetch_array( $query3 ) )
    {
        $wildcard_user = $cols3[ 'recipient' ];
    }
    mysqli_free_result( $query3 );

    // Scan the domains table in sorted order
    $query = mysqli_query( $link, "SELECT name FROM virtual_domains ORDER BY name" ) or
        die( mysqli_error() );

    // Output the body of our table of domains
    while ( $cols = mysqli_fetch_array( $query ) )
    {
        $domain = $cols[ 'name' ];
        if ( $domain != "" )
        {
            $query2 = mysqli_query( $link, "SELECT recipient FROM virtual_aliases WHERE address_user = '*' AND address_domain = '$domain'" ) or
                die( mysqli_error() );
            $default_user = $wildcard_user;
            if ( $cols2 = mysqli_fetch_array( $query2 ) )
            {
                $default_user = $cols2[ 'recipient' ];
            }
            mysqli_free_result( $query2 );
            echo "            <tr><td class=\"listing\">".htmlspecialchars( $domain )."</td><td class=\"listing\">".htmlspecialchars( $default_user )."</td></tr>";
        }
    }
    mysqli_free_result( $query );
?>
        </table>
    </p>
    
    <p>
        <form method="POST" action="VirtualDomains.php">
            <table class="entry">
                <tr>
                    <td class="entry_label">Domain:</td>
                    <td class="entry_value"><input type="text" name="domain" value="" size="50" /></td>
                </tr>
                <tr>
                    <td class="entry_label">Default user:</td>
                    <td class="entry_value"><input type="text" name="defaultuser" value="" size="50" /></td>
                </tr>
                <tr>
                    <td class="entry_label">Administrative Password:</td>
                    <td class="entry_value"><input type="password" name="apassword" value="" size="50" /></td>
                </tr>
                <tr>
                    <td class="buttons"><input type="submit" name="add" value="Add Domain" /> <input type="submit" name="delete" value="Delete Domain" /></td>
                </tr>
            </table>
        </form>
    </p>
<?php if ( $msg != "" ) echo "    <p class=\"message\">".$msg."</p>".PHP_EOL; ?>
</body>
</html>