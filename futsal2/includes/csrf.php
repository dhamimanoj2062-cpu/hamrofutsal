<?php
// includes/csrf.php
// ============================================
// CSRF (Cross-Site Request Forgery) PROTECTION
// ============================================
// CSRF attacks trick logged-in users into submitting forms on other sites.
// This file generates a unique token per session and validates it on form submission.

// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a CSRF token and store it in the session.
 * If a token already exists, reuse it (one token per session).
 * @return string The CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        // bin2hex(random_bytes(32)) creates a secure 64-character random string
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify that the submitted CSRF token matches the session token.
 * @param string $token The token from the submitted form
 * @return bool True if valid, false if invalid
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    // hash_equals() prevents timing attacks (compares strings in constant time)
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output a hidden HTML input field containing the CSRF token.
 * Usage: <?php csrf_input_field(); ?> inside any <form>
 */
function csrf_input_field() {
    $token = generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}
?>
