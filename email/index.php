<?php require 'ini.php'; ?>
<!DOCTYPE html>
<!--
    Copyright 2015 Todd Knarr
    Licensed under the terms of the GPL v3.0 or any later version
 -->
<html>
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
if ( !empty( $org ) )
{
    $title = htmlspecialchars( $org." e-mail user links" );
}
else
{
    $title = "E-mail user links";
}

echo "<title>".$title."</title>".PHP_EOL;
?>
<link href="main.css" rel="stylesheet" type="text/css" title="Standard styles" />
</head>
<body>

<?php echo "    <h1 class=\"page_title\">".$title."</h1>".PHP_EOL; ?>

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