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
        $title = htmlspecialchars($org . " e-mail hosted domains");
    } else {
        $title = "E-mail hosted domains";
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

// Check to see if the form has been submitted
if ($_SERVER ['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        // Raw new username, we'll escape it later
        $domain_name = $_POST['domain'];
        $default_recipient = $_POST['defaultuser'];

        // Validate the form fields
        if (empty($domain_name)) {
            $msg = "Domain not specified.";
        } else {
            $domain = new Domain($domain_name);

            if ($domain->exists()) {
                $msg = "This domain already exists.";
            } else {
                Domain::create($domain_name, $default_recipient, $msg);
            }
        }
    } else if (isset($_POST['update'])) {
        $domain_name = $_POST['domain'];
        $default_recipient = $_POST['defaultuser'];

        if (empty($domain_name)) {
            $msg = "Domain name is required.";
        } else {
            $domain = new Domain($domain_name);
            if (!$domain->exists()) {
                $msg = "This domain does not exist.";
            } else {
                $domain->update($default_recipient, $msg);
            }
        }
    } else if (isset($_POST['delete'])) {
        $domain_name = $_POST['domain'];

        if (empty($domain_name)) {
            $msg = "Domain name is required.";
        } else {
            $domain = new Domain($domain_name);
            if (!$domain->exists()) {
                $msg = "This domain does not exist.";
            } else {
                $domain->delete($msg);
            }
        }
    }
}

mysqli_commit($link) or die("Database commit failed.");
?>

<body>

<h1 class="page_title"><?= $title ?></h1>

<p>

<table class="listing">
    <tr>
        <th class="listing">Domain name</th>
        <th class="listing">Default user</th>
    </tr>
    <?php
    $wildcard_user = MailRoute::getWildcardRecipient();
    // Output the body of our table of domains
    $domains = Domain::getDomains();
    foreach ($domains as $domain) {
        $default_recipient = $domain->getDefaultRecipient();
        // Special mark wildcard recipients, unless the wildcard is "nobody" (no domain or global wildcard recipient)
        if (!is_null($default_recipient)) {
            $default_recipient = htmlspecialchars($default_recipient);
        } else if (!is_null($wildcard_user)) {
            $default_recipient = "<span class=\"wildcard\">" . htmlspecialchars($wildcard_user) . "</span>";
        } else {
            $default_recipient = '-';
        }
        // Link to routing form filtered by domain for domain name
        $mail_route_link =
            "<a href=\"MailRouting.php?d=" . urlencode($domain) . "\">" . htmlspecialchars($domain) . "</a>";
        ?>
        <tr>
            <td class="listing"><?= $mail_route_link ?></td>
            <td class="listing"><?= $default_recipient ?></td>
        </tr>
        <?php
    }
    ?>
</table>

<p>

<form method="POST" action="HostedDomains.php">
    <table class="entry">
        <tr>
            <td class="entry_label">Domain:</td>
            <td class="entry_value">
                <input type="text" pattern="[A-Za-z0-9.-]+" name="domain" title="Domain" value="" size="50"/>
            </td>
        </tr>
        <tr>
            <td class="entry_label">Default user:</td>
            <td class="entry_value">
                <input type="text" pattern="[A-Za-z0-9_.]*" name="defaultuser" title="Default user" value="" size="50"/>
            </td>
        </tr>
        <tr>
            <td class="buttons">
                <input type="submit" name="add" value="Add"/>
                <input type="submit" name="delete" value="Delete"/>
                <input type="submit" name="update" value="Update"/>
            </td>
        </tr>
    </table>
</form>

<?php if (!empty($msg)) { ?>
    <p class="message"><?= htmlspecialchars($msg) ?></p>
<?php } ?>

<p class="footer"><a href="admin.php">Return to system administration links</a></p>

</body>
</html>
