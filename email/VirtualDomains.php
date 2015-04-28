<?php require 'ini.php' ?>
<!DOCTYPE html>
<!--
    Copyright 2015 Todd Knarr
    Licensed under the terms of the GPL v3.0
 -->
<html>
<head>
<meta charset="UTF-8" />
<?php
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
// Check to see if the form has been submitted
if ( $_SERVER ['REQUEST_METHOD'] == "POST" )
{
    if ( !$logged_in_admin )
    {
        $msg = "You are not an administrator.";
    }
    elseif ( $_POST[ 'add' ] )
    {
        // Raw new username, we'll escape it later
        $raw_domain = $_POST[ 'domain' ];
        $raw_default_user = $_POST[ 'defaultuser' ];
    
        // Validate the form fields
        if ( empty( $raw_domain ) )
        {
            $msg = "Domain not specified.";
        }
        else
        {
            // Query the database to check for the new domain's existence
            $domain = mysqli_real_escape_string( $link, $raw_domain );
            $query = mysqli_query( $link, "SELECT * FROM virtual_domains WHERE name = '$domain'" ) or die( mysqli_error() );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );
    
            if ( $numrows != 0 )
            {
                $msg = "This domain already exists.";
            }
            else
            {
                $msg = "The new domain was successfully added.";
                mysqli_query( $link, "INSERT INTO virtual_domains ( name ) VALUES ( '$domain' )" ) or
                     die( mysqli_error() );

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
    elseif ( $_POST[ 'delete' ] )
    {
        // Raw new username, we'll escape it later
        $raw_domain = $_POST[ 'domain' ];
    
        // Validate the form fields
        if ( empty( $raw_domain ) )
        {
            $msg = "Domain not specified.";
        }
        else
        {
            // Query the database to check for the domain's existence
            $domain = mysqli_real_escape_string( $link, $raw_domain );
            $query = mysqli_query( $link, "SELECT * FROM virtual_domains WHERE name = '$domain'" ) or die( mysqli_error() );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );
    
            if ( $numrows == 0 )
            {
                $msg = "This domain does not exist.";
            }
            else
            {
                $msg = "The new domain was successfully deleted.";
                mysqli_query( $link, "DELETE FROM virtual_domains WHERE name = '$domain'" ) or
                     die( mysqli_error() );
    
                // Delete all mail routing entries for the domain, if any
                mysqli_query( $link, "DELETE FROM virtual_aliases WHERE address_domain = '$domain'" );
            }
        }
    }
    elseif ( $_POST[ 'update' ] )
    {
        // Raw new username, we'll escape it later
        $raw_domain = $_POST[ 'domain' ];
        $raw_default_user = $_POST[ 'defaultuser' ];
        
        // Validate the form fields
        if ( empty( $raw_domain ) )
        {
            $msg = "Domain not specified.";
        }
        elseif ( empty( $raw_default_user ) )
        {
            $msg = "If you want to remove the default user, use the dash ('-').";
        }
        else
        {
            // Query the database to check for the domain's existence
            $domain = mysqli_real_escape_string( $link, $raw_domain );
            if ( $raw_default_user == '-' )
                $default_user = '-';
            else
                $default_user = mysqli_real_escape_string( $link, $raw_default_user );
            $query = mysqli_query( $link, "SELECT * FROM virtual_domains WHERE name = '$domain'" ) or die( mysqli_error() );
            $numrows = mysqli_num_rows( $query );
            mysqli_free_result( $query );
    
            if ( $numrows == 0 )
            {
                $msg = "This domain does not exist.";
            }
            else
            {
                $msg = "The domain default user was successfully updated.";
                mysqli_query( $link, "UPDATE virtual_domains SET default_user = '$default_user' WHERE name = '$domain'" ) or
                     die( mysqli_error() );

                // Delete the catch-all routing entry for the domain if removing the default user
                if ( $default_user == '-' )
                    mysqli_query( $link, "DELETE FROM virtual_aliases WHERE address_user = '*' AND address_domain = '$domain'" );
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
            <tr><th class="listing">Domain name</th><th class="listing">Default user</th></tr>
<?php
    $wildcard_user = '-';
    // Find the global wildcard recipient if any
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
            // Check default routing entry for the domain for a default recipient
            $query2 = mysqli_query( $link, "SELECT recipient FROM virtual_aliases WHERE address_user = '*' AND address_domain = '$domain'" ) or
                die( mysqli_error() );
            $default_user = $wildcard_user;
            if ( $cols2 = mysqli_fetch_array( $query2 ) )
            {
                $default_user = $cols2[ 'recipient' ];
            }
            mysqli_free_result( $query2 );
            // Special mark wildcard recipients, unless the wildcard is "nobody" (no domain or global wildcard recipient)
            if ( $default_user == $wildcard_user && $wildcard_user != '-' )
                $duname = "<div class=\"wildcard\">".htmlspecialchars( $default_user )."</div>";
            else
                $duname = htmlspecialchars( $default_user );
            // Link to routing form filtered by domain for domain name
            $dlink = "<a href=\"MailRouting.php?d=".urlencode( $domain )."\">".htmlspecialchars( $domain )."</a>";
            echo "            <tr><td class=\"listing\">".$dlink."</td><td class=\"listing\">".$duname."</td></tr>".PHP_EOL;
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
                    <td class="buttons">
                        <input type="submit" name="add" value="Add Domain" />
                        <input type="submit" name="delete" value="Delete Domain" /><br>
                        <input type="submit" name="update" value="Update Domain" />
                        </td>
                </tr>
            </table>
        </form>
    </p>

<?php if ( $msg != "" ) echo "    <p class=\"message\">".$msg."</p>".PHP_EOL; ?>

    <p class="footer"><a href="admin.php">Return to system administration links</a></p>

</body>
</html>