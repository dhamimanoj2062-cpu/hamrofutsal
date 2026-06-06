<?php
// logout.php
// Securely terminates user sessions and returns the user to the homepage

require_once 'includes/auth.php';

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will completely destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Start a fresh, clean session to pass a success flash message to the homepage
session_start();
set_flash_message("You have been logged out successfully.", "info");

// Redirect to the visitor homepage
header("Location: index.php");
exit;
?>
