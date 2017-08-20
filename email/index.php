<?php require 'ini.php'; ?>
<!DOCTYPE html>

<html>
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    /**
     * Copyright (C) 2017 Todd Knarr <tknarr@silverglass.org>
     */

    /**
     * This program is free software: you can redistribute it and/or modify it under the
     * terms of the GNU General Public License as published by the Free Software Foundation,
     * either version 3 of the License, or (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful, but WITHOUT ANY
     * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
     * PARTICULAR PURPOSE. See the GNU General Public License for more details.
     * You should have received a copy of the GNU General Public License along with this
     * program. If not, see http://www.gnu.org/licenses/
     */

    if ( !empty( $org ) ) {
        $title = htmlspecialchars( $org . " e-mail user links" );
    }
    else {
        $title = "E-mail user links";
    }

    echo "<title>" . $title . "</title>" . PHP_EOL;
    ?>
    <link href="main.css" rel="stylesheet" type="text/css" title="Standard styles"/>
</head>
<body>

<?php echo "    <h1 class=\"page_title\">" . $title . "</h1>" . PHP_EOL; ?>

<p>
<table class="index">
    <tr>
        <td class="index"><a href="ChangePassword.php">Change user's e-mail password</a></td>
    </tr>
    <tr>
        <td class="index"><a href="admin.php">Admin links</a></td>
    </tr>
</table>
</p>

</body>
</html>
