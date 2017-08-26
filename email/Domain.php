<?php
/**
 * Copyright (C) 2017 Todd Knarr <tknarr@silverglass.org>
 */

/**
 * Class Domain
 */
class Domain
{
    /** @var string */
    private $domainName;

    /**
     * Domain constructor.
     *
     * @param string $domain_name
     */
    public function __construct($domain_name)
    {
        $this->domainName = $domain_name;
    }

    /**
     * @return string
     */
    public function getDomainName()
    {
        return $this->domainName;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        $link = Config::instance()->getDatabaseLink();
        $domain_esc = mysqli_real_escape_string($link, $this->domainName);
        $query = mysqli_query($link, "SELECT * FROM hosted_domains WHERE name = '$domain_esc'")
        or die(mysqli_error($link));
        $numrows = mysqli_num_rows($query);
        mysqli_free_result($query);

        return $numrows > 0;
    }

    /**
     * Update the default recipient for a domain
     *
     * @param string $default_recipient
     * @param string $result_message
     *
     * @return bool
     */
    public function update($default_recipient, &$result_message)
    {
        $link = Config::instance()->getDatabaseLink();
        $domain_esc = mysqli_real_escape_string($link, $this->domainName);

        if (empty($default_recipient)) {
            mysqli_query($link, "DELETE FROM mail_routing WHERE address_user = '*' AND address_domain = '$domain_esc'")
            or die(mysqli_error($link));
            $result_message = "Default user successfully removed.";
        } else {
            $default_recipient_esc = mysqli_real_escape_string($link, $default_recipient);

            $query = mysqli_query($link,
                "SELECT * FROM mail_routing WHERE address_user = '*' AND address_domain = '$domain_esc'")
            or die(mysqli_error($link));
            $num_rows = mysqli_num_rows($query);
            mysqli_free_result($query);

            if ($num_rows > 0) {
                mysqli_query($link,
                    "UPDATE mail_routing SET recipient = '$default_recipient_esc' WHERE address_user = '*' AND address_domain = '$domain_esc'")
                or die(mysqli_error($link));
                $result_message = "Default user successfully updated.";
            } else {
                $default_recipient_esc = mysqli_real_escape_string($link, $default_recipient);
                mysqli_query($link,
                    "INSERT INTO mail_routing ( address_user, address_domain, recipient ) VALUES ( '*', '$domain_esc', '$default_recipient_esc' )")
                or die(mysqli_error($link));
                $result_message = "Default user successfully added.";
            }
        }

        return true;
    }

    /**
     * Delete the domain and any associated mail routing entries
     *
     * @param string $result_message
     *
     * @return bool
     */
    public function delete(&$result_message)
    {
        $link = Config::instance()->getDatabaseLink();
        $domain_esc = mysqli_real_escape_string($link, $this->domainName);

        mysqli_query($link, "DELETE FROM hosted_domains WHERE name = '$domain_esc'") or die(mysqli_error($link));
        // Delete all mail routing entries for the domain, if any
        mysqli_query($link, "DELETE FROM mail_routing WHERE address_domain = '$domain_esc'");
        $result_message = "The domain was successfully deleted.";

        return true;
    }

    /**
     * @return null|string
     */
    public function getDefaultRecipient()
    {
        $result = null;
        $link = Config::instance()->getDatabaseLink();

        $domain_name_esc = mysqli_real_escape_string($link, $this->domainName);
        $query = mysqli_query($link,
            "SELECT recipient FROM mail_routing WHERE address_user = '*' AND address_domain = '$domain_name_esc'")
        or die(mysqli_error($link));
        if ($cols = mysqli_fetch_array($query)) {
            $result = $cols['recipient'];
        }
        mysqli_free_result($query);

        return $result;
    }

    /**
     * Get hosted domain list
     *
     * @return Domain[]
     */
    public static function getDomains()
    {
        $result = [];
        $link = Config::instance()->getDatabaseLink();

        $query = mysqli_query($link, "SELECT name FROM hosted_domains ORDER BY name") or die(mysqli_error($link));

        while ($cols = mysqli_fetch_array($query)) {
            $result[] = new Domain($cols['name']);
        }
        mysqli_free_result($query);

        return $result;
    }

    /**
     * @param string $domain_name
     * @param null|string $default_recipient
     * @param string $result_message
     *
     * @return bool
     */
    public static function create($domain_name, $default_recipient, &$result_message)
    {
        $link = Config::instance()->getDatabaseLink();
        $domain_esc = mysqli_real_escape_string($link, $domain_name);

        mysqli_query($link, "INSERT INTO hosted_domains ( name ) VALUES ( '$domain_esc' )")
        or die(mysqli_error($link));
        $result_message = "Domain successfully added.";

        if (!empty($default_recipient))
        {
            $default_recipient_esc = mysqli_real_escape_string($link, $default_recipient);

            mysqli_query($link,
                "INSERT INTO mail_routing ( address_user, address_domain, recipient ) VALUES ( '*', '$domain_esc', '$default_recipient_esc' )")
            or die(mysqli_error($link));
            $result_message = "Domain successfully added with default recipient.";
        }

        return true;
    }
}
