<?php
// Configure session before starting
ini_set('session.gc_maxlifetime', 60);
session_set_cookie_params(60);
session_start();

session_destroy();

// Redirect with optional timeout message
if (isset($_GET['timeout'])) {
    header('Location: ../index.php?error=Session expired due to inactivity');
} else {
    header('Location: ../index.php');
}
exit();
?>