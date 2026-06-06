<?php
// forgot_password.php
// Handles password reset requests by sending an email

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
                $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = :email");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch();
                
                // ALWAYS show success message to prevent email enumeration
                $success_msg = "If an account with that email exists, a password reset link has been sent. Please check your inbox.";
                
                if ($user) {
                    $token = generate_secure_token();
                    // expiry time is NOW + 1 hour (using MySQL syntax for the query or PHP for string)
                    $expiry = date('Y-m-d H:i:s', time() + RESET_TOKEN_EXPIRY);
                    
                    $update_stmt = $conn->prepare("UPDATE users SET reset_token = :token, token_expiry = :expiry WHERE id = :id");
                    $update_stmt->execute([
                        'token' => $token,
                        'expiry' => $expiry,
                        'id' => $user['id']
                    ]);
                    
                    send_reset_email($email, $user['name'], $token);
                }
            } catch (PDOException $e) {
                $error_msg = "Database error: " . $e->getMessage();
                $success_msg = ""; // clear success msg on error
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
                <h2>Account Recovery</h2>
                <p>Enter your email address and we will send you secure instructions to reset your password and get you back on the field.</p>
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
            
            <h2>Forgot Password</h2>
            <p class="subtitle">Enter your email to recover your account</p>
            
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
                <a href="login.php" class="btn btn-primary btn-block" style="margin-top: 15px;">Return to Login</a>
            <?php else: ?>
                <form action="forgot_password.php" method="POST">
                    <?php csrf_input_field(); ?>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your registered email" value="<?php echo sanitize($email); ?>" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">Send Reset Link</button>
                </form>
                
                <div style="text-align: center; margin-top: 20px; font-size: 0.9rem; color: #9ca3af;">
                    Remember your password? <a href="login.php" style="color: var(--primary-color); font-weight: 600;">Login here</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
