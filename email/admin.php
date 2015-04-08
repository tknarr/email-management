<!DOCTYPE html>
<!--
    Copyright 2015 Todd Knarr
    Licensed under the terms of the GPL v3.0
 -->
<html>
<head>
<meta charset="UTF-8" />
<?php
require 'ini.php';

if ( !empty( $org ) )
{
    $title = htmlspecialchars( $org." e-mail system administration" );
}
else 
{
    $title = "E-mail system administration";
}

echo "<title>".$title."</title>";
?>
<link href="main.css" rel="stylesheet" type="text/css" title="Standard styles" />
</head>
<body>

<?php echo "    <h1 class=\"centered\">".$title."</h1>";?>
    
    <table class="centered">
        <tr>
            <td class="centered"><a href="UserMaintenance.php">User account maintenance</a></td>
        </tr>
        <tr>
            <td class="centered"><a href="MailRouting.php">Mail routing entry management</a></td>
        </tr>
        <tr>
            <td class="centered"><a href="VirtualDomains.php">Virtual domain management</a></td>
        </tr>
        <tr>
            <td class="centered"><a href="ChangePassword.php">Password change form</a></td>
        </tr>
    </table>

</body>
</html>