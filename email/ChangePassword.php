<?php require 'ini.php'; ?>
<!DOCTYPE html>
<!--
    Copyright 2015 Todd Knarr
    Licensed under the terms of the GPL v3.0 or any later version
 -->
<html>
<head>
<meta charset="UTF-8" />
<?php
if ( !empty( $org ) )
{
    $title = htmlspecialchars( $org . " e-mail user password change" );
}
else
{
    $title = "E-mail user password change";
}

echo "<title>" . $title . "</title>" . PHP_EOL;
?>
<link href="main.css" rel="stylesheet" type="text/css" title="Standard styles" />
</head>

<?php
if ( $_SERVER ['REQUEST_METHOD'] == "GET" )
{
    $raw_username = $logged_in_user;
    if ( isset( $_GET ['u'] ) )
    {
        $u = $_GET ['u'];
        $raw_username = filter_var( $u, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );
    }
}
// Check to see if the form has been submitted
if ( $_SERVER ['REQUEST_METHOD'] == "POST" )
{
    if ( isset( $_POST ['submit'] ) )
    {
        // Raw username, we'll escape it later
        $u = $_POST ['username'];
        $raw_username = filter_var( $u, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );
        // Collect the old and new password fields
        // We'll encode these later after we've done our pre-hashing checks
        $password = $_POST ['password'];
        $npassword = $_POST ['npassword'];
        $rpassword = $_POST ['rpassword'];
        
        // Validations that depend only on the raw formfields and don't require the database
        if ( empty( $raw_username ) || empty( $password ) || empty( $npassword ) || empty( $rpassword ) )
        {
            $msg = "All fields are required.";
        }
        elseif ( $npassword != $rpassword )
        {
            $msg = "Your new passwords do not match.";
        }
        elseif ( ( $npassword == $password ) && !$logged_in_admin )
        {
            $msg = "Your new password cannot match your old password.";
        }
        else
        {
            // Query the database to find which user we're working with
            $username = mysqli_real_escape_string( $link, $raw_username );
            $query = mysqli_query( $link, "SELECT username, password, change_attempts FROM mail_users WHERE username = '$username'" ) or
                             die( mysqli_error( $link ) );
            $numrows = mysqli_num_rows( $query );
            
            // Gather database information
            $tries = $max_tries;
            while ( $cols = mysqli_fetch_array( $query ) )
            {
                $dbusername = $cols ['username'];
                $dbpassword = $cols ['password'];
                $tries = $cols ['change_attempts'];
            }
            mysqli_free_result( $query );
            
            // Validation that requires the database
            if ( $numrows == 0 )
            {
                $msg = "This username does not exist.";
            }
            else
            {
                // Generate new SHA512 salt: 12 random bytes, base64-encoded to produce 16 characters
                $nsalt = "$6$" . base64_encode( mcrypt_create_iv( 12 ) ) . "$";
                
                // Hash the old and new passwords
                // This depends on a Linux-type crypt() implementation
                $hpassword = crypt( $password, $dbpassword );
                $hnpassword = crypt( $npassword, $nsalt );
                
                // Checks that have to be done after hashing passwords
                if ( ( $hpassword != $dbpassword || $tries >= $max_tries ) &&
                                 ( !$logged_in_admin || ( $logged_in_user == $dbusername ) ) )
                {
                    $msg = "The CURRENT password you entered is incorrect.";
                    mysqli_query( $link, "UPDATE mail_users SET change_attempts = change_attempts + 1 where username = '$username'" );
                }
                elseif ( substr( $hnpassword, 0, 3 ) != "$6$" )
                {
                    $msg = "An error occurred when hashing the new password.";
                }
                else
                {
                    $msg = "Your password has been successfully changed.";
                    mysqli_query( $link, "UPDATE mail_users SET password = '$hnpassword', change_attempts = 0 WHERE username = '$username'" ) or
                                     die( mysqli_error( $link ) );
                }
            }
        }
    }
}
?>

<body>

<?php echo "    <h1 class=\"page_title\">".$title."</h1>".PHP_EOL; ?>

    <p>
    
    
    <form method="POST" action="ChangePassword.php">
        <table class="entry">
            <tr>
                <td class="entry_label">Username:</td>
<?php echo "                    <td class=\"entry_value\"><input type=\"text\" name=\"username\" value=\"".htmlspecialchars( $raw_username )."\" size=\"50\" /></td>".PHP_EOL; ?>
                </tr>
            <tr>
                <td class="entry_label">Current Password:</td>
                <td class="entry_value"><input type="password" name="password" value="" size="50" /></td>
            </tr>
            <tr>
                <td class="entry_label">New Password:</td>
                <td class="entry_value"><input type="password" name="npassword" value="" size="50" /></td>
            </tr>
            <tr>
                <td class="entry_label">Repeat New Password:</td>
                <td class="entry_value"><input type="password" name="rpassword" value="" size="50" /></td>
            </tr>
            <tr>
                <td class="buttons"><input type="submit" name="submit" value="Change Password" /></td>
            </tr>
        </table>
    </form>
    </p>

<?php if ( $msg != "" ) echo "    <p class=\"message\">".$msg."</p>".PHP_EOL; ?>

    <p class="footer">
        <a href="admin.php">Return to system administration links</a>
    </p>

</body>
</html>