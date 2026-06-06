<?php
// player/history.php
// Lists user's reservations and processes booking cancellations with late penalties

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Restrict access to players
require_role('player');

$player_id = $_SESSION['user_id'];

// 1. Process Booking Cancellation Action
if (isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    
    try {
        // Fetch booking to verify ownership and evaluate timestamps
        $stmt_booking = $conn->prepare("
            SELECT b.*, f.name as futsal_name 
            FROM bookings b 
            JOIN futsals f ON b.futsal_id = f.id 
            WHERE b.id = :id AND b.player_id = :player_id
        ");
        $stmt_booking->execute(['id' => $booking_id, 'player_id' => $player_id]);
        $booking = $stmt_booking->fetch();
        
        if (!$booking) {
            set_flash_message("Booking record not found.", "danger");
        } elseif ($booking['status'] === 'cancelled') {
            set_flash_message("This booking has already been cancelled.", "warning");
        } elseif ($booking['status'] === 'rejected') {
            set_flash_message("You cannot cancel a booking that was rejected by the owner.", "warning");
        } else {
            // Start a SQL Transaction to ensure booking state and point changes sync perfectly
            $conn->beginTransaction();
            
            // Calculate time difference between slot time and current time
            $slot_datetime_str = $booking['booking_date'] . ' ' . $booking['start_time'];
            $slot_timestamp = strtotime($slot_datetime_str);
            $current_timestamp = time(); // Server current time
            
            // Calculate difference in hours
            $hours_difference = ($slot_timestamp - $current_timestamp) / 3600;
            
            $points_deducted = 0;
            $applied_penalty = false;
            
            // Determine if the cancellation is "last-minute" (within 6 hours)
            if ($hours_difference < 6) {
                $applied_penalty = true;
                $points_deducted = 20; // 20 points penalty for late cancellations
                
                // Fetch player's current reward points
                $stmt_points = $conn->prepare("SELECT points FROM reward_points WHERE player_id = :player_id");
                $stmt_points->execute(['player_id' => $player_id]);
                $points_row = $stmt_points->fetch();
                $current_points = $points_row ? $points_row['points'] : 0;
                
                // Calculate new points (cannot go below 0)
                $new_points = max(0, $current_points - $points_deducted);
                
                // Update player's reward points balance
                $stmt_update_points = $conn->prepare("UPDATE reward_points SET points = :points WHERE player_id = :player_id");
                $stmt_update_points->execute([
                    'points' => $new_points,
                    'player_id' => $player_id
                ]);
            }
            
            // Update booking status to 'cancelled' and log point modification
            $stmt_cancel = $conn->prepare("
                UPDATE bookings 
                SET status = 'cancelled', reward_points_modified = :points_modified 
                WHERE id = :id
            ");
            $stmt_cancel->execute([
                'points_modified' => -$points_deducted, // Log negative value indicating deduction
                'id' => $booking_id
            ]);
            
            $conn->commit();
            
            // Formulate beautiful response notifications
            if ($applied_penalty) {
                set_flash_message(
                    "⚠️ Booking for '{$booking['futsal_name']}' has been cancelled. Because you cancelled within 6 hours of the schedule, a penalty of {$points_deducted} points was deducted from your rewards.",
                    "warning"
                );
            } else {
                set_flash_message(
                    "✔ Booking for '{$booking['futsal_name']}' has been cancelled successfully. No penalty was applied because you cancelled in advance.",
                    "success"
                );
            }
        }
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        set_flash_message("Failed to process cancellation: " . $e->getMessage(), "danger");
    }
    
    // Refresh page to show updated history table
    header("Location: history.php");
    exit;
}

// 2. Fetch all historical bookings for this player
try {
    $stmt = $conn->prepare("
        SELECT b.*, f.name as futsal_name, f.location, f.contact_number 
        FROM bookings b 
        JOIN futsals f ON b.futsal_id = f.id 
        WHERE b.player_id = :player_id 
        ORDER BY b.booking_date DESC, b.start_time DESC
    ");
    $stmt->execute(['player_id' => $player_id]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query error: " . $e->getMessage());
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
            <li class="active">
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
            <div class="topbar-title">My Booking History</div>
            <div class="topbar-right">
                <span style="font-weight: 500; font-size: 0.92rem;">Welcome, <?php echo sanitize($_SESSION['user_name']); ?></span>
            </div>
        </header>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-content">
            <?php display_flash_message(); ?>

            <div class="card" style="padding: 0;">
                <div class="card-header" style="background-color: #ffffff; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.15rem; margin: 0; color: var(--primary-darkest);">Futsal Reservations</h3>
                </div>
                
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($bookings)): ?>
                        <div style="text-align: center; padding: 50px;">
                            <p class="text-muted" style="font-size: 1.05rem; margin-bottom: 15px;">You do not have any historical bookings yet.</p>
                            <a href="search.php" class="btn btn-primary btn-sm">Find and Book Futsal Ground</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="db-table">
                                <thead>
                                    <tr>
                                        <th>Futsal Arena</th>
                                        <th>Location</th>
                                        <th>Date</th>
                                        <th>Time Slot</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Points Log</th>
                                        <th style="text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $b): ?>
                                        <tr>
                                            <td style="font-weight: 600;"><?php echo sanitize($b['futsal_name']); ?></td>
                                            <td><?php echo sanitize($b['location']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($b['booking_date'])); ?></td>
                                            <td style="font-weight: 500;">
                                                <?php 
                                                    echo date('h:i A', strtotime($b['start_time'])) . ' - ' . date('h:i A', strtotime($b['end_time'])); 
                                                ?>
                                            </td>
                                            <td style="font-weight: 600; color: var(--text-main);">Rs. <?php echo number_format($b['total_price']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $b['status']; ?>">
                                                    <?php echo $b['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                    $mod = $b['reward_points_modified'];
                                                    if ($mod > 0) {
                                                        echo "<span class='text-success' style='font-weight:600;'>+{$mod} XP</span>";
                                                    } elseif ($mod < 0) {
                                                        echo "<span class='text-danger' style='font-weight:600;'>{$mod} XP</span>";
                                                    } else {
                                                        echo "<span class='text-muted'>--</span>";
                                                    }
                                                ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php if ($b['status'] === 'pending' || $b['status'] === 'approved'): ?>
                                                    <!-- Simple form post for secure state transition -->
                                                    <form action="history.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking? If you cancel within 6 hours of the match, 20 reward points will be deducted as a late cancellation penalty.');" style="display: inline-block;">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 4px 10px; font-size: 0.8rem; font-weight: 500;">Cancel</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="font-size: 0.85rem; color: var(--text-muted);">No Action</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Helpful user tips about cancellation policies -->
            <div class="card" style="margin-top: 30px; padding: 20px; border-left: 4px solid var(--primary-color);">
                <h4 style="color: var(--primary-darkest); margin-bottom: 8px;">💡 Futsal Booking Tips</h4>
                <ul style="font-size: 0.88rem; color: var(--text-muted); padding-left: 20px; list-style: disc; display: flex; flex-direction: column; gap: 6px;">
                    <li><strong>Pending Bookings:</strong> The futsal owner will review and approve your slot. You will gain <strong>+15 Reward Points</strong> when approved!</li>
                    <li><strong>Early Cancellation:</strong> You can cancel any reservation free of penalty if you do so at least <strong>6 hours before</strong> the start time.</li>
                    <li><strong>Late Cancellation:</strong> Canceling within 6 hours of your booked match is penalized by a deduction of <strong>20 Reward Points</strong> from your balance.</li>
                </ul>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
