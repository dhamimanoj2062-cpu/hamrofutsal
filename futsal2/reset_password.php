<?php
// reset_password.php
// Validates token and updates user password

require_once 'includes/header.php';
require_once 'includes/db.php';
require_once 'includes/csrf.php';

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error_msg = "";
$success_msg = "";
$token_valid = false;
$user_id = null;
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if (empty($token)) {
    $error_msg = "Invalid or missing reset token.";
} else {
    try {
        // Check if token is valid and not expired
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE reset_token = :token AND token_expiry > NOW()");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token_valid = true;
            $user_id = $user['id'];
        } else {
            $error_msg = "This password reset link is invalid or has expired. Please request a new one.";
        }
    } catch (PDOException $e) {
        $error_msg = "Database error: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_msg = "Invalid request. Please try again.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $strength = validate_password_strength($password);
        
        if (empty($password) || empty($confirm_password)) {
            $error_msg = "Please fill in all fields.";
        } elseif ($password !== $confirm_password) {
            $error_msg = "Passwords do not match.";
        } elseif (!$strength['valid']) {
            $error_msg = "Password does not meet the security requirements: " . implode(", ", $strength['errors']);
        } else {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $update_stmt = $conn->prepare("UPDATE users SET password = :password, reset_token = NULL, token_expiry = NULL WHERE id = :id");
                $update_stmt->execute([
                    'password' => $hashed_password,
                    'id' => $user_id
                ]);
                
                $success_msg = "Your password has been successfully reset! You can now log in with your new password.";
                $token_valid = false; // Hide the form
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
                <h2>Secure Your Account</h2>
                <p>Create a strong password that meets our complexity requirements. Protect your credentials and keep playing.</p>
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
            
            <h2>Reset Password</h2>
            <p class="subtitle">Create a new secure password</p>
            
            <!-- Display session flash notifications if set -->
            <?php display_flash_message(); ?>
            
            <?php if (!empty($error_msg)): ?>
                <div style="background-color: #fce8e6; color: #c5221f; border: 1px solid #c5221f; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; font-size: 0.9rem;">
                    <?php echo sanitize($error_msg); ?>
                    <?php if (!$token_valid && empty($success_msg)): ?>
                        <div style="margin-top: 10px;">
                            <a href="forgot_password.php" style="color: #c5221f; text-decoration: underline; font-weight: 600;">Request New Link</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_msg)): ?>
                <div style="background-color: #064e3b; color: #a7f3d0; border: 1px solid #047857; padding: 20px; border-radius: 8px; margin-bottom: 25px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 10px;">✅</div>
                    <h3 style="margin-bottom: 10px; color: #ffffff;">Password Reset!</h3>
                    <p><?php echo sanitize($success_msg); ?></p>
                </div>
                <a href="login.php" class="btn btn-primary btn-block">Go to Login</a>
            <?php elseif ($token_valid): ?>
                <form action="reset_password.php" method="POST" id="resetForm">
                    <?php csrf_input_field(); ?>
                    <input type="hidden" name="token" value="<?php echo sanitize($token); ?>">
                    
                    <div class="form-group">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter new password" required>
                        <div id="password-strength-container" style="margin-top: 5px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your new password" required>
                        <div id="password-match-msg" class="validation-hint"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block" id="submitBtn" style="margin-top: 1.5rem;">Save New Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
