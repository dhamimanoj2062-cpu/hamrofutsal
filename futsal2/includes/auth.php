<?php
// includes/auth.php
// ============================================
// CENTRALIZED AUTHENTICATION, SESSION MANAGEMENT & SECURITY HELPERS
// ============================================

// Start session if it hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include CSRF protection
require_once __DIR__ . '/csrf.php';

/**
 * Checks if a user is currently logged in.
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Restricts access to authenticated users only.
 * Redirects to the login page if not logged in.
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash_message('Please log in to access this page.', 'danger');
        header("Location: ../login.php");
        exit;
    }
}

/**
 * Restricts access to a specific role ('player' or 'owner').
 * @param string $role
 */
function require_role($role) {
    require_login();
    if ($_SESSION['user_role'] !== $role) {
        set_flash_message('Access denied! You do not have permission to view that page.', 'danger');
        if ($_SESSION['user_role'] === 'owner') {
            header("Location: ../owner/dashboard.php");
        } else {
            header("Location: ../player/dashboard.php");
        }
        exit;
    }
}

/**
 * Helper to securely clean output and prevent XSS (Cross-Site Scripting) attacks.
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Set a session flash message to show alerts on the next page load.
 * @param string $message
 * @param string $type (success, danger, info, warning)
 */
function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Displays the flash message if one exists, then clears it.
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        
        // Define alert background & text colors based on the type (Clean Flat Theme)
        $bgColor = '#e6f4ea'; // Soft Green
        $textColor = '#137333';
        $borderColor = '#137333';
        
        if ($type === 'danger') {
            $bgColor = '#fce8e6'; // Soft Red
            $textColor = '#c5221f';
            $borderColor = '#c5221f';
        } elseif ($type === 'warning') {
            $bgColor = '#fef7e0'; // Soft Yellow
            $textColor = '#b06000';
            $borderColor = '#b06000';
        } elseif ($type === 'info') {
            $bgColor = '#e8f0fe'; // Soft Blue
            $textColor = '#1a73e8';
            $borderColor = '#1a73e8';
        }
        
        echo "
        <div style='background-color: {$bgColor}; color: {$textColor}; border: 1px solid {$borderColor}; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem; display: flex; justify-content: space-between; align-items: center;' id='flash-alert'>
            <span>{$msg}</span>
            <button onclick=\"document.getElementById('flash-alert').style.display='none'\" style='background: none; border: none; color: {$textColor}; font-size: 1.2rem; cursor: pointer; font-weight: bold; line-height: 1;'>&times;</button>
        </div>
        ";
        
        // Clear message so it doesn't show again on refresh
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

// ============================================
// NEW SECURITY & VALIDATION HELPER FUNCTIONS
// ============================================

/**
 * Generate a cryptographically secure random token.
 * Used for email verification and password reset tokens.
 * @return string 64-character hex string
 */
function generate_secure_token() {
    return bin2hex(random_bytes(32));
}

/**
 * Validate password strength.
 * Requirements: 8+ chars, 1 uppercase, 1 lowercase, 1 number, 1 special character.
 * @param string $password
 * @return array ['valid' => bool, 'errors' => string[]]
 */
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "At least 8 characters";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "At least 1 uppercase letter (A-Z)";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "At least 1 lowercase letter (a-z)";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "At least 1 number (0-9)";
    }
    if (!preg_match('/[!@#$%^&*()_\-+=\[\]{};:,.<>?\/\\|`~]/', $password)) {
        $errors[] = "At least 1 special character (!@#$%^&*)";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate a Nepal mobile phone number.
 * Must be exactly 10 digits and start with 97 or 98.
 * @param string $phone
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validate_nepal_phone($phone) {
    // Remove any spaces or dashes
    $phone = preg_replace('/[\s\-]/', '', $phone);
    
    if (!preg_match('/^\d+$/', $phone)) {
        return ['valid' => false, 'error' => 'Phone number must contain only numbers.'];
    }
    if (strlen($phone) !== 10) {
        return ['valid' => false, 'error' => 'Phone number must be exactly 10 digits.'];
    }
    if (!preg_match('/^(97|98)/', $phone)) {
        return ['valid' => false, 'error' => 'Phone number must start with 97 or 98 (Nepal mobile).'];
    }
    
    return ['valid' => true, 'error' => null];
}
?>
