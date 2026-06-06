<?php
// player/profile.php
// Profile editor for players: edit name, phone number, and change passwords securely

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Restrict access
require_role('player');

$player_id = $_SESSION['user_id'];
$error_msg = "";
$success_msg = "";

// 1. Process profile details updates
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
                'id' => $player_id
            ]);
            
            // Update session values
            $_SESSION['user_name'] = $name;
            $_SESSION['user_phone'] = $phone;
            
            set_flash_message("Your profile details have been updated successfully!", "success");
            header("Location: profile.php");
            exit;
        } catch (PDOException $e) {
            $error_msg = "Database update failed: " . $e->getMessage();
        }
    }
}

// 2. Process password changes
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
            // Retrieve current password hash from DB
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute(['id' => $player_id]);
            $user_pass = $stmt->fetch()['password'];
            
            if (password_verify($current_password, $user_pass)) {
                // Hash new password securely
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt_update = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt_update->execute([
                    'password' => $new_hash,
                    'id' => $player_id
                ]);
                
                set_flash_message("Your account password has been changed successfully!", "success");
                header("Location: profile.php");
                exit;
            } else {
                $error_msg = "The current password you entered is incorrect.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database update failed: " . $e->getMessage();
        }
    }
}

// 3. Fetch latest details
try {
    $stmt = $conn->prepare("SELECT name, email, phone, created_at FROM users WHERE id = :id");
    $stmt->execute(['id' => $player_id]);
    $user_details = $stmt->fetch();
    
    if (!$user_details) {
        // Force logout if session ID is stale/desynced from the database
        header("Location: ../logout.php");
        exit;
    }
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
            <div class="sidebar-user-role">⚽ Player</div>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php">
                    <svg viewBox="0 0 24 24"><path d="M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z"/></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="search.php">
                    <svg viewBox="0 0 24 24"><path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z"/></svg>
                    Book Futsal
                </a>
            </li>
            <li>
                <a href="history.php">
                    <svg viewBox="0 0 24 24"><path d="M13.5,8H12V13L16.28,15.54L17,14.33L13.5,12.25V8M13,3A9,9 0 0,0 4,12H1L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3Z"/></svg>
                    Booking History
                </a>
            </li>
            <li>
                <a href="rewards.php">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/></svg>
                    Reward Points
                </a>
            </li>
            <li class="active">
                <a href="profile.php">
                    <svg viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
                    My Profile
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
            <div class="topbar-title">Manage Profile</div>
            <div class="topbar-right">
                <span style="font-weight: 500; font-size: 0.92rem;">Welcome, <?php echo sanitize($_SESSION['user_name']); ?></span>
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

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: start; flex-wrap: wrap;">
                
                <!-- Left Side: Profile Details form -->
                <div class="card" style="padding: 25px;">
                    <h3 style="font-size: 1.15rem; color: var(--primary-darkest); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Personal Details</h3>
                    
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
                
                <!-- Right Side: Change Password form -->
                <div class="card" style="padding: 25px;">
                    <h3 style="font-size: 1.15rem; color: var(--primary-darkest); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Change Password</h3>
                    
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter current password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
                
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
