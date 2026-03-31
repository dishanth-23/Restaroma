<?php
// user/logout.php
// Logs out the user and destroys relevant session variables.
$pageClass = "user-page";
require __DIR__ . '/../includes/auth.php';

userLogout();
header('Location: login.php');
exit;