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
    if (!empty($org)) {
        $title = htmlspecialchars($org . " password view of accounts");
    } else {
        $title = "Password view of accounts";
    }
    ?>
    <title><?= $title ?></title>
    <link href="main.css" rel="stylesheet" type="text/css" title="Standard styles"/>
</head>

<?php
if (!$logged_in_as_admin) {
    die("You are not logged in as an administrator.");
}
?>

<body>

<h1 class="page_title"><?= $title ?></h1>

<p>

<table class="listing">
    <tr>
        <th class="listing">Username</th>
        <th class="listing">Type</th>
        <th class="listing">UID</th>
        <th class="listing">GID</th>
        <th class="listing">Homedir</th>
        <th class="listing">Transport</th>
    </tr>
    <?php
    $link = Config::instance()->getDatabaseLink();

    // Scan the domains table in sorted order
    $query =
        mysqli_query($link, "SELECT username, acct_type, uid, gid, home, transport FROM v_passwd ORDER BY username")
    or die(mysqli_error($link));

    // Output the body of our table of domains
    while ($cols = mysqli_fetch_array($query)) {
        $username = htmlspecialchars($cols['username']);
        $account_type = $cols['acct_type'];
        $uid = $cols['uid'];
        $gid = $cols['gid'];
        $home_directory = htmlspecialchars($cols['home']);
        $transport = htmlspecialchars($cols['transport']);
        ?>
        <tr>
            <td class=\"listing\"><?= $username ?></td>
            <td class=\"listing\"><?= $account_type ?></td>
            <td class=\"listing\"><?= $uid ?></td>
            <td class=\"listing\"><?= $gid ?></td>
            <td class=\"listing\"><?= $home_directory ?></td>
            <td class=\"listing\"><?= $transport ?></td>
        </tr>
        <?php
    }
    mysqli_free_result($query);
    ?>
</table>

<p class="footer"><a href="admin.php">Return to system administration links</a></p>

</body>
</html>
