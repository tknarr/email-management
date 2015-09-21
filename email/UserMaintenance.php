<?php require 'ini.php'; ?>
<!DOCTYPE html>
<!--
    Copyright 2015 Todd Knarr
    Licensed under the terms of the GPL v3.0 or any later version
 -->
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
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
$msg = '';
$msg2 = '';

if ( $_SERVER ['REQUEST_METHOD'] == "GET" )
{
    $raw_username = "";
    if ( isset( $_GET ['reset'] ) )
    {
        $u = $_GET ['reset'];
        $raw_username = filter_var( $u, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );
    }
    if ( !empty( $raw_username ) && $logged_in_admin )
    {
        $username = mysqli_real_escape_string( $link, $raw_username );
        $query = mysqli_query( $link, "UPDATE mail_users SET change_attempts = 0 WHERE username = '$username'" ) or die( mysqli_error( $link ) );
    }
}
// Check to see if the form has been submitted
if ( $_SERVER ['REQUEST_METHOD'] == "POST" )
{
    if ( !$logged_in_admin )
    {
        $msg = "You are not an administrator.";
    }
    elseif ( isset( $_POST[ 'add' ] ) )
    {
        // Raw new username, we'll escape it later
        $raw_username = $_POST[ 'username' ];
        if ( isset( $_POST[ 'alldomains' ] ) )
        {
            $all_domains = $_POST[ 'alldomains' ];
        }
        else
        {
            $all_domains = '';
        }
        $virt_user = $_POST[ 'virtualuser' ];
        // Collect the old and new password fields
        $npassword = $_POST[ 'npassword' ];
        $rpassword = $_POST[ 'rpassword' ];

        if ( !empty( $raw_username) )
        {
            $raw_username = preg_replace( '/[^A-Za-z0-9_\.]/', '', $raw_username );
        }

        if ( empty( $raw_username ) || empty( $npassword ) || empty( $rpassword ) )
        {
            $msg = "All fields are required.";
        }
        elseif ( $npassword != $rpassword )
        {
            $msg = "Your new passwords do not match.";
        }
        else
        {
            // Query the database to check for the new user's existence
            $username = mysqli_real_escape_string( $link, $raw_username );
            $query = mysqli_query( $link, "SELECT * FROM mail_users WHERE username = '$username'" ) or die( mysqli_error( $link ) );
            $numrows = mysqli_num_rows( $query );

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
                $hnpassword = crypt( $npassword, $nsalt );

                $accttype = 'S';
                if ( $username == 'root' )
                {
                    $accttype = 'R';
                }
                else if ( $virt_user == 'yes' )
                {
                    $accttype = 'V';
                }

                // Checks that have to be done after hashing passwords
                if ( substr( $hnpassword, 0, 3 ) != "$6$" )
                {
                    $msg = "An error occurred when hashing the new user's password.";
                }
                else
                {
                    $msg = "The new user was successfully added.";
                    mysqli_query( $link, "INSERT INTO mail_users ( username, password, acct_type ) VALUES ( '$username', '$hnpassword', '$accttype' )" ) or
                         die( mysqli_error( $link ) );
                    $op = [];
                    $retval = 0;
                    exec( '/usr/bin/sudo -u vmail /usr/local/bin/makevmaildir.sh '.$raw_username, $op, $retval );
                    if ( $retval != 0 )
                    {
                        $msg2 = "The mail directory was not created:";
                        foreach ( $op as $oline )
                        {
                            $msg2 = $msg2.'<br>'.PHP_EOL.htmlspecialchars( $oline );
                        }
                    }

                    if ( $all_domains == "yes" )
                    {
                        // When adding a new user and all-domains was checked, add a "user@* -> user" mail routing entry for them if one doesn't already exist
                        $query = mysqli_query( $link, "SELECT * FROM mail_routing WHERE address_user = '$username' AND address_domain = '*'" ) or
                            die( mysqli_error( $link ) );
                        $numrows = mysqli_num_rows( $query );
                        if ( $numrows == 0 )
                        {
                            $msg = "The new user was successfully added with an all-domains entry for mail.";
                            $query = mysqli_query( $link, "INSERT INTO mail_routing ( address_user, address_domain, recipient ) VALUES ( '$username', '*', '$username' )" ) or
                                die( mysqli_error( $link ) );
                        }
                    }
                }
            }
        }
    }
    elseif ( isset( $_POST[ 'delete' ] ) )
    {
        // Raw new username, we'll escape it later
        $raw_username = $_POST[ 'username' ];

        if ( empty( $raw_username ) )
        {
            $msg = "Username is required.";
        }
        else
        {
            // Query the database to check for the user's existence
            $username = mysqli_real_escape_string( $link, $raw_username );
            $query = mysqli_query( $link, "SELECT * FROM mail_users WHERE username = '$username'" ) or die( mysqli_error( $link ) );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );

            // When deleting a user that's the target of a default mail routing entry for a domain, complain
            $query = mysqli_query( $link, "SELECT * FROM mail_routing WHERE recipient = '$username' AND address_user = '*'" );
            $numrows_domain = mysqli_num_rows( $query );
            mysqli_free_result( $query );

            if ( $numrows == 0 )
            {
                $msg = "This username does not exist.";
            }
            else if ( $numrows_domain > 0 )
            {
                $msg = "User is the default destination for a domain.";
            }
            else if ( $username == 'root' )
            {
                $msg = "Root user must not be deleted.";
            }
            else
            {
                $msg = "The user was successfully deleted.";
                mysqli_query( $link, "DELETE FROM mail_users WHERE username = '$username'" ) or
                     die( mysqli_error( $link ) );

                // When deleting a user, delete any mail routing entries that specify them
                mysqli_query( $link, "DELETE FROM mail_routing WHERE recipient = '$username'" ) or die( mysqli_error( $link ) );
            }
        }
    }
}
mysqli_commit( $link ) or die( "Database commit failed." );
?>

<body>
<?php echo "    <h1 class=\"page_title\">".$title."</h1>".PHP_EOL; ?>

    <p>
        <table class="listing">
            <tr><th class="listing">Username</th><th class="listing">Type</th><th class="listing">Change<br>attempts</th><th class="listing_extra">&nbsp;</th></tr>
<?php
    // Scan the domains table in sorted order
    $query = mysqli_query( $link, "SELECT username, change_attempts, a.abbreviation AS abbreviation FROM mail_users, acct_types a WHERE acct_type = a.code ORDER BY username" ) or
        die( mysqli_error( $link ) );

    // Output the body of our table of domains
    while ( $cols = mysqli_fetch_array( $query ) )
    {
        $username = $cols[ 'username' ];
        $acct_type = $cols[ 'abbreviation' ];
        $change_attempts = $cols[ 'change_attempts' ];
        if ( $username != "" )
        {
            $rlink = "<a href=\"MailRouting.php?u=".urlencode( $username )."\">".htmlspecialchars( $username )."</a>";
            if ( $logged_in_admin )
            {
                $pw_btn = "<button type=\"button\" onclick=\"window.location.assign( &quot;ChangePassword.php?reset=".urlencode( $username )."&quot; )\">Chg Pwd</button>";
                $ca_btn = "<button type=\"button\" onclick=\"window.location.assign( &quot;UserMaintenance.php?reset=".urlencode( $username )."&quot; )\">Reset CAs</button>";
            }
            else
            {
                $pw_btn = "";
                $ca_btn = "";
            }
            echo "            <tr>".PHP_EOL;
            echo "              <td class=\"listing\">".$rlink."</td>".PHP_EOL;
            echo "              <td class=\"listing\">".$acct_type."</td>".PHP_EOL;
            echo "              <td class=\"listing_right\">".$change_attempts."</td>".PHP_EOL;
            echo "              <td class=\"listing_extra\">".$ca_btn." ".$pw_btn."</td>".PHP_EOL;
            echo "            </tr>".PHP_EOL;
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
                    <td class="entry_value"><input type="text" pattern="[A-Za-z0-9_.]+" name="username" value="" size="50" /></td>
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
                    <td class="entry_value"><input type="checkbox" name="alldomains" value="yes" /></td>
                </tr>
                <tr>
                    <td class="entry_label">Virtual user?</td>
<?php
$ckd = $default_virtual_users ? "checked " : "";
echo "                    <td class=\"entry_value\"><input type=\"checkbox\" name=\"virtualuser\" value=\"yes\" ".$ckd."/></td>".PHP_EOL;
?>
                </tr>
                <tr>
                    <td class="buttons">
                        <input type="submit" name="add" value="Add" />
                        <input type="submit" name="delete" value="Delete" />
                    </td>
                </tr>
            </table>
        </form>
    </p>

<?php if ( $msg != "" ) echo "    <p class=\"message\">".$msg."</p>".PHP_EOL; ?>
<?php if ( $msg2 != "" ) echo "    <p class=\"message\">".$msg2."</p>".PHP_EOL; ?>

    <p class="footer"><a href="admin.php">Return to system administration links</a></p>

</body>
</html>