<?php require 'ini.php'; ?>
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
    $title = htmlspecialchars( $org." password view of accounts" );
}
else 
{
    $title = "Password view of accounts";
}

echo "<title>".$title."</title>".PHP_EOL;
?>
<link href="main.css" rel="stylesheet" type="text/css" title="Standard styles" />
</head>

<body>
<?php echo "    <h1 class=\"page_title\">".$title."</h1>".PHP_EOL; ?>

    <p>
        <table class="listing">
            <tr><th class="listing">Username</th><th class="listing">Type</th><th class="listing">UID</th><th class="listing">GID</th><th class="listing">Homedir</th></tr>
<?php
    // Scan the domains table in sorted order
    $query = mysqli_query( $link, "SELECT username, acct_type, uid, gid, home FROM v_passwd ORDER BY username" ) or
        die( mysqli_error( $link ) );

    // Output the body of our table of domains
    while ( $cols = mysqli_fetch_array( $query ) )
    {
        $username = htmlspecialchars( $cols[ 'username' ] );
        $acct_type = $cols[ 'acct_type' ];
        $uid = $cols[ 'uid' ];
        $gid = $cols[ 'gid' ];
        $homedir = htmlspecialchars( $cols[ 'home' ] );

        if ( $username != "" )
        {
            echo "            <tr>".PHP_EOL;
            echo "              <td class=\"listing\">".$username."</td>".PHP_EOL;
            echo "              <td class=\"listing\">".$acct_type."</td>".PHP_EOL;
            echo "              <td class=\"listing\">".$uid."</td>".PHP_EOL;
            echo "              <td class=\"listing\">".$gid."</td>".PHP_EOL;
            echo "              <td class=\"listing\">".$homedir."</td>".PHP_EOL;
            echo "            </tr>".PHP_EOL;
        }
    }
    mysqli_free_result( $query );
?>
        </table>
    </p>

<?php if ( $msg != "" ) echo "    <p class=\"message\">".$msg."</p>".PHP_EOL; ?>

    <p class="footer"><a href="admin.php">Return to system administration links</a></p>

</body>
</html>