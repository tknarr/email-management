<?php
/**
 * Copyright (C) 2017 Todd Knarr <tknarr@silverglass.org>
 */

/**
 * Class MailRoute
 *
 * Represents a mail routing entry
 */
class MailRoute
{
    /** @var string */
    private $addressUser;
    /** @var string */
    private $addressDomain;
    /** @var string */
    private $recipient;

    /**
     * MailRoute constructor.
     *
     * @param string $address_user
     * @param string $address_domain
     * @param string $recipient
     */
    public function __construct($address_user, $address_domain, $recipient)
    {
        $this->addressUser = $address_user;
        $this->addressDomain = $address_domain;
        $this->recipient = $recipient;
    }

    /**
     * @return string
     */
    public function getAddressUser()
    {
        return $this->addressUser;
    }

    /**
     * @return string
     */
    public function getAddressDomain()
    {
        return $this->addressDomain;
    }

    /**
     * @return string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        $link = Config::instance()->getDatabaseLink();
        $address_user_esc = mysqli_real_escape_string($link, $this->addressUser);
        $address_domain_esc = mysqli_real_escape_string($link, $this->addressDomain);
        $query = mysqli_query($link,
            "SELECT * FROM mail_routing WHERE address_user = '$address_user_esc' AND address_domain = '$address_domain_esc'")
        or die(mysqli_error($link));
        $numrows = mysqli_num_rows($query);
        mysqli_free_result($query);

        return $numrows > 0;
    }

    /**
     * @param string $recipient
     * @param string $result_message
     *
     * @return bool
     */
    public function update($recipient, &$result_message)
    {
        if (empty($recipient)) {
            $result_message = "Mail routing entry must have a recipient.";
            return false;
        }

        $link = Config::instance()->getDatabaseLink();
        $address_user_esc = mysqli_real_escape_string($link, $this->addressUser);
        $address_domain_esc = mysqli_real_escape_string($link, $this->addressDomain);
        $recipient_esc = mysqli_real_escape_string($link, $recipient);

        mysqli_query($link,
            "UPDATE mail_routing SET recipient = '$recipient_esc' WHERE address_user = '$address_user_esc' AND address_domain = '$address_domain_esc'")
        or die(mysqli_error($link));
        $result_message = "Mail routing entry updated successfully.";

        return true;
    }

    /**
     * @param string $result_message
     *
     * @return bool
     */
    public function delete(&$result_message)
    {
        $link = Config::instance()->getDatabaseLink();
        $address_user_esc = mysqli_real_escape_string($link, $this->addressUser);
        $address_domain_esc = mysqli_real_escape_string($link, $this->addressDomain);

        mysqli_query($link,
            "DELETE FROM mail_routing WHERE address_user = '$address_user_esc' AND address_domain = '$address_domain_esc'")
        or die(mysqli_error($link));
        $result_message = "Mail routing entry successfully deleted.";

        return true;
    }

    /**
     * Get mail routing list in an order matching how entries will be checked during actual mail routing
     *
     * @return MailRoute[]
     */
    public static function getMailRoutingList()
    {
        $result = [];
        $link = Config::instance()->getDatabaseLink();

        // Get explicit entries first.
        $query = mysqli_query($link,
            "SELECT address_user, address_domain, recipient FROM mail_routing WHERE address_user != '*' AND address_domain != '*' ORDER BY address_domain, address_user")
        or die(mysqli_error($link));
        while ($cols = mysqli_fetch_array($query)) {
            $user = $cols['address_user'];
            $domain = $cols['address_domain'];
            $recipient = $cols['recipient'];

            $result[] = new MailRoute($user, $domain, $recipient);
        }
        mysqli_free_result($query);

        // Next, entries with a non-wildcard user and a wildcard domain.
        $query = mysqli_query($link,
            "SELECT address_user, address_domain, recipient FROM mail_routing WHERE address_user != '*' AND address_domain = '*' ORDER BY address_domain, address_user")
        or die(mysqli_error($link));
        while ($cols = mysqli_fetch_array($query)) {
            $user = $cols['address_user'];
            $domain = $cols['address_domain'];
            $recipient = $cols['recipient'];

            $result[] = new MailRoute($user, $domain, $recipient);
        }
        mysqli_free_result($query);

        // Then entries with a wildcard user and a non-wildcard domain.
        $query = mysqli_query($link,
            "SELECT address_user, address_domain, recipient FROM mail_routing WHERE address_user = '*' AND address_domain != '*' ORDER BY address_domain, address_user")
        or die(mysqli_error($link));
        while ($cols = mysqli_fetch_array($query)) {
            $user = $cols['address_user'];
            $domain = $cols['address_domain'];
            $recipient = $cols['recipient'];

            $result[] = new MailRoute($user, $domain, $recipient);
        }
        mysqli_free_result($query);

        // Finally full wildcard entries
        $query = mysqli_query($link,
            "SELECT address_user, address_domain, recipient FROM mail_routing WHERE address_user = '*' AND address_domain = '*' ORDER BY address_domain, address_user")
        or die(mysqli_error($link));
        while ($cols = mysqli_fetch_array($query)) {
            $user = $cols['address_user'];
            $domain = $cols['address_domain'];
            $recipient = $cols['recipient'];

            $result[] = new MailRoute($user, $domain, $recipient);
        }
        mysqli_free_result($query);

        return $result;
    }

    /**
     * @return null|string
     */
    public static function getWildcardRecipient()
    {
        $result = null;
        $link = Config::instance()->getDatabaseLink();

        $query = mysqli_query($link,
            "SELECT recipient FROM mail_routing WHERE address_user = '*' AND address_domain = '*'")
        or die(mysqli_error($link));
        if ($cols = mysqli_fetch_array($query)) {
            $result = $cols['recipient'];
        }
        mysqli_free_result($query);

        return $result;
    }

    /**
     * @param string $address_user
     * @param string $address_domain
     * @param string $recipient
     * @param string $result_message
     *
     * @return bool
     */
    public static function create($address_user, $address_domain, $recipient, &$result_message)
    {
        if (empty($recipient)) {
            $result_message = "Mail routing entry must have a recipient.";

            return false;
        }

        $link = Config::instance()->getDatabaseLink();
        $address_user_esc = mysqli_real_escape_string($link, $address_user);
        $address_domain_esc = mysqli_real_escape_string($link, $address_domain);
        $recipient_esc = mysqli_real_escape_string($link, $recipient);

        mysqli_query($link,
            "INSERT INTO mail_routing ( address_user, address_domain, recipient ) VALUES ( '$address_user_esc', '$address_domain_esc', '$recipient_esc' )")
        or die(mysqli_error($link));
        $result_message = "Mail routing entry successfully added.";

        return true;
    }
}
