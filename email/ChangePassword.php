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
        $title = htmlspecialchars($org . " e-mail user password change");
    } else {
        $title = "E-mail user password change";
    }
    ?>
    <title><?= $title ?></title>
    <link href="main.css" rel="stylesheet" type="text/css" title="Standard styles"/>
</head>

<?php
$msg = '';
$username = '';

if ($_SERVER ['REQUEST_METHOD'] == 'GET') {
    $username = $logged_in_user->getUsername();
    if (isset($_GET ['u'])) {
        $u = $_GET ['u'];
        $username = filter_var($u, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }
}
// Check to see if the form has been submitted
if ($_SERVER ['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST ['submit'])) {
        $u = $_POST ['username'];
        $username = filter_var($u, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        // Collect the old and new password fields
        // We'll encode these later after we've done our pre-hashing checks
        $password = $_POST ['password'];
        $new_password = $_POST ['npassword'];
        $confirm_password = $_POST ['rpassword'];

        if (empty($username) || empty($password) || empty($new_password) || empty($confirm_password)) {
            $msg = "All fields are required.";
        } else if ($new_password != $confirm_password) {
            $msg = "Your new passwords do not match.";
        } else if (($new_password == $password) && !$logged_in_as_admin) {
            $msg = "Your new password cannot match your old password.";
        } else if ($username != $logged_in_user->getUsername() && !$logged_in_as_admin) {
            $msg = "You are not allowed to change someone else's password.";
        } else {
            $user = new User($username);
            $user->updatePassword($password, $new_password, $msg);
        }
    }
}
?>

<body>

<h1 class="page_title"><?= $title ?></h1>

<p>

<form method="POST" action="ChangePassword.php">
    <table class="entry">
        <tr>
            <td class="entry_label">Username:</td>
            <td class="entry_value">
                <?php if ($logged_in_as_admin) { ?>
                    <input type="text" pattern="[A-Za-z0-9_.]+" name="username" value="<?= htmlspecialchars($username) ?>"
                           size="50" title="Username"/>
                <?php } else { ?>
                    <input type="hidden" name="username"
                           value="<?= $logged_in_user->getUsername() ?>"/><?= $logged_in_user->getUsername() ?>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td class="entry_label">Current Password:</td>
            <td class="entry_value"><input type="password" name="password" value="" size="50" title="Old password"/>
            </td>
        </tr>
        <tr>
            <td class="entry_label">New Password:</td>
            <td class="entry_value"><input type="password" name="npassword" value="" size="50" title="New password"/>
            </td>
        </tr>
        <tr>
            <td class="entry_label">Repeat New Password:</td>
            <td class="entry_value"><input type="password" name="rpassword" value="" size="50"
                                           title="Confirm password"/></td>
        </tr>
        <tr>
            <td class="buttons"><input type="submit" name="submit" value="Change Password"/></td>
        </tr>
    </table>
</form>

<?php if (!empty($msg)) { ?>
    <p class="message"><?= htmlspecialchars($msg) ?></p>
<?php } ?>

<p class="footer"><a href="admin.php">Return to system administration links</a></p>

</body>
</html>
