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
        $title = htmlspecialchars($org . " e-mail user links");
    } else {
        $title = "E-mail user links";
    }
    ?>
    <title><?= $title ?></title>
    <link href="main.css" rel="stylesheet" type="text/css" title="Standard styles"/>
</head>
<body>

<h1 class="page_title"><?= $title ?></h1>

<p>

<table class="index">
    <tr>
        <td class="index"><a href="ChangePassword.php">Change user's e-mail password</a></td>
    </tr>
    <?php if ($logged_in_as_admin) { ?>
        <tr>
            <td class="index"><a href="admin.php">Admin links</a></td>
        </tr>
    <?php } ?>
</table>

</body>
</html>
