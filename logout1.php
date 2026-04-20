<?php
session_start();

// Clear the persistent session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session data
session_unset();
session_destroy();

// Redirect to login page with logout message
header("Location: student_login.php?logout=success");
exit();
?>