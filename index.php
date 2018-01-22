<?php
require_once 'Dads.class.php';

if (!include('config.php'))
    die('Error: Could not open \"config.php\", please edit and rename \"config.php.dist\"');
?>


<html>
<body>
<form action="FilesenderRestClient.php" method="post">
    <input type="hidden" name="itemid" value="<?php echo htmlspecialchars($_SERVER['QUERY_STRING']); ?>">

    <?php
    if (isset($_SERVER['HTTP_REFERER'])) {
        ?>
        <input type="hidden" name="referrer" value="<?php echo htmlspecialchars($_SERVER['HTTP_REFERER']); ?>">
        <?php
    }
    ?>
    You have requested items that have conditional access. By submitting your email address, you are agreeing to the
    following conditions of access <?php echo ACCESS_CONDITIONS; ?>
    <br/>
    E-mail: <input type="text" name="email"/></p>
    <br/>
    <p><input type="submit" value="Request the items"></p>
</form>
</body>
</html>