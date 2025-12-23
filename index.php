<?php
session_start();
error_reporting(E_ALL);

ini_set('display_errors', '1');
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['status'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: student/dashboard.php");
    }
    exit();
}

header("Location: login.php");
exit();
?>