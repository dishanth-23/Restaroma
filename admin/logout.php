<?php
// admin/logout.php
// Logs out admin.
$pageClass = "admin-page";
require __DIR__ . '/../includes/auth.php';

adminLogout();
header('Location: login.php');
exit;