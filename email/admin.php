<!DOCTYPE html>
<!--
  ~ Copyright (C) 2017 Todd Knarr <tknarr@silverglass.org>
  -->
<?php require 'authenticate.php'; ?>

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
    ?>
    <title><?= $title ?></title>
    <link href="main.css" rel="stylesheet" type="text/css" title="Standard styles"/>
</head>
<body>

<h1 class="page_title"><?= $title ?></h1>

<p>

<table class="index">
    <?php if ($logged_in_as_admin) { ?>
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
    <?php } ?>
    <tr>
        <td class="index"><a href="ChangePassword.php">Password change form</a></td>
    </tr>
</table>

<?php if ( $logged_in_as_admin ) { ?>

    <p>

    <table class="container">
        <tr>
            <th class="container">Admin users:</th>
        </tr>
        <?php foreach ( Config::instance()->getAdminUsers() as $au ) { ?>
            <tr><td class="container"><?= htmlspecialchars( $au ) ?></td></tr>
        <?php } ?>
    </table>

<?php } ?>

<p class="footer"><a href="index.php">Return to home page</a></p>

</body>
</html>
