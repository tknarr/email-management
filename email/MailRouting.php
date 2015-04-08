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
    $title = htmlspecialchars( $org." e-mail routing" );
}
else 
{
    $title = "E-mail routing";
}

echo "<title>".$title."</title>";
?>
<link href="main.css" rel="stylesheet" type="text/css" title="Standard styles" />
</head>

<?php
// Administrative username, we'll escape it later
$raw_admin_username = $ini_file ["admin_user"];

// Connect to the database
$link = mysqli_connect( $db_host, $db_user, $db_password, $db_database ) or die( mysqli_connect_error() );
mysqli_autocommit( $link, FALSE );

// Check to see if the form has been submitted
if ( $_SERVER ['REQUEST_METHOD'] == "POST" )
{
    if ( $_POST ['add'] )
    {
        // Raw new user, domain, recipient, we'll escape them later
        $raw_user = $_POST ['user'];
        $raw_domain = $_POST ['domain'];
        $raw_recipient = $_POST ['recipient'];
        // Collect the password field
        $apassword = $_POST ['apassword']; // Admin password
                                            
        // Validate the form fields
        if ( empty( $raw_user ) || empty( $raw_domain ) || empty( $raw_recipient ) || empty( $apassword ) )
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
                $admin_password = $cols ['password'];
                $tries = $cols ['change_attempts'];
            }
            mysqli_free_result( $query );
            
            // Query the database to check for the new user's existence
            $user = mysqli_real_escape_string( $link, $raw_user );
            $domain = mysqli_real_escape_string( $link, $raw_domain );
            $query = mysqli_query( $link, "SELECT * FROM virtual_aliases WHERE address_user = '$user' AND address_domain = '$domain'" ) or
                             die( mysqli_error() );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );
            
            // Validate that requires the database
            if ( $numrows != 0 )
            {
                $msg = "This mail routing entry already exists.";
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
                    $recipient = mysqli_real_escape_string( $link, $raw_recipient );
                    $msg = "The new mail routing entry was successfully added.";
                    mysqli_query( $link, "INSERT INTO virtual_aliases ( address_user, address_domain, recipient ) VALUES ( '$user', '$domain', '$recipient' )" ) or
                                     die( mysqli_error() );
                    mysqli_query( $link, "UPDATE virtual_users SET change_attempts = 0 WHERE username = '$admin_username'" );
                }
            }
        }
    }
    elseif ( $_POST ['delete'] )
    {
        // Raw new user, domain, we'll escape them later
        $raw_user = $_POST ['user'];
        $raw_domain = $_POST ['domain'];
        // Collect the password field
        $apassword = $_POST ['apassword']; // Admin password
                                            
        // Validate the form fields
        if ( empty( $raw_user ) || empty( $raw_domain ) || empty( $apassword ) )
        {
            $msg = "User, domain and administrative password are required.";
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
                $admin_password = $cols ['password'];
                $tries = $cols ['change_attempts'];
            }
            mysqli_free_result( $query );
            
            // Query the database to check for the new user's existence
            $user = mysqli_real_escape_string( $link, $raw_user );
            $domain = mysqli_real_escape_string( $link, $raw_domain );
            $query = mysqli_query( $link, "SELECT * FROM virtual_aliases WHERE user = '$user' AND domain = '$domain'" ) or
                             die( mysqli_error() );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );
            
            // Validate that requires the database
            if ( $numrows == 0 )
            {
                $msg = "This mail routing entry does not exist.";
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
                    $msg = "The mail routing entry was successfully deleted.";
                    mysqli_query( $link, "DELETE FROM virtual_aliases WHERE user = '$user' AND domain = '$domain'" ) or
                                     die( mysqli_error() );
                    mysqli_query( $link, "UPDATE virtual_users SET change_attempts = 0 WHERE username = '$admin_username'" );
                }
            }
        }
    }
    elseif ( $_POST ['update'] )
    {
        // Raw new user, domain, recipient, we'll escape them later
        $raw_user = $_POST ['user'];
        $raw_domain = $_POST ['domain'];
        $raw_recipient = $_POST ['recipient'];
        // Collect the password field
        $apassword = $_POST ['apassword']; // Admin password
                                            
        // Validate the form fields
        if ( empty( $raw_user ) || empty( $raw_domain ) || empty( $raw_recipient ) || empty( $apassword ) )
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
                $admin_password = $cols ['password'];
                $tries = $cols ['change_attempts'];
            }
            mysqli_free_result( $query );
            
            // Query the database to check for the new user's existence
            $user = mysqli_real_escape_string( $link, $raw_user );
            $domain = mysqli_real_escape_string( $link, $raw_domain );
            $query = mysqli_query( $link, "SELECT * FROM virtual_aliases WHERE address_user = '$user' AND address_domain = '$domain'" ) or
                             die( mysqli_error() );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );
            
            // Validate that requires the database
            if ( $numrows != 0 )
            {
                $msg = "This mail routing entry already exists.";
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
                    $recipient = mysqli_real_escape_string( $link, $raw_recipient );
                    $msg = "The mail routing entry was successfully updated.";
                    mysqli_query( $link, "UPDATE virtual_aliases SET recipient = '$recipient' WHERE address_user = '$user' AND address_domain = '$domain'" ) or
                                     die( mysqli_error() );
                    mysqli_query( $link, "UPDATE virtual_users SET change_attempts = 0 WHERE username = '$admin_username'" );
                }
            }
        }
    }
}

mysqli_commit( $link ) or die( "Database commit failed." );
?>

<body>
<?php echo "    <h1>".$title."</h1>";?>

    <p>
        <table>
            <tr>
                <th>Addressee user</th>
                <th>Addressee domain</th>
                <th>Recipient</th>
            </tr>
<?php
    function ttk_output_entries( $q )
    {
        // Output rows of the body of our table of domains
        while ( $cols = mysqli_fetch_array( $q ) )
        {
            $user = $cols[ 'address_user' ];
            $domain = $cols[ 'address_domain' ];
            $recipient = $cols[ 'recipient' ];
            echo "            <tr><td>".htmlspecialchars( $user )."</td><td>".htmlspecialchars( $domain )."</td><td>".htmlspecialchars( $recipient )."</td></tr>";
        }
    }

    // Scan the domains table in the same order the stored procedure will check them.
    // First non-wildcard entries
    $query = mysqli_query( $link, "SELECT address_user, address_domain, recipient FROM virtual_aliases WHERE address_user != '*' AND address_domain != '*' ORDER BY address_domain, address_user" ) or
        die( mysqli_error() );
    ttk_output_entries( $query );
    mysqli_free_result( $query );
    // Then entries with a non-wildcard user and a wildcard domain
    $query = mysqli_query( $link, "SELECT address_user, address_domain, recipient FROM virtual_aliases WHERE address_user != '*' AND address_domain = '*' ORDER BY address_domain, address_user" ) or
        die( mysqli_error() );
    ttk_output_entries( $query );
    mysqli_free_result( $query );
    // Then entries with a wildcard user and a non=wildcard domain
    $query = mysqli_query( $link, "SELECT address_user, address_domain, recipient FROM virtual_aliases WHERE address_user = '*' AND address_domain != '*' ORDER BY address_domain, address_user" ) or
        die( mysqli_error() );
    ttk_output_entries( $query );
    mysqli_free_result( $query );
    // And finally the all-wildcards entry if any
    $query = mysqli_query( $link, "SELECT address_user, address_domain, recipient FROM virtual_aliases WHERE address_domain = '*' AND address_user = '*' ORDER BY address_domain, address_user" ) or
        die( mysqli_error() );
    ttk_output_entries( $query );
    mysqli_free_result( $query );
?>
        </table>
    </p>

    <p>
        <form method="POST" action="MailRouting.php">
            <table>
                <tr>
                    <td align="right">Addressee user:</td>
                    <td><input type="text" name="user" value="" size="50" /></td>
                </tr>
                <tr>
                    <td align="right">Addressee domain:</td>
                    <td><input type="text" name="domain" value="" size="50" /></td>
                </tr>
                <tr>
                    <td align="right">Recipient:</td>
                    <td><input type="text" name="recipient" value="" size="50" /></td>
                </tr>
                <tr>
                    <td align="right">Administrative Password:</td>
                    <td><input type="password" name="apassword" value="" size="50" /></td>
                </tr>
                <tr>
                    <td>
                        <input type="submit" name="add" value="Add" />
                        <input type="submit" name="delete" value="Delete" />
                        <input type="submit" name="update" value="Update" />
                    </td>
                </tr>
            </table>
        </form>
    </p>
<?php if ( $msg != "" ) echo "    <p>".$msg."</p>"; ?>
</body>
</html>