<?php
// owner/profile.php
// Business credentials and profile manager for futsal owners

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Restrict access
require_role('owner');

$owner_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// 1. Process Personal Profile Details Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($name) || empty($phone)) {
        $error_msg = "Name and Phone fields cannot be empty.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE users SET name = :name, phone = :phone WHERE id = :id");
            $stmt->execute([
                'name' => $name,
                'phone' => $phone,
                'id' => $owner_id
            ]);
            
            // Update session values
            $_SESSION['user_name'] = $name;
            $_SESSION['user_phone'] = $phone;
            
            set_flash_message("Personal details updated successfully!", "success");
            header("Location: profile.php");
            exit;
        } catch (PDOException $e) {
            $error_msg = "Database update failed: " . $e->getMessage();
        }
    }
}

// 2. Process Business profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_business') {
    $business_name = trim($_POST['business_name'] ?? '');
    $pan_number = trim($_POST['pan_number'] ?? '');
    $bank_details = trim($_POST['bank_details'] ?? '');
    
    try {
        // Upsert style behavior: first check if row exists (owner_details has UNIQUE constraint)
        $stmt_check = $conn->prepare("SELECT id FROM owner_details WHERE owner_id = :owner_id");
        $stmt_check->execute(['owner_id' => $owner_id]);
        
        if ($stmt_check->fetch()) {
            $stmt_update = $conn->prepare("
                UPDATE owner_details 
                SET business_name = :business_name, pan_number = :pan_number, bank_details = :bank_details 
                WHERE owner_id = :owner_id
            ");
            $stmt_update->execute([
                'business_name' => $business_name,
                'pan_number' => $pan_number,
                'bank_details' => $bank_details,
                'owner_id' => $owner_id
            ]);
        } else {
            $stmt_insert = $conn->prepare("
                INSERT INTO owner_details (owner_id, business_name, pan_number, bank_details) 
                VALUES (:owner_id, :business_name, :pan_number, :bank_details)
            ");
            $stmt_insert->execute([
                'owner_id' => $owner_id,
                'business_name' => $business_name,
                'pan_number' => $pan_number,
                'bank_details' => $bank_details
            ]);
        }
        
        set_flash_message("Business settings and credentials saved successfully!", "success");
        header("Location: profile.php");
        exit;
    } catch (PDOException $e) {
        $error_msg = "Business update failed: " . $e->getMessage();
    }
}

// 3. Process password changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_msg = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "Confirm password does not match new password.";
    } elseif (strlen($new_password) < 6) {
        $error_msg = "New password must be at least 6 characters long.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute(['id' => $owner_id]);
            $user_pass = $stmt->fetch()['password'];
            
            if (password_verify($current_password, $user_pass)) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt_update = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt_update->execute([
                    'password' => $new_hash,
                    'id' => $owner_id
                ]);
                
                set_flash_message("Password has been changed successfully!", "success");
                header("Location: profile.php");
                exit;
            } else {
                $error_msg = "The current password you entered is incorrect.";
            }
        } catch (PDOException $e) {
            $error_msg = "Password update failed: " . $e->getMessage();
        }
    }
}

// 4. Query current details from users and owner_details
try {
    $stmt_user = $conn->prepare("SELECT name, email, phone, created_at FROM users WHERE id = :id");
    $stmt_user->execute(['id' => $owner_id]);
    $user_details = $stmt_user->fetch();
    
    if (!$user_details) {
        // Force logout if session ID is stale/desynced from the database (e.g. installer was re-run)
        header("Location: ../logout.php");
        exit;
    }
    
    $stmt_bus = $conn->prepare("SELECT business_name, pan_number, bank_details FROM owner_details WHERE owner_id = :owner_id");
    $stmt_bus->execute(['owner_id' => $owner_id]);
    $business_details = $stmt_bus->fetch();
    
    $b_name = $business_details ? $business_details['business_name'] : '';
    $b_pan = $business_details ? $business_details['pan_number'] : '';
    $b_bank = $business_details ? $business_details['bank_details'] : '';
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

require_once '../includes/header.php';
?>

<div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="../index.php" class="logo">HAMRO<span>FUTSAL</span></a>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-user-name"><?php echo sanitize($_SESSION['user_name']); ?></div>
            <div class="sidebar-user-role">🛡️ Owner</div>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php">
                    <svg viewBox="0 0 24 24"><path d="M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z"/></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="futsals.php">
                    <svg viewBox="0 0 24 24"><path d="M19,4H5A2,2 0 0,0 3,6V18A2,2 0 0,0 5,20H19A2,2 0 0,0 21,18V6A2,2 0 0,0 19,4M19,18H5V8H19V18Z"/></svg>
                    Manage Grounds
                </a>
            </li>
            <li>
                <a href="bookings.php">
                    <svg viewBox="0 0 24 24"><path d="M19,19H5V8H19M19,3H14V1H10V3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3Z"/></svg>
                    Reservations
                </a>
            </li>
            <li>
                <a href="stats.php">
                    <svg viewBox="0 0 24 24"><path d="M16,6V18H18V6H16M12,10V18H14V10H12M8,14V18H10V14H8M4,18H6V22H4V18M12,2A10,10 0 1,1 2,12A10,10 0 0,1 12,2Z"/></svg>
                    Revenue Stats
                </a>
            </li>
            <li class="active">
                <a href="profile.php">
                    <svg viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
                    Business Profile
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <a href="../logout.php">
                <svg style="width:20px;height:20px;fill:currentColor" viewBox="0 0 24 24"><path d="M16,17V14H9V10H16V7L21,12L16,17M14,2A2,2 0 0,1 16,4V6H14V4H5V20H14V18H16V20A2,2 0 0,1 14,22H5A2,2 0 0,1 3,20V4A2,2 0 0,1 5,2H14Z"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content Frame -->
    <main class="dashboard-main">
        <!-- Topbar Panel -->
        <header class="dashboard-topbar">
            <button class="hamburger-btn" id="dashboard-hamburger" onclick="document.getElementById('sidebar').classList.toggle('active')">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="topbar-title">Manage Business Profile</div>
            <div class="topbar-right">
                <span style="font-weight: 500; font-size: 0.92rem;">Owner: <?php echo sanitize($_SESSION['user_name']); ?></span>
            </div>
        </header>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-content">
            <?php display_flash_message(); ?>
            
            <?php if (!empty($error_msg)): ?>
                <div style="background-color: #fce8e6; color: #c5221f; border: 1px solid #c5221f; padding: 12px 16px; border-radius: 8px; margin-bottom: 25px; font-weight: 500; font-size: 0.95rem;">
                    <?php echo sanitize($error_msg); ?>
                </div>
            <?php endif; ?>

            <!-- Grid Layout of Form Cards -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: start; flex-wrap: wrap; margin-bottom: 30px;">
                
                <!-- Left Column: Personal details form -->
                <div class="card" style="padding: 25px;">
                    <h3 style="font-size: 1.15rem; color: var(--primary-darkest); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Personal Credentials</h3>
                    
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address (Cannot change)</label>
                            <input type="email" id="email" class="form-control" value="<?php echo sanitize($user_details['email']); ?>" style="background-color: var(--background-color); cursor: not-allowed;" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo sanitize($user_details['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo sanitize($user_details['phone']); ?>" required>
                        </div>
                        
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">
                            Registered on: <?php echo date('M d, Y', strtotime($user_details['created_at'])); ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
                
                <!-- Right Column: Business Profile Form -->
                <div class="card" style="padding: 25px;">
                    <h3 style="font-size: 1.15rem; color: var(--primary-darkest); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Business Settings</h3>
                    
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="action" value="update_business">
                        
                        <div class="form-group">
                            <label for="business_name" class="form-label">Business Name</label>
                            <input type="text" id="business_name" name="business_name" class="form-control" placeholder="e.g. Elite Sports Arena Pvt. Ltd." value="<?php echo sanitize($b_name); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="pan_number" class="form-label">PAN Number</label>
                            <input type="text" id="pan_number" name="pan_number" class="form-control" placeholder="9-digit tax number" value="<?php echo sanitize($b_pan); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="bank_details" class="form-label">Bank Billing Account Details</label>
                            <textarea id="bank_details" name="bank_details" class="form-control" rows="3" placeholder="e.g. Bank Name - A/C Holder - Account Number"><?php echo sanitize($b_bank); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Business Details</button>
                    </form>
                </div>
            </div>

            <!-- Bottom Row: Change Password Form -->
            <div class="card" style="padding: 25px; max-width: 600px;">
                <h3 style="font-size: 1.15rem; color: var(--primary-darkest); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Change Password</h3>
                
                <form action="profile.php" method="POST">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter current password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="At least 6 characters" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
