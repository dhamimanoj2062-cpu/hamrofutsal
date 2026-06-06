<?php
// register.php
// User registration page supporting Player and Owner roles
// Now with enhanced validation, CSRF protection, and email verification

// Include header which also starts sessions
require_once 'includes/header.php';
require_once 'includes/db.php';
require_once 'includes/csrf.php';
require_once 'includes/mailer.php';

// Redirect if already logged in
if (is_logged_in()) {
    if ($_SESSION['user_role'] === 'owner') {
        header("Location: owner/dashboard.php");
    } else {
        header("Location: player/dashboard.php");
    }
    exit;
}

$error_msg = "";
$name = $email = $phone = $role = "";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Protection Check
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_msg = "Security token invalid or expired. Please try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'player'; // default to player

        // 2. Input validation
        $phone_check = validate_nepal_phone($phone);
        $password_check = validate_password_strength($password);
        
        if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password) || empty($role)) {
            $error_msg = "Please fill in all fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Please enter a valid email address.";
        } elseif (!$phone_check['valid']) {
            $error_msg = $phone_check['error'];
        } elseif ($password !== $confirm_password) {
            $error_msg = "Passwords do not match.";
        } elseif (!$password_check['valid']) {
            $error_msg = "Password is not strong enough: " . implode(", ", $password_check['errors']);
        } elseif ($role !== 'player' && $role !== 'owner') {
            $error_msg = "Invalid role selected.";
        } else {
            try {
                // 3. Check for duplicates (Email OR Phone)
                $stmt = $conn->prepare("SELECT id, email, phone FROM users WHERE email = :email OR phone = :phone");
                $stmt->execute([
                    'email' => $email,
                    'phone' => $phone
                ]);
                
                $existing_user = $stmt->fetch();
                if ($existing_user) {
                    if (strtolower($existing_user['email']) === strtolower($email)) {
                        $error_msg = "An account with this email already exists.";
                    } else {
                        $error_msg = "An account with this phone number already exists.";
                    }
                } else {
                    // Start transaction
                    $conn->beginTransaction();
                    
                    // 4. Hash password and generate verification token
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $verification_token = generate_secure_token();
                    
                    // 5. Insert user (email_verified defaults to 0)
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, verification_token, verification_sent_at) VALUES (:name, :email, :password, :role, :phone, :token, CURRENT_TIMESTAMP)");
                    $stmt->execute([
                        'name' => $name,
                        'email' => $email,
                        'password' => $hashed_password,
                        'role' => $role,
                        'phone' => $phone,
                        'token' => $verification_token
                    ]);
                    
                    $user_id = $conn->lastInsertId();
                    
                    // 6. Role specific seeding
                    if ($role === 'player') {
                        $stmt_points = $conn->prepare("INSERT INTO reward_points (player_id, points) VALUES (:player_id, 100)");
                        $stmt_points->execute(['player_id' => $user_id]);
                    } else {
                        $stmt_owner = $conn->prepare("INSERT INTO owner_details (owner_id) VALUES (:owner_id)");
                        $stmt_owner->execute(['owner_id' => $user_id]);
                    }
                    
                    // Commit database changes
                    $conn->commit();
                    
                    // 7. Send Verification Email
                    $email_sent = send_verification_email($email, $name, $verification_token);
                    
                    if ($email_sent) {
                        set_flash_message("Registration successful! Please check your email to verify your account.", "success");
                    } else {
                        set_flash_message("Registration successful, but we couldn't send the verification email. Please use the resend verification page.", "warning");
                    }
                    
                    header("Location: login.php");
                    exit;
                }
            } catch (PDOException $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $error_msg = "Registration failed: " . $e->getMessage();
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
                <h2>Join the Futsal Community</h2>
                <p>Register as a player to book pitches, or as an owner to manage your courts and scale your business.</p>
                <div class="carousel-indicators-mini">
                    <span class="dot"></span>
                    <span class="dot active"></span>
                    <span class="dot"></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Registration Form -->
    <div class="auth-split-form-container">
        <div class="auth-split-form-box">
            <!-- Mobile header (hidden on desktop) -->
            <div class="mobile-auth-header">
                <a href="index.php" class="logo">
                    HAMRO<span>FUTSAL</span>
                </a>
                <a href="index.php" class="btn-back-website-mobile">&larr; Home</a>
            </div>
            
            <h2>Create Account</h2>
            <p class="subtitle">Already have an account? <a href="login.php">Login here</a></p>
            
            <!-- Display session flash notifications if set -->
            <?php display_flash_message(); ?>
            
            <?php if (!empty($error_msg)): ?>
                <div class="auth-error-box">
                    <?php echo sanitize($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="POST" id="registerForm" autocomplete="off">
                <!-- CSRF Token -->
                <?php csrf_input_field(); ?>
                
                <!-- Segmented Control for Role Selector -->
                <div class="form-group">
                    <label class="form-label">Register As</label>
                    <div class="segmented-control">
                        <div class="segmented-option">
                            <input type="radio" id="role-player" name="role" value="player" <?php echo ($role === 'player' || empty($role)) ? 'checked' : ''; ?>>
                            <label for="role-player">Player</label>
                        </div>
                        <div class="segmented-option">
                            <input type="radio" id="role-owner" name="role" value="owner" <?php echo ($role === 'owner') ? 'checked' : ''; ?>>
                            <label for="role-owner">Futsal Owner</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. John Doe" value="<?php echo sanitize($name); ?>" autocomplete="off" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="e.g. name@example.com" value="<?php echo sanitize($email); ?>" autocomplete="off" required>
                    <div id="email-msg" class="validation-hint"></div>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number (Nepal)</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="e.g. 9812345678" value="<?php echo sanitize($phone); ?>" autocomplete="off" required>
                    <div id="phone-msg" class="validation-hint"></div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Create a secure password" autocomplete="off" required>
                    <div id="password-strength-container" style="margin-top: 5px;"></div>
                    <div class="requirement-list" id="password-requirements" style="display: none; margin-top: 10px; font-size: 0.8rem;">
                        <div id="req-length" class="req-item">❌ At least 8 characters</div>
                        <div id="req-upper" class="req-item">❌ 1 uppercase letter</div>
                        <div id="req-lower" class="req-item">❌ 1 lowercase letter</div>
                        <div id="req-number" class="req-item">❌ 1 number</div>
                        <div id="req-special" class="req-item">❌ 1 special character (!@#$%^&*)</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Type your password again" autocomplete="off" required>
                    <div id="password-match-msg" class="validation-hint"></div>
                </div>
                
                <!-- Terms & Conditions checkbox matching reference image layout -->
                <div class="auth-split-checkbox-row">
                    <input type="checkbox" id="terms" required checked>
                    <label for="terms">I agree to the <a href="#">Terms & Conditions</a></label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" id="submitBtn">Register</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
