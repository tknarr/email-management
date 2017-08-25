<?php
/**
 * Copyright (C) 2017 Todd Knarr <tknarr@silverglass.org>
 */

// Run our user authentication, fail if the browser isn't sending credentials or the password validation fails
if (!isset($_SERVER ['PHP_AUTH_USER']) || !isset($_SERVER ['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="EMail Admin System"');
    header('HTTP/1.0 401 Unauthorized');
    die('Authentication is required to use the EMail admin system.');
}
// We'll need the logged-in user object later
$logged_in_user = new User($_SERVER['PHP_AUTH_USER']);
if (!$logged_in_user->validate($_SERVER ['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="EMail Admin System"');
    header('HTTP/1.0 401 Unauthorized');
    die('You must be logged in to use the EMail admin system.');
}
// Having this makes for convenient shorthand when checking admin rights
$logged_in_as_admin = Config::instance()->isAdminUser($logged_in_user->getUsername());
