<?php
/**
 * adminlogout.php — destroys the admin session and returns to login.
 */
session_start();
unset($_SESSION['admin_id'], $_SESSION['admin_username']);
header('Location: adminlogin.php');
exit;