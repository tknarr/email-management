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
        $title = htmlspecialchars($org . " e-mail routing");
    } else {
        $title = "E-mail routing";
    }
    ?>
    <title><?= $title ?></title>
    <link href="main.css" rel="stylesheet" type="text/css" title="Standard styles"/>
</head>

<?php
if (!$logged_in_as_admin) {
    die("You are not logged in as an administrator.");
}

/**
 * @param SystemAlias $a
 * @param SystemAlias $b
 *
 * @return int
 */
function compare_system_alias($a, $b)
{
    if ($a->getAlias() == $b->getAlias()) {
        return 0;
    }
    return $a->getAlias() < $b->getAlias() ? -1 : 1;
}

$msg = '';
$filter_address_user = null;
$filter_address_domain = null;

// Check for username to filter by
if ($_SERVER['REQUEST_METHOD'] == "GET") {
    if (isset($_GET['u'])) {
        $u = $_GET['u'];
        $filter_address_user = filter_var($u, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }
    if (isset($_GET['d'])) {
        $d = $_GET['d'];
        $filter_address_domain = filter_var($d, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }
}
// Check to see if the form has been submitted
if ($_SERVER ['REQUEST_METHOD'] == "POST") {
    if (isset($_POST ['add'])) {
        $address_user = $_POST ['user'];
        $address_domain = $_POST ['domain'];
        $recipient = $_POST ['recipient'];

        if (empty($address_user) || empty($address_domain) || empty($recipient)) {
            $msg = "All fields are required.";
        } else {
            $routing_entry = new MailRoute($address_user, $address_domain, $recipient);
            if ($routing_entry->exists()) {
                $msg = "This mail routing entry already exists.";
            } else {
                MailRoute::create($address_user, $address_domain, $recipient, $msg);
            }
        }
    } else if (isset($_POST ['update'])) {
        $address_user = $_POST ['user'];
        $address_domain = $_POST ['domain'];
        $recipient = $_POST ['recipient'];

        if (empty($address_user) || empty($address_domain) || empty($recipient)) {
            $msg = "All fields are required.";
        } else {
            $routing_entry = new MailRoute($address_user, $address_domain, $recipient);
            if (!$routing_entry->exists()) {
                $msg = "This mail routing entry does not exist.";
            } else {
                $routing_entry->update($recipient, $msg);
            }
        }
    } else if (isset($_POST ['delete'])) {
        $address_user = $_POST ['user'];
        $address_domain = $_POST ['domain'];

        if (empty($address_user) || empty($address_domain)) {
            $msg = "User and domain are required.";
        } else {
            $routing_entry = new MailRoute($address_user, $address_domain, '');
            if (!$routing_entry->exists()) {
                $msg = "This mail routing entry does not exist.";
            } else {
                $routing_entry->delete($msg);
            }
        }
    }
}
?>

<body>

<h1 class="page_title"><?= $title ?></h1>

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
                foreach (MailRoute::getMailRoutingList() as $routing_entry) {
                    // Only output if no recipient filter or recipient matches filter AND no domain filter or domain matches filter.
                    if ((!$filter_address_user || $routing_entry->getAddressUser() == $filter_address_user)
                        && (!$filter_address_domain || $routing_entry->getAddressDomain() == $filter_address_domain)) {
                        ?>
                        <tr>
                            <td class="listing"><?= htmlspecialchars($routing_entry->getAddressUser()) ?></td>
                            <td class="listing"><?= htmlspecialchars($routing_entry->getAddressDomain()) ?></td>
                            <td class="listing"><?= htmlspecialchars($routing_entry->getRecipient()) ?></td>
                        </tr>
                        <?php
                    }
                }
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
                $alias_list = SystemAlias::getSystemAliases();
                uasort($alias_list, "compare_system_alias");
                foreach ($alias_list as $i => $alias) {
                    $alias_username = $alias->getAlias();
                    $recipient_class = 'listing_sys';
                    $recipient_list_html = '';
                    // Convert the list of recipients into HTML with each recipient on it's own line
                    if (!empty($alias->getRecipients())) {
                        foreach ($alias->getRecipients() as $j => $recipient) {
                            if ($j > 0) {
                                $recipient_list_html .= '<br>';
                                $recipient_list_html .= $recipient;
                            }
                        }
                    } else {
                        $recipient_class = 'listing_syserror';
                        $recipient_list_html = 'INVALID';
                    }
                    ?>
                    <tr>
                        <td class="listing_sys"><?= htmlspecialchars($alias_username) ?></td>
                        <td class="<?= $recipient_class ?>"><?= htmlspecialchars($recipient_list_html) ?></td>
                    </tr>
                    <?php
                }
                ?>
            </table>
        </td>

    </tr>
</table>

<p>

<form method="POST" action="MailRouting.php">
    <table class="entry">
        <tr>
            <td class="entry_label">Addressee user:</td>
            <td class="entry_value"><input type="text" pattern="[A-Za-z0-9_.*]+" name="user" title="Addressee user" value="" size="50"/></td>
        </tr>
        <tr>
            <td class="entry_label">Addressee domain:</td>
            <td class="entry_value"><input type="text" pattern="[A-Za-z0-9.*-]+" name="domain" title="Address domain" value="" size="50"/></td>
        </tr>
        <tr>
            <td class="entry_label">Recipient:</td>
            <td class="entry_value"><input type="text" pattern="[A-Za-z0-9_.]+" name="recipient" title="Recipient" value="" size="50"/></td>
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

<p class="footer"><a href="MailRouting.php">Display unfiltered mail routing table</a></p>
<p class="footer"><a href="admin.php">Return to system administration links</a></p>

</body>
</html>
