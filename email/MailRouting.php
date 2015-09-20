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
    $title = htmlspecialchars( $org." e-mail routing" );
}
else
{
    $title = "E-mail routing";
}

echo "<title>".$title."</title>".PHP_EOL;
?>
<link href="main.css" rel="stylesheet" type="text/css" title="Standard styles" />
</head>

<?php
$filter_username = null;
$filter_domain = null;

// Check for username to filter by
if ( $_SERVER[ 'REQUEST_METHOD' ] == "GET" )
{
    if ( isset( $_GET[ 'u' ] ) )
    {
        $u = $_GET[ 'u' ];
        $filter_username = filter_var( $u, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );
    }
    if ( isset( $_GET[ 'd' ] ) )
    {
        $d = $_GET[ 'd' ];
        $filter_domain = filter_var( $d, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH );
    }
}
// Check to see if the form has been submitted
if ( $_SERVER ['REQUEST_METHOD'] == "POST" )
{
    if ( !$logged_in_admin )
    {
        $msg = "You are not an administrator.";
    }
    elseif ( isset( $_POST ['add'] ) )
    {
        // Raw new user, domain, recipient, we'll escape them later
        $raw_user = $_POST ['user'];
        $raw_domain = $_POST ['domain'];
        $raw_recipient = $_POST ['recipient'];

        // Validate the form fields
        if ( empty( $raw_user ) || empty( $raw_domain ) || empty( $raw_recipient )  )
        {
            $msg = "All fields are required.";
        }
        else
        {
            // Query the database to check for the new user's existence
            $user = mysqli_real_escape_string( $link, $raw_user );
            $domain = mysqli_real_escape_string( $link, $raw_domain );
            $query = mysqli_query( $link, "SELECT * FROM mail_routing WHERE address_user = '$user' AND address_domain = '$domain'" ) or
                             die( mysqli_error( $link ) );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );

            if ( $numrows != 0 )
            {
                $msg = "This mail routing entry already exists.";
            }
            else
            {
                $recipient = mysqli_real_escape_string( $link, $raw_recipient );
                $msg = "The new mail routing entry was successfully added.";
                mysqli_query( $link, "INSERT INTO mail_routing ( address_user, address_domain, recipient ) VALUES ( '$user', '$domain', '$recipient' )" ) or
                                 die( mysqli_error( $link ) );
                }
        }
    }
    elseif ( isset( $_POST ['delete'] ) )
    {
        // Raw new user, domain, we'll escape them later
        $raw_user = $_POST ['user'];
        $raw_domain = $_POST ['domain'];

        // Validate the form fields
        if ( empty( $raw_user ) || empty( $raw_domain ) )
        {
            $msg = "User and domain are required.";
        }
        else
        {
            // Query the database to check for the new user's existence
            $user = mysqli_real_escape_string( $link, $raw_user );
            $domain = mysqli_real_escape_string( $link, $raw_domain );
            $query = mysqli_query( $link, "SELECT * FROM mail_routing WHERE user = '$user' AND domain = '$domain'" ) or
                             die( mysqli_error( $link ) );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );

            if ( $numrows == 0 )
            {
                $msg = "This mail routing entry does not exist.";
            }
            else
            {
                $msg = "The mail routing entry was successfully deleted.";
                mysqli_query( $link, "DELETE FROM mail_routing WHERE user = '$user' AND domain = '$domain'" ) or
                                 die( mysqli_error( $link ) );
            }
        }
    }
    elseif ( isset( $_POST ['update'] ) )
    {
        // Raw new user, domain, recipient, we'll escape them later
        $raw_user = $_POST ['user'];
        $raw_domain = $_POST ['domain'];
        $raw_recipient = $_POST ['recipient'];

        // Validate the form fields
        if ( empty( $raw_user ) || empty( $raw_domain ) || empty( $raw_recipient )  )
        {
            $msg = "All fields are required.";
        }
        else
        {
            // Query the database to check for the new user's existence
            $user = mysqli_real_escape_string( $link, $raw_user );
            $domain = mysqli_real_escape_string( $link, $raw_domain );
            $query = mysqli_query( $link, "SELECT * FROM mail_routing WHERE address_user = '$user' AND address_domain = '$domain'" ) or
                             die( mysqli_error( $link ) );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );

            if ( $numrows == 0 )
            {
                $msg = "This mail routing entry does not exist.";
            }
            else
            {
                $recipient = mysqli_real_escape_string( $link, $raw_recipient );
                $msg = "The mail routing entry was successfully updated.";
                mysqli_query( $link, "UPDATE mail_routing SET recipient = '$recipient' WHERE address_user = '$user' AND address_domain = '$domain'" ) or
                                 die( mysqli_error( $link ) );
            }
        }
    }
}

mysqli_commit( $link ) or die( "Database commit failed." );
?>

<body>
<?php echo "    <h1 class=\"page_title\">".$title."</h1>".PHP_EOL; ?>

    <p>
        <table class="container">
        <tr>

        <td class="container">
        <table class="listing">
            <caption>Mail users</caption>
            <tr>
                <th class="listing">Addressee user</th>
                <th class="listing">Addressee domain</th>
                <th class="listing">Recipient</th>
            </tr>
<?php
    function ttk_output_entries( $q, $r, $d )
    {
        // Output rows of the body of our table of domains
        while ( $cols = mysqli_fetch_array( $q ) )
        {
            $user = $cols[ 'address_user' ];
            $domain = $cols[ 'address_domain' ];
            $recipient = $cols[ 'recipient' ];
            // Only output if no recipient filter or recipient matches filter AND no domain filter or domain matches filter.
            if ( ( !$r || ( $recipient == $r ) ) && ( !$d || ( $domain == $d ) ) )
            {
                echo "            <tr>".PHP_EOL;
                echo "              <td class=\"listing\">".htmlspecialchars( $user )."</td>".PHP_EOL;
                echo "              <td class=\"listing\">".htmlspecialchars( $domain )."</td>".PHP_EOL;
                echo "              <td class=\"listing\">".htmlspecialchars( $recipient )."</td>".PHP_EOL;
                echo "            </tr>".PHP_EOL;
            }
        }
    }

    function cmp_sysentry( $a, $b )
    {
        $o_t = strcmp( $a[1], $b[1] );
        $o_u = strcmp( $a[0], $b[0] );
        return ( $o_t == 0 ) ? $o_u : $o_t;
    }

    function ttk_output_sysentry( $u, $r )
    {
        if ( $u && $r )
        {
            $r_entries = explode( ",", $r );
            $r_list = "";
            $r_class = "listing_sys";
            foreach ( $r_entries as $i => $e )
            {
                $r_entry = trim( $e );
                if ( $r_entry )
                {
                    if ( $i > 0 )
                        $r_list .= "<br>";
                    $r_list .= $r_entry;
                }
                else
                {
                    // Bad entry
                    $r_class = "listing_syserror";
                }
            }
            if ( !$r_list )
            {
                $r_list = "INVALID";
                $r_class = "listing_syserror";
            }
            echo "            <tr><td class=\"listing_sys\">".htmlspecialchars( $u )."</td><td class=\"".$r_class."\">".htmlspecialchars( $r_list )."</td></tr>".PHP_EOL;
        }
    }

    // Scan the domains table in the same order the stored procedure will check them.
    // First non-wildcard entries
    $query = mysqli_query( $link, "SELECT address_user, address_domain, recipient FROM mail_routing WHERE address_user != '*' AND address_domain != '*' ORDER BY address_domain, address_user" ) or
        die( mysqli_error( $link ) );
    ttk_output_entries( $query, $filter_username, $filter_domain );
    mysqli_free_result( $query );
    // Then entries with a non-wildcard user and a wildcard domain
    $query = mysqli_query( $link, "SELECT address_user, address_domain, recipient FROM mail_routing WHERE address_user != '*' AND address_domain = '*' ORDER BY address_domain, address_user" ) or
        die( mysqli_error( $link ) );
    ttk_output_entries( $query, $filter_username, '*' );
    mysqli_free_result( $query );
    // Then entries with a wildcard user and a non=wildcard domain
    $query = mysqli_query( $link, "SELECT address_user, address_domain, recipient FROM mail_routing WHERE address_user = '*' AND address_domain != '*' ORDER BY address_domain, address_user" ) or
        die( mysqli_error( $link ) );
    ttk_output_entries( $query, $filter_username, $filter_domain );
    mysqli_free_result( $query );
    // And finally the all-wildcards entry if any
    $query = mysqli_query( $link, "SELECT address_user, address_domain, recipient FROM mail_routing WHERE address_user = '*' AND address_domain = '*' ORDER BY address_domain, address_user" ) or
        die( mysqli_error( $link ) );
    ttk_output_entries( $query, $filter_username, '*' );
    mysqli_free_result( $query );
?>
        </table>
        </td>

        <td class="container">
        <table class="listing">
            <caption>System aliases</caption>
            <tr>
                <th class="listing">System user</th>
                <th class="listing">Target user</th>
            </tr>
<?php
    // Now report the system aliases table
    $alias_list = array();
    $aliases_file = new SplFileObject( "/etc/aliases", "r" );
    $aliases_file->setFlags( SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY );
    foreach ( $aliases_file as $rawline )
    {
        $line = trim( $rawline );
        if ( strlen( $line ) == 0 || $line[0] == '#' )
            continue;
        $user_entry = explode( ":", $line, 2);
        if ( count( $user_entry ) == 2 )
        {
            $alias_user = trim( $user_entry[0] );
            $alias_target = trim( $user_entry[1] );
            $item = array( $alias_user, $alias_target );
            $alias_list[] = $item;
        }
    }
    $aliases_file = null;
    uasort( $alias_list, "cmp_sysentry" );
    foreach ( $alias_list as $i => $a )
    {
        $alias_user = $a[0];
        $alias_target = $a[1];
        ttk_output_sysentry( $alias_user, $alias_target );
    }
?>
        </table>
        </td>

        </tr>
        </table>
    </p>

    <p>
        <form method="POST" action="MailRouting.php">
            <table class="entry">
                <tr>
                    <td class="entry_label">Addressee user:</td>
                    <td class="entry_value"><input type="text" name="user" value="" size="50" /></td>
                </tr>
                <tr>
                    <td class="entry_label">Addressee domain:</td>
                    <td class="entry_value"><input type="text" name="domain" value="" size="50" /></td>
                </tr>
                <tr>
                    <td class="entry_label">Recipient:</td>
                    <td class="entry_value"><input type="text" name="recipient" value="" size="50" /></td>
                </tr>
                <tr>
                    <td class="buttons">
                        <input type="submit" name="add" value="Add" />
                        <input type="submit" name="delete" value="Delete" />
                        <input type="submit" name="update" value="Update" />
                    </td>
                </tr>
            </table>
        </form>
    </p>

<?php if ( $msg != "" ) echo "    <p class=\"message\">".$msg."</p>".PHP_EOL; ?>

    <p class="footer"><a href="MailRouting.php">Display unfiltered mail routing table</a></p>
    <p class="footer"><a href="admin.php">Return to system administration links</a></p>

</body>
</html>
