<?php
// Redirect to dashboard or login depending on session
session_start();
if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;