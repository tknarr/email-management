<?php
/**
 * Copyright (C) 2017 Todd Knarr <tknarr@silverglass.org>
 */

/**
 * Software configuration data
 */
class Config
{
    /** @var null|Config */
    private static $instance = null;

    /** @var string */
    private $organization;
    /** @var bool */
    private $defaultVirtualUsers;
    /** @var string */
    private $dbHostname;
    /** @var string */
    private $dbDatabase;
    /** @var string */
    private $dbUsername;
    /** @var string */
    private $dbPassword;
    /** @var mysqli */
    private $databaseLink;
    /** @var string[] */
    private $adminUsers;
    /** @var int */
    private $maxAuthenticationTries;
    /** @var int */
    private $maxPasswordChangeTries;

    /** @var string */
    private $backupMXToken;

    /**
     * Config constructor, used only internally.
     */
    private function __construct()
    {
        $ini_file = parse_ini_file("/etc/email_management.ini") or die("Error reading configuration.");

        $this->organization = $ini_file ["organization"];
        if (empty($this->organization)) {
            $this->organization = $_ENV ["ORGANIZATION"];
        }
        $this->defaultVirtualUsers = strtolower(trim($ini_file["default_user_type"])) == 'virtual';
        // Get database connection settings from config file
        $this->dbHostname = $ini_file ["host"];
        $this->dbDatabase = $ini_file ["dbname"];
        $this->dbUsername = $ini_file ["user"];
        $this->dbPassword = $ini_file ["password"];
        if (empty($this->dbHostname) || empty($this->dbUsername) || empty($this->dbPassword)
            || empty($this->dbDatabase)) {
            die("Database not configured.");
        }

        // Generate the list of admin usernames
        $au_raw = trim($ini_file ["admin_user"]);
        if (!$au_raw) {
            $au_raw = "root";
        }
        $au_list = explode(",", $au_raw);
        $this->adminUsers = [];
        foreach ($au_list as $au_item) {
            $au_t = trim($au_item);
            if ($au_t) {
                $this->adminUsers[] = $au_t;
            }
        }

        $this->maxAuthenticationTries = $ini_file['max_auth_attempts'];
        if (empty($this->maxAuthenticationTries)) {
            $this->maxAuthenticationTries = 3;
        }
        $this->maxPasswordChangeTries = $ini_file['max_password_change_attempts'];
        if (empty($this->maxPasswordChangeTries)) {
            $this->maxPasswordChangeTries = 5;
        }

        $this->backupMXToken = $ini_file['backup_mx_token'];

        // Connect to database
        $this->databaseLink = mysqli_connect($this->dbHostname, $this->dbUsername, $this->dbPassword, $this->dbDatabase)
        or die(mysqli_connect_error());
        mysqli_autocommit($this->databaseLink, false);
    }

    /**
     * Return the single instance of the configuration.
     *
     * @return Config
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Config();
        }

        return self::$instance;
    }

    /**
     * @return string
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @return bool
     */
    public function defaultVirtualUsers()
    {
        return $this->defaultVirtualUsers;
    }

    /**
     * @return string
     */
    public function getDbHostname()
    {
        return $this->dbHostname;
    }

    /**
     * @return string
     */
    public function getDbDatabase()
    {
        return $this->dbDatabase;
    }

    /**
     * @return string
     */
    public function getDbUsername()
    {
        return $this->dbUsername;
    }

    /**
     * @return string
     */
    public function getDbPassword()
    {
        return $this->dbPassword;
    }

    /**
     * @return mysqli
     */
    public function getDatabaseLink()
    {
        return $this->databaseLink;
    }

    /**
     * @return string[]
     */
    public function getAdminUsers()
    {
        return $this->adminUsers;
    }

    /**
     * @param string $username
     *
     * @return bool
     */
    public function isAdminUser($username)
    {
        return in_array($username, $this->adminUsers);
    }

    /**
     * @return int
     */
    public function getMaxAuthenticationTries()
    {
        return $this->maxAuthenticationTries;
    }

    /**
     * @return int
     */
    public function getMaxPasswordChangeTries()
    {
        return $this->maxPasswordChangeTries;
    }

    /**
     * @return null|string
     */
    public function getBackupMXToken()
    {
        return $this->backupMXToken;
    }
}
