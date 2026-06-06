<?php
// resend_verification.php
// Resends the email verification link to users

require_once 'includes/header.php';
require_once 'includes/db.php';
require_once 'includes/mailer.php';
require_once 'includes/smtp_config.php';
require_once 'includes/csrf.php';

// If already logged in, redirect
if (is_logged_in()) {
    if ($_SESSION['user_role'] === 'owner') {
        header("Location: owner/dashboard.php");
    } else {
        header("Location: player/dashboard.php");
    }
    exit;
}

$error_msg = "";
$success_msg = "";
$email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_msg = "Invalid request. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error_msg = "Please enter your email address.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Please enter a valid email address.";
        } else {
            try {
                $stmt = $conn->prepare("SELECT id, name, email_verified, verification_sent_at FROM users WHERE email = :email");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    // Don't reveal if account exists or not
                    $success_msg = "If an account exists with that email, a verification link has been sent.";
                } elseif ($user['email_verified'] == 1) {
                    $error_msg = "This email is already verified. You can log in directly.";
                } else {
                    // Check cooldown (rate limiting)
                    $last_sent = $user['verification_sent_at'] ? strtotime($user['verification_sent_at']) : 0;
                    $now = time();
                    
                    if (($now - $last_sent) < RESEND_COOLDOWN) {
                        $wait_time = ceil((RESEND_COOLDOWN - ($now - $last_sent)) / 60);
                        $error_msg = "Please wait {$wait_time} minute(s) before requesting another email.";
                    } else {
                        // Generate new token
                        $new_token = generate_secure_token();
                        
                        // Update database
                        $update_stmt = $conn->prepare("UPDATE users SET verification_token = :token, verification_sent_at = CURRENT_TIMESTAMP WHERE id = :id");
                        $update_stmt->execute([
                            'token' => $new_token,
                            'id' => $user['id']
                        ]);
                        
                        // Send email
                        if (send_verification_email($email, $user['name'], $new_token)) {
                            $success_msg = "Verification email sent! Please check your inbox (and spam folder).";
                        } else {
                            $error_msg = "Failed to send email. Please try again later or contact support.";
                        }
                    }
                }
            } catch (PDOException $e) {
                $error_msg = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="auth-split-wrapper">
    <!-- Left Column: Branding and Image -->
    <div class="auth-split-image" style="background-image: url('assets/images/default_futsal.jpg');">
        <div class="auth-split-image-overlay"></div>
        <div class="auth-split-image-content">
            <div class="auth-split-logo-row">
                <a href="index.php" class="logo">
                    HAMRO<span>FUTSAL</span>
                </a>
                <a href="index.php" class="btn-back-website">Back to website &rarr;</a>
            </div>
            
            <div class="auth-split-promo-text">
                <h2>Email Verification Required</h2>
                <p>Ensure your account security. Request a new verification link to activate your player or owner profile.</p>
                <div class="carousel-indicators-mini">
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot active"></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Form -->
    <div class="auth-split-form-container">
        <div class="auth-split-form-box">
            <!-- Mobile header (hidden on desktop) -->
            <div class="mobile-auth-header">
                <a href="index.php" class="logo">
                    HAMRO<span>FUTSAL</span>
                </a>
                <a href="index.php" class="btn-back-website-mobile">&larr; Home</a>
            </div>
            
            <h2>Resend Verification</h2>
            <p class="subtitle">Enter your email to receive a new link</p>
            
            <!-- Display session flash notifications if set -->
            <?php display_flash_message(); ?>
            
            <?php if (!empty($error_msg)): ?>
                <div style="background-color: #fce8e6; color: #c5221f; border: 1px solid #c5221f; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; font-size: 0.9rem;">
                    <?php echo sanitize($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_msg)): ?>
                <div style="background-color: #064e3b; color: #a7f3d0; border: 1px solid #047857; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem;">
                    <?php echo sanitize($success_msg); ?>
                </div>
                <a href="login.php" class="btn btn-primary btn-block" style="margin-top: 15px;">Go to Login</a>
            <?php else: ?>
                <form action="resend_verification.php" method="POST">
                    <?php csrf_input_field(); ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Registered Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="e.g. name@example.com" value="<?php echo sanitize($email); ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">Send Verification Email</button>
                </form>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 20px; font-size: 0.9rem; color: #9ca3af;">
                <a href="login.php" style="color: var(--primary-color); font-weight: 600;">Back to Login</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
