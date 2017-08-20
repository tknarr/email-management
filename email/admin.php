<?php require 'ini.php'; ?>
<!DOCTYPE html>
<!--
    Copyright 2015 Todd Knarr
    Licensed under the terms of the GPL v3.0 or any later version
 -->
<html>
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    if ( !empty( $org ) ) {
        $title = htmlspecialchars( $org . " e-mail system administration" );
    }
    else {
        $title = "E-mail system administration";
    }

    echo "<title>" . $title . "</title>" . PHP_EOL;
    ?>
    <link href="main.css" rel="stylesheet" type="text/css" title="Standard styles"/>
</head>
<body>

<?php echo "    <h1 class=\"page_title\">" . $title . "</h1>" . PHP_EOL; ?>

<p>
<table class="index">
    <tr>
        <td class="index"><a href="UserMaintenance.php">User account maintenance</a></td>
    </tr>
    <tr>
        <td class="index"><a href="PasswdView.php">Passwd view of accounts</a></td>
    </tr>
    <tr>
        <td class="index"><a href="MailRouting.php">Mail routing entry management</a></td>
    </tr>
    <tr>
        <td class="index"><a href="HostedDomains.php">Hosted domain management</a></td>
    </tr>
    <tr>
        <td class="index"><a href="ChangePassword.php">Password change form</a></td>
    </tr>
</table>
</p>

<?php if ( $logged_in_admin ) { ?>
    <p>
    <table class="container">
        <tr>
            <th class="container">Admin users:</th>
        </tr>
        <?php
        foreach ( $admin_users as $au ) {
            echo "            <tr><td class=\"container\">" . htmlspecialchars( $au ) . "</td></tr>" . PHP_EOL;
        }
        ?>
    </table>
    </p>

<?php } ?>
<p class="footer"><a href="index.php">Return to home page</a></p>

</body>
</html>
