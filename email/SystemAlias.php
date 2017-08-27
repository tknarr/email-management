<?php
/**
 * Copyright (C) 2017 Todd Knarr <tknarr@silverglass.org>
 */

/**
 * File description
 */

/**
 * Class SystemAlias
 */
class SystemAlias
{
    /** @var string */
    private $alias;
    /** @var string[] */
    private $recipients;

    /**
     * SystemAlias constructor.
     *
     * @param string $alias
     * @param string[] $recipients
     */
    public function __construct($alias, $recipients)
    {
        $this->alias = $alias;
        $this->recipients = $recipients;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string[]
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * @return SystemAlias[]
     */
    public static function getSystemAliases()
    {
        $aliases_file = new SplFileObject("/etc/aliases", "r");
        $aliases_file->setFlags(SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY);

        $aliases = [];
        foreach ($aliases_file as $raw_alias_line) {
            $alias_line = trim($raw_alias_line);
            // Skip blank lines and comments
            if (strlen($alias_line) == 0 || $alias_line[0] == '#') {
                continue;
            }
            // Parse a single alias line, it must have exactly two fields
            $alias_entry = explode(":", $alias_line, 2);
            if (count($alias_entry) == 2) {
                $alias_user = trim($alias_entry[0]);
                // Target is a comma-separated list of recipients, break it up into an array trimming off
                // excess whitespace in the process
                $alias_recipient_list = explode(',', $alias_entry[1]);
                $recipients = [];
                foreach ($alias_recipient_list as $alias_recipient) {
                    $trimmed_recipient = trim($alias_recipient);
                    if (!empty($trimmed_recipient)) {
                        $recipients[] = $trimmed_recipient;
                    }
                }
                // Sort the recipients and push the new system alias onto the list
                asort($recipients, SORT_STRING);
                $aliases[] = new SystemAlias($alias_user, $recipients);
            }
        }
        // Make sure we get rid of the file so it gets physically closed
        $aliases_file = null;

        return $aliases;
    }
}
