<?php
// player/rewards.php
// Loyalty rewards information panel displaying points balances and point logs

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Restrict access
require_role('player');

$player_id = $_SESSION['user_id'];

try {
    // 1. Fetch current reward points
    $stmt_points = $conn->prepare("SELECT points FROM reward_points WHERE player_id = :player_id");
    $stmt_points->execute(['player_id' => $player_id]);
    $reward_row = $stmt_points->fetch();
    $points_balance = $reward_row ? $reward_row['points'] : 0;
    
    // 2. Fetch point history log (any bookings where reward points were modified)
    $stmt_log = $conn->prepare("
        SELECT b.*, f.name as futsal_name 
        FROM bookings b 
        JOIN futsals f ON b.futsal_id = f.id 
        WHERE b.player_id = :player_id AND b.reward_points_modified != 0 
        ORDER BY b.booking_date DESC, b.start_time DESC
    ");
    $stmt_log->execute(['player_id' => $player_id]);
    $points_logs = $stmt_log->fetchAll();
    
    // 3. Fetch registration date to show signup points award
    $stmt_user = $conn->prepare("SELECT created_at FROM users WHERE id = :id");
    $stmt_user->execute(['id' => $player_id]);
    $user_row = $stmt_user->fetch();
    
    if (!$user_row) {
        // Force logout if session ID is stale/desynced from the database
        header("Location: ../logout.php");
        exit;
    }
    
    $signup_date = $user_row['created_at'];
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
            <li class="active">
                <a href="rewards.php">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/></svg>
                    Reward Points
                </a>
            </li>
            <li>
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
            <div class="topbar-title">Loyalty Reward Center</div>
            <div class="topbar-right">
                <span style="font-weight: 500; font-size: 0.92rem;">Welcome, <?php echo sanitize($_SESSION['user_name']); ?></span>
            </div>
        </header>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-content">
            <?php display_flash_message(); ?>

            <!-- Scorecard and Rulebook Grid Layout -->
            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; align-items: start; margin-bottom: 30px; flex-wrap: wrap;">
                
                <!-- Left Side: Massive Points Balance Card -->
                <div class="card" style="padding: 30px; text-align: center; background-color: var(--light-green); border-color: var(--primary-color);">
                    <h3 style="color: var(--primary-darkest); font-size: 1.15rem; margin-bottom: 15px;">Your Total Balance</h3>
                    <div style="width: 140px; height: 140px; border-radius: 50%; background-color: #ffffff; border: 4px solid var(--primary-color); display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; box-shadow: var(--box-shadow);">
                        <span style="font-size: 2.75rem; font-weight: 700; color: var(--primary-color);"><?php echo $points_balance; ?></span>
                    </div>
                    <h4 style="margin-bottom: 5px; color: var(--primary-darkest);">Reward Points (XP)</h4>
                    <p class="text-muted" style="font-size: 0.85rem;">Keep booking matches to increase your loyalty ranking.</p>
                </div>
                
                <!-- Right Side: Rules Guide Cards -->
                <div class="card" style="padding: 25px;">
                    <h3 style="font-size: 1.25rem; color: var(--primary-darkest); margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">How Rewards Work</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div style="display: flex; gap: 12px; align-items: start;">
                            <span style="font-size: 1.25rem; background: var(--light-green); border-radius: 50%; width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; flex-shrink: 0; color: var(--primary-color);">✔</span>
                            <div>
                                <strong style="display: block; font-size: 0.92rem;">Registration Bonus (+100 XP)</strong>
                                <span class="text-muted" style="font-size: 0.85rem;">Every player receives 100 loyalty points automatically upon creating an account!</span>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 12px; align-items: start;">
                            <span style="font-size: 1.25rem; background: var(--light-green); border-radius: 50%; width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; flex-shrink: 0; color: var(--primary-color);">🔥</span>
                            <div>
                                <strong style="display: block; font-size: 0.92rem;">Successful Booking Reward (+15 XP)</strong>
                                <span class="text-muted" style="font-size: 0.85rem;">Get +15 points credited automatically as soon as the futsal owner approves your pending booking request!</span>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 12px; align-items: start;">
                            <span style="font-size: 1.25rem; background: #fee2e2; border-radius: 50%; width: 35px; height: 35px; display: flex; justify-content: center; align-items: center; flex-shrink: 0; color: var(--danger-color);">⚠️</span>
                            <div>
                                <strong style="display: block; font-size: 0.92rem; color: #dc2626;">Late Cancellation Penalty (-20 XP)</strong>
                                <span class="text-muted" style="font-size: 0.85rem;">Canceling a scheduled booking within 6 hours of the match start time automatically subtracts 20 points from your balance.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Logs Table -->
            <div class="card" style="padding: 0;">
                <div class="card-header" style="background-color: #ffffff; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.15rem; margin: 0; color: var(--primary-darkest);">Reward Transaction Log</h3>
                </div>
                
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="db-table">
                            <thead>
                                <tr>
                                    <th>Event Date</th>
                                    <th>Activity Description</th>
                                    <th>Status Trigger</th>
                                    <th>Points Change</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Seed row representing account signup -->
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($signup_date)); ?></td>
                                    <td style="font-weight: 500;">Welcome Signup Bonus</td>
                                    <td><span class="status-badge status-approved">Active</span></td>
                                    <td><span class="text-success" style="font-weight: 600;">+100 XP</span></td>
                                </tr>
                                
                                <!-- Dynamic logs queried from user's booking transactions -->
                                <?php if (!empty($points_logs)): ?>
                                    <?php foreach ($points_logs as $log): ?>
                                        <tr>
                                            <td><?php echo date('d M Y', strtotime($log['booking_date'])); ?></td>
                                            <td>
                                                Booking for court <b><?php echo sanitize($log['futsal_name']); ?></b>
                                                (Slot: <?php echo date('h:i A', strtotime($log['start_time'])); ?>)
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $log['status']; ?>">
                                                    <?php echo $log['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                    $mod = $log['reward_points_modified'];
                                                    if ($mod > 0) {
                                                        echo "<span class='text-success' style='font-weight:600;'>+{$mod} XP</span>";
                                                    } else {
                                                        echo "<span class='text-danger' style='font-weight:600;'>{$mod} XP</span>";
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
