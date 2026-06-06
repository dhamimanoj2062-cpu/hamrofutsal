<?php
// includes/smtp_config.php
// ============================================
// SMTP EMAIL CONFIGURATION FOR HAMROFUTSAL
// ============================================
// IMPORTANT: Replace the values below with your own Gmail credentials.
// To get a Gmail App Password:
// 1. Go to https://myaccount.google.com/security
// 2. Enable "2-Step Verification"
// 3. Go to "App Passwords" → Select "Mail" → Generate
// 4. Copy the 16-character password and paste it below

// --- SMTP Server Settings ---
define('SMTP_HOST', 'smtp.gmail.com');       // Gmail SMTP server
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
// --- Your Gmail Credentials ---
define('SMTP_USERNAME', 'support.hamrofutsal@gmail.com');  // ← CHANGE THIS to your Gmail
define('SMTP_PASSWORD', 'smrutkpuowfacqjx');      // ← CHANGE THIS to your App Password

// --- Sender Display Info ---
define('SMTP_FROM_EMAIL', 'support.hamrofutsal@gmail.com'); // ← Same as SMTP_USERNAME
define('SMTP_FROM_NAME', 'HAMROFUTSAL');            // Sender display name

// --- Site URL (used to build verification/reset links) ---
// Change this if your project is in a different folder or on a live server
define('SITE_URL', 'http://localhost/hamrofutsal/futsal2');

// --- Token Expiry Times ---
define('VERIFICATION_TOKEN_EXPIRY', 24 * 60 * 60); // 24 hours in seconds
define('RESET_TOKEN_EXPIRY', 60 * 60);              // 1 hour in seconds
define('RESEND_COOLDOWN', 2 * 60);                  // 2 minutes between resends

// --- Login Security ---
define('MAX_LOGIN_ATTEMPTS', 5);              // Lock after 5 failed attempts
define('LOCKOUT_DURATION', 15 * 60);          // Lock for 15 minutes (in seconds)
?>
