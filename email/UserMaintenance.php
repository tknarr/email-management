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
        $title = htmlspecialchars($org . " e-mail user maintenance");
    } else {
        $title = "E-mail user maintenance";
    }
    ?>
    <title><?= $title ?></title>
    <link href="main.css" rel="stylesheet" type="text/css" title="Standard styles"/>
</head>

<?php
if (!$logged_in_as_admin) {
    die("You are not logged in as an administrator.");
}

$msg = '';
$maildir_msg = '';

// Request for the list, or a reset operation
if ($_SERVER ['REQUEST_METHOD'] == 'GET') {
    $username = '';
    if (isset($_GET ['reset'])) {
        $username = filter_var($_GET ['reset'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        if (!empty($username)) {
            $user = new User($username);
            $user->resetPasswordChangeAttempts();
        }
    }
}

// Form submission
if ($_SERVER ['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $across_all_domains = false;
        $username =
            filter_var($_POST['username'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        if (isset($_POST['alldomains'])) {
            $checkbox_value = filter_var($_POST['alldomains'], FILTER_SANITIZE_STRING,
                FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
            if ($checkbox_value == "yes") {
                $across_all_domains = true;
            }
        }
        $virtual_user_checkbox =
            filter_var($_POST['virtualuser'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        // Collect the old and new password fields
        $new_password =
            filter_var($_POST['npassword'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $confirm_password =
            filter_var($_POST['rpassword'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

        if (!empty($username)) {
            $username = preg_replace('/[^A-Za-z0-9_\.]/', '', $username);
        }

        if (empty($username) || empty($new_password) || empty($confirm_password)) {
            $msg = "All fields are required.";
        } else if ($new_password != $confirm_password) {
            $msg = "The new passwords do not match.";
        } else {
            $new_user = new User($username);
            if ($new_user->exists()) {
                $msg = "This username already exists.";
            } else {
                $account_type = 'S';
                if ($username == 'root') {
                    $account_type = 'R';
                } else if ($virtual_user_checkbox == 'yes') {
                    $account_type = 'V';
                }

                User::create($username, $new_password, $account_type, $across_all_domains, $msg, $maildir_msg);
            }
        }
    } else if (isset($_POST['delete'])) {
        $username = $_POST['username'];

        if (empty($username)) {
            $msg = "Username is required.";
        } else {
            $deleted_user = new User($username);
            $deleted_user->delete($msg);
        }
    }
}
?>

<body>

<h1 class="page_title"><?= $title ?></h1>

<p>

<table class="listing">
    <tr>
        <th class="listing">Username</th>
        <th class="listing">Type</th>
        <th class="listing">Change<br>attempts</th>
        <th class="listing_extra">&nbsp;</th>
    </tr>
    <?php
    // Output the body of our table of domains
    $users = User::getUsernameInfo();
    foreach ($users as $current_user) {
        $current_username = $current_user['username'];
        $account_type = $current_user['abbreviation'];
        $change_attempts = $current_user['change_attempts'];
        if ($current_username != "") {
            $url_username = urlencode($current_username);
            $html_username = htmlspecialchars($current_username);
            $reset_link = "<a href=\"MailRouting.php?u=$url_username\">$html_username</a>";
            if ($logged_in_as_admin) {
                // TODO need reset-auth-attempts button
                $password_button =
                    "<button type=\"button\" onclick=\"window.location.assign(&quot;ChangePassword.php?reset=$url_username&quot;)\">Chg Pwd</button>";
                $change_attempts_button =
                    "<button type=\"button\" onclick=\"window.location.assign(&quot;UserMaintenance.php?reset=$url_username&quot;)\">Reset CAs</button>";
            } else {
                $password_button = "";
                $change_attempts_button = "";
            }
            ?>
            <tr>
                <td class="listing"><?= $reset_link ?></td>
                <td class="listing"><?= $account_type ?></td>
                <td class="listing_right"><?= $change_attempts ?></td>
                <td class="listing_extra"><?= $change_attempts_button ?><?= $password_button ?></td>
                <!-- TODO need reset-auth-attempts button -->
            </tr>
            <?php
        }
    }
    ?>
</table>

<p>

<form method="POST" action="UserMaintenance.php">
    <table class="entry">
        <tr>
            <td class="entry_label">Username:</td>
            <td class="entry_value">
                <input type="text" pattern="[A-Za-z0-9_.]+" name="username" title="Username" value="" size="50"/>
            </td>
        </tr>
        <tr>
            <td class="entry_label">Password:</td>
            <td class="entry_value">
                <input type="password" name="npassword" title="Password" value="" size="50"/>
            </td>
        </tr>
        <tr>
            <td class="entry_label">Confirm Password:</td>
            <td class="entry_value">
                <input type="password" name="rpassword" title="Confirm password" value="" size="50"/>
            </td>
        </tr>
        <tr>
            <td class="entry_label">All domains?</td>
            <td class="entry_value">
                <input type="checkbox" name="alldomains" title="All domains" value="yes"/>
            </td>
        </tr>
        <tr>
            <td class="entry_label">Virtual user?</td>
            <?php
                $checked = Config::instance()->defaultVirtualUsers() ? 'checked' : '';
            ?>
            <td class="entry_value">
                <input type="checkbox" name="virtualuser" title="Virtual user" value="yes" <?= $checked ?>/>
            </td>
        </tr>
        <tr>
            <td class="buttons">
                <input type="submit" name="add" value="Add"/>
                <input type="submit" name="delete" value="Delete"/>
            </td>
        </tr>
    </table>
</form>

<?php if (!empty($msg)) { ?>
    <p class="message"><?= htmlspecialchars($msg) ?></p>
<?php } ?>
<?php if (!empty($maildir_msg)) { ?>
    <p class="message"><?= htmlspecialchars($maildir_msg) ?></p>
<?php } ?>

<p class="footer"><a href="admin.php">Return to system administration links</a></p>

</body>
</html>
