<?php
// index.php — หน้าหลัก redirect ไปที่เหมาะสม
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: books/index.php');
} else {
    header('Location: auth/login.php');
}
exit;