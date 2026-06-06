<?php
// includes/mailer.php
// ============================================
// REUSABLE EMAIL SENDING FUNCTIONS
// ============================================
// Uses PHPMailer to send verification and password reset emails via Gmail SMTP.

// Load PHPMailer classes (manual install — no Composer autoload needed)
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

// Load SMTP configuration
require_once __DIR__ . '/smtp_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Create and configure a PHPMailer instance with SMTP settings.
 * This is a helper used by all email functions below.
 * @return PHPMailer Configured mailer instance
 */
function create_mailer() {
    $mail = new PHPMailer(true); // true = enable exceptions

    // --- Server Settings ---
    $mail->isSMTP();                          // Use SMTP protocol
    $mail->Host       = SMTP_HOST;            // Gmail SMTP server
    $mail->SMTPAuth   = true;                 // Enable authentication
    $mail->Username   = SMTP_USERNAME;        // Your Gmail address
    $mail->Password   = SMTP_PASSWORD;        // Your Gmail App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // use the constant here instead
     $mail->Port       = SMTP_PORT;
    // --- Sender Info ---
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->isHTML(true);                      // Send as HTML email
    $mail->CharSet = 'UTF-8';

    return $mail;
}

/**
 * Send an email verification link to a newly registered user.
 * @param string $email Recipient email address
 * @param string $name  Recipient name
 * @param string $token Verification token
 * @return bool True on success, false on failure
 */
function send_verification_email($email, $name, $token) {
    try {
        $mail = create_mailer();
        $mail->addAddress($email, $name);

        // Build the verification URL
        $verify_url = SITE_URL . '/verify_email.php?token=' . urlencode($token);

        $mail->Subject = 'Verify Your HAMROFUTSAL Account';
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background-color: #f9fafb;">
            <div style="background-color: #ffffff; border-radius: 12px; padding: 35px; border: 1px solid #e5e7eb;">
                <div style="text-align: center; margin-bottom: 25px;">
                    <h1 style="color: #10b981; font-size: 28px; margin: 0;">HAMRO<span style="color: #1f2937;">FUTSAL</span></h1>
                    <p style="color: #6b7280; font-size: 14px; margin-top: 5px;">Smart Futsal Booking System</p>
                </div>

                <h2 style="color: #064e3b; font-size: 20px; margin-bottom: 15px;">Welcome, ' . htmlspecialchars($name) . '! 👋</h2>

                <p style="color: #4b5563; font-size: 15px; line-height: 1.7;">
                    Thank you for registering on HAMROFUTSAL. To activate your account and start booking futsal courts, please verify your email address by clicking the button below:
                </p>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $verify_url . '" style="display: inline-block; background-color: #10b981; color: #ffffff; text-decoration: none; padding: 14px 35px; border-radius: 8px; font-weight: 600; font-size: 16px;">
                        ✅ Verify My Email
                    </a>
                </div>

                <p style="color: #9ca3af; font-size: 13px; line-height: 1.6;">
                    If the button does not work, copy and paste this link into your browser:<br>
                    <a href="' . $verify_url . '" style="color: #10b981; word-break: break-all;">' . $verify_url . '</a>
                </p>

                <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 25px 0;">

                <p style="color: #9ca3af; font-size: 12px; text-align: center;">
                    This verification link expires in 24 hours.<br>
                    If you did not create this account, please ignore this email.
                </p>
            </div>
        </div>';

        $mail->AltBody = "Welcome to HAMROFUTSAL, {$name}! Please verify your email by visiting: {$verify_url}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error for debugging (don't expose to users)
        error_log("HAMROFUTSAL Mail Error (verification): " . $e->getMessage());
        return false;
    }
}

/**
 * Send a password reset link to a user.
 * @param string $email Recipient email address
 * @param string $name  Recipient name
 * @param string $token Reset token
 * @return bool True on success, false on failure
 */
function send_reset_email($email, $name, $token) {
    try {
        $mail = create_mailer();
        $mail->addAddress($email, $name);

        // Build the reset URL
        $reset_url = SITE_URL . '/reset_password.php?token=' . urlencode($token);

        $mail->Subject = 'Reset Your HAMROFUTSAL Password';
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background-color: #f9fafb;">
            <div style="background-color: #ffffff; border-radius: 12px; padding: 35px; border: 1px solid #e5e7eb;">
                <div style="text-align: center; margin-bottom: 25px;">
                    <h1 style="color: #10b981; font-size: 28px; margin: 0;">HAMRO<span style="color: #1f2937;">FUTSAL</span></h1>
                    <p style="color: #6b7280; font-size: 14px; margin-top: 5px;">Smart Futsal Booking System</p>
                </div>

                <h2 style="color: #064e3b; font-size: 20px; margin-bottom: 15px;">Password Reset Request 🔐</h2>

                <p style="color: #4b5563; font-size: 15px; line-height: 1.7;">
                    Hello <strong>' . htmlspecialchars($name) . '</strong>, we received a request to reset your password. Click the button below to choose a new password:
                </p>

                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . $reset_url . '" style="display: inline-block; background-color: #10b981; color: #ffffff; text-decoration: none; padding: 14px 35px; border-radius: 8px; font-weight: 600; font-size: 16px;">
                        🔑 Reset My Password
                    </a>
                </div>

                <p style="color: #9ca3af; font-size: 13px; line-height: 1.6;">
                    If the button does not work, copy and paste this link into your browser:<br>
                    <a href="' . $reset_url . '" style="color: #10b981; word-break: break-all;">' . $reset_url . '</a>
                </p>

                <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 0 6px 6px 0; margin: 20px 0;">
                    <p style="color: #92400e; font-size: 13px; margin: 0;">
                        <strong>⚠️ Security Notice:</strong> This link expires in 1 hour. If you did not request this reset, please ignore this email — your password will remain unchanged.
                    </p>
                </div>

                <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 25px 0;">

                <p style="color: #9ca3af; font-size: 12px; text-align: center;">
                    This is an automated email from HAMROFUTSAL.<br>
                    Please do not reply to this email.
                </p>
            </div>
        </div>';

        $mail->AltBody = "Hello {$name}, reset your HAMROFUTSAL password by visiting: {$reset_url} - This link expires in 1 hour.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("HAMROFUTSAL Mail Error (reset): " . $e->getMessage());
        return false;
    }
}
?>
