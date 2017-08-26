<?php
/**
 * Copyright (C) 2017 Todd Knarr <tknarr@silverglass.org>
 */

/**
 * Class User
 */
class User
{
    /** @var string */
    private $username;

    /**
     * User constructor.
     *
     * @param string $username
     */
    public function __construct($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        $link = Config::instance()->getDatabaseLink();
        $username_esc = mysqli_real_escape_string($link, $this->username);
        $query = mysqli_query($link, "SELECT * FROM mail_users WHERE username = '$username_esc'")
        or die(mysqli_error($link));
        $numrows = mysqli_num_rows($query);
        mysqli_free_result($query);

        return $numrows > 0;
    }

    /**
     * Confirm that the user's password matches what's in the database and the maximum number of
     * authentication attempts hasn't been reached yet. Failures increment the number of authentication
     * attempts.
     *
     * @param string $password
     *
     * @return bool
     */
    public function validate($password)
    {
        $link = Config::instance()->getDatabaseLink();

        // Query the database to find the user's password
        $username_esc = mysqli_real_escape_string($link, $this->username);
        $query = mysqli_query($link, "SELECT password, auth_attempts FROM mail_users WHERE username = '$username_esc'")
        or die(mysqli_error($link));
        // If user not found, fail
        $numrows = mysqli_num_rows($query);
        if ($numrows == 0) {
            mysqli_free_result($query);

            return false;
        }
        $database_hashed_password = null;
        $tries = Config::instance()->getMaxAuthenticationTries();
        while ($cols = mysqli_fetch_array($query)) {
            // Hashed form of correct password
            $database_hashed_password = $cols ['password'];
            // Number of tries at authentication or changing the password
            $tries = $cols ['auth_attempts'];
        }
        mysqli_free_result($query);

        // Check for problems with correct password hash
        if (!$database_hashed_password || substr($database_hashed_password, 0, 3) != "$6$") {
            return false;
        }
        // Hash the entered password
        $hashed_password = self::hashPassword($password, $database_hashed_password);
        if (is_null($hashed_password)) {
            return false;
        }

        // If passwords don't match or maximum tries exceeded, increment the tries counter and return a fail
        if ($hashed_password != $database_hashed_password
            || $tries >= Config::instance()->getMaxAuthenticationTries()) {
            mysqli_query($link,
                "UPDATE mail_users SET auth_attempts = auth_attempts + 1 WHERE username = '$username_esc'");
            mysqli_commit($link) or die("Database error.");

            return false;
        }

        // We passed all checks, zero out the number of failed authentication attempts
        mysqli_query($link, "UPDATE mail_users SET auth_attempts = 0 WHERE username = '$username_esc'");
        mysqli_commit($link) or die ("Database error.");

        return true;
    }

    /**
     * Delete the user and any mail routing entries associated with them. The user may not be deleted
     * if they're root or if they're the default recipient of all mail for any domain.
     *
     * @param string $result_message
     *
     * @return bool
     */
    public function delete(&$result_message)
    {
        $link = Config::instance()->getDatabaseLink();

        if ($this->username == 'root') {
            $result_message = "Root user may not be deleted.";

            return false;
        }

        // Query the database to check for the user's existence
        $username_esc = mysqli_real_escape_string($link, $this->username);
        $query = mysqli_query($link, "SELECT * FROM mail_users WHERE username = '$username_esc'")
        or die(mysqli_error($link));
        $numrows = mysqli_num_rows($query);
        mysqli_free_result($query);
        if ($numrows == 0) {
            $result_message = "This username does not exist.";

            return false;
        }
        // When deleting a user that's the target of a default mail routing entry for a domain, complain
        $query =
            mysqli_query($link, "SELECT * FROM mail_routing WHERE recipient = '$username_esc' AND address_user = '*'");
        $numrows_domain = mysqli_num_rows($query);
        mysqli_free_result($query);
        if ($numrows_domain > 0) {
            $result_message = "User is the default destination for a domain.";

            return false;
        }

        $result_message = "The user was successfully deleted.";
        mysqli_query($link, "DELETE FROM mail_users WHERE username = '$username_esc'") or die(mysqli_error($link));
        // When deleting a user, delete any mail routing entries that specify them as the recipient
        mysqli_query($link, "DELETE FROM mail_routing WHERE recipient = '$username_esc'") or die(mysqli_error($link));

        return true;
    }

    /**
     * Updates the user's password.
     *
     * @param string $current_password
     * @param string $new_password
     * @param string $result_message
     *
     * @return bool
     */
    public function updatePassword($current_password, $new_password, &$result_message)
    {
        // Query the database to find which user we're working with
        $link = Config::instance()->getDatabaseLink();
        $username_esc = mysqli_real_escape_string($link, $this->username);
        $query =
            mysqli_query($link, "SELECT password, change_attempts FROM mail_users WHERE username = '$username_esc'")
        or die(mysqli_error($link));
        $numrows = mysqli_num_rows($query);
        mysqli_free_result($query);
        if ($numrows == 0) {
            $result_message = "This username does not exist.";

            return false;
        }

        // Gather database information
        $max_tries = Config::instance()->getMaxPasswordChangeTries();
        $tries = $max_tries;
        $hashed_password = '';
        while ($cols = mysqli_fetch_array($query)) {
            $hashed_password = $cols ['password'];
            $tries = $cols ['change_attempts'];
        }
        mysqli_free_result($query);

        // Validation that requires the database

        // Hash the old and new passwords
        $current_hashed_password = self::hashPassword($current_password, $hashed_password);
        $new_hashed_password = self::hashPassword($new_password, null);

        // Checks that have to be done after hashing passwords
        if (($current_hashed_password != $hashed_password || $tries >= $max_tries)
            && !Config::instance()->isAdminUser($this->username)) {
            // The current password given is wrong, or the number of change attempts has been exceeded, and they're
            // not an admin user
            $result_message = "The CURRENT password you entered is incorrect.";
            mysqli_query($link,
                "UPDATE mail_users SET change_attempts = change_attempts + 1 where username = '$username_esc'");
            mysqli_commit($link) or die("Database commit failed.");

            return false;
        } else if (is_null($new_hashed_password)) {
            // The hashed new password is invalid
            $result_message = "An error occurred when hashing the new password.";

            return false;
        } else {
            // The current password matched and they haven't tried too many times and been locked out, or they're
            // an admin user and don't get checked
            $result_message = "Your password has been successfully changed.";
            mysqli_query($link,
                "UPDATE mail_users SET password = '$new_hashed_password', change_attempts = 0 WHERE username = '$username_esc'")
            or die(mysqli_error($link));
            mysqli_commit($link) or die("Database commit failed.");

            return true;
        }
    }

    /**
     * Zero out the password change attempt counter
     */
    public function resetPasswordChangeAttempts()
    {
        $link = Config::instance()->getDatabaseLink();
        $username_esc = mysqli_real_escape_string($link, $this->username);
        $query = mysqli_query($link, "UPDATE mail_users SET change_attempts = 0 WHERE username = '$username_esc'")
        or die(mysqli_error($link));
        mysqli_commit($link) or die("Database commit failed.");
    }

    /**
     * Create a new user in the database.
     *
     * @param string $username
     * @param string $password
     * @param string $account_type
     * @param bool $across_all_domains
     * @param string &$result_message
     * @param string &$maildir_message
     *
     * @return bool
     */
    public static function create($username, $password, $account_type, $across_all_domains, &$result_message,
        &$maildir_message)
    {
        $link = Config::instance()->getDatabaseLink();

        $username_esc = mysqli_real_escape_string($link, $username);

        // Hash the old and new passwords
        // This depends on a Linux-type crypt() implementation
        $hashed_password = crypt($password, null);

        // Checks that have to be done after hashing passwords
        if (substr($hashed_password, 0, 3) != "$6$") {
            $result_message = "An error occurred when hashing the new user's password.";

            return false;
        } else {
            $result_message = "The new user was successfully added.";
            mysqli_query($link,
                "INSERT INTO mail_users ( username, password, acct_type ) VALUES ( '$username_esc', '$hashed_password', '$account_type' )")
            or die(mysqli_error($link));
            $op = [];
            $retval = 0;
            exec('/usr/bin/sudo -u vmail /usr/local/bin/makevmaildir.sh ' . $username, $op, $retval);
            if ($retval != 0) {
                $maildir_message = "The mail directory was not created:";
                foreach ($op as $oline) {
                    $maildir_message = $maildir_message . '<br>' . PHP_EOL . htmlspecialchars($oline);
                }

                return false;
            }

            if ($across_all_domains) {
                // When adding a new user and all-domains was checked, add a "user@* -> user" mail routing entry for them if one doesn't already exist
                $query = mysqli_query($link,
                    "INSERT INTO mail_routing ( address_user, address_domain, recipient ) VALUES ( '$username_esc', '*', '$username_esc' )")
                or die(mysqli_error($link));
                $result_message = "The new user was successfully added with an all-domains entry for mail.";
            }
        }

        return true;
    }

    /**
     * Get username information needed for user maintenance display
     *
     * @return array
     */
    public static function getUsernameInfo()
    {
        $result = [];
        $link = Config::instance()->getDatabaseLink();

        $query = mysqli_query($link,
            "SELECT username, change_attempts, a.abbreviation AS abbreviation FROM mail_users, acct_types a WHERE acct_type = a.code ORDER BY username")
        or die(mysqli_error($link));

        while ($cols = mysqli_fetch_array($query)) {
            $result[] = [$cols['username'], $cols['abbreviation'], $cols['change_attempts']];
        }
        mysqli_free_result($query);

        return $result;
    }

    /**
     * Isolate the hashing function so we can easily replace or update it.
     *
     * @param string $password
     * @param string $salt
     *
     * @return string|null
     */
    private static function hashPassword($password, $salt)
    {
        if (is_null($salt)) {
            // Generate new SHA512 salt: 12 random bytes, base64-encoded to produce 16 characters with the
            // correct prefix and terminating separator for crypt()
            $salt = "$6$" . base64_encode(mcrypt_create_iv(12)) . "$";
        }
        $hashed_password = crypt($password, $salt);
        if (substr($hashed_password, 0, 3) != "$6$") {
            return null;
        }

        return $hashed_password;
    }
}
