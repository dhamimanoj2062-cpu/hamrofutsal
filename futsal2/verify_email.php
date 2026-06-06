<?php
// verify_email.php
// Handles email verification links sent to users

require_once 'includes/header.php';
require_once 'includes/db.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    if ($_SESSION['user_role'] === 'owner') {
        header("Location: owner/dashboard.php");
    } else {
        header("Location: player/dashboard.php");
    }
    exit;
}

$status = 'error';
$message = 'Invalid or missing verification token.';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);
    
    try {
        $stmt = $conn->prepare("SELECT id, name, email_verified FROM users WHERE verification_token = :token");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($user['email_verified'] == 1) {
                $status = 'info';
                $message = 'Your email address is already verified. You can now log in.';
            } else {
                // Verify the user
                $update_stmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = :id");
                $update_stmt->execute(['id' => $user['id']]);
                
                $status = 'success';
                $message = 'Your email address has been successfully verified! You can now log in to your account.';
            }
        } else {
            $status = 'error';
            $message = 'Invalid or expired verification token. Please request a new verification email.';
        }
    } catch (PDOException $e) {
        $status = 'error';
        $message = 'Database error occurred: ' . $e->getMessage();
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
                <h2>Email Verification</h2>
                <p>Activate your account to start booking pitches and checking in on your rewards. Secure, fast, and simple.</p>
                <div class="carousel-indicators-mini">
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot active"></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Verification Status -->
    <div class="auth-split-form-container">
        <div class="auth-split-form-box">
            <!-- Mobile header (hidden on desktop) -->
            <div class="mobile-auth-header">
                <a href="index.php" class="logo">
                    HAMRO<span>FUTSAL</span>
                </a>
                <a href="index.php" class="btn-back-website-mobile">&larr; Home</a>
            </div>
            
            <h2>Verification Status</h2>
            <p class="subtitle">Account activation status check</p>
            
            <!-- Display session flash notifications if set -->
            <?php display_flash_message(); ?>
            
            <?php if ($status === 'success'): ?>
                <div style="background-color: #064e3b; color: #a7f3d0; border: 1px solid #047857; padding: 20px; border-radius: 8px; margin-bottom: 25px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 10px;">✅</div>
                    <h3 style="margin-bottom: 10px; color: #ffffff;">Success!</h3>
                    <p><?php echo sanitize($message); ?></p>
                </div>
                <a href="login.php" class="btn btn-primary btn-block">Proceed to Login</a>
                
            <?php elseif ($status === 'info'): ?>
                <div style="background-color: #1e3a8a; color: #93c5fd; border: 1px solid #2563eb; padding: 20px; border-radius: 8px; margin-bottom: 25px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 10px;">ℹ️</div>
                    <h3 style="margin-bottom: 10px; color: #ffffff;">Already Verified</h3>
                    <p><?php echo sanitize($message); ?></p>
                </div>
                <a href="login.php" class="btn btn-primary btn-block">Proceed to Login</a>
                
            <?php else: ?>
                <div style="background-color: #7f1d1d; color: #fca5a5; border: 1px solid #dc2626; padding: 20px; border-radius: 8px; margin-bottom: 25px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 10px;">❌</div>
                    <h3 style="margin-bottom: 10px; color: #ffffff;">Verification Failed</h3>
                    <p><?php echo sanitize($message); ?></p>
                </div>
                <a href="resend_verification.php" class="btn btn-outline btn-block" style="margin-bottom: 15px; border-color: #dc2626; color: #fca5a5;">Resend Verification Email</a>
                <a href="login.php" class="btn btn-primary btn-block">Go to Login</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
