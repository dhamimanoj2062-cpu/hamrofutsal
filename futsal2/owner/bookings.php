<?php
// owner/bookings.php
// Manages customer reservations, processes approvals/rejections, and credits reward points

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Restrict access
require_role('owner');

$owner_id = $_SESSION['user_id'];

// 1. Process Approve or Reject Post-Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    $action = $_POST['action'];
    
    try {
        // Verify booking belongs to one of the owner's futsal courts
        $stmt_booking = $conn->prepare("
            SELECT b.*, f.name as futsal_name, u.name as player_name 
            FROM bookings b 
            JOIN futsals f ON b.futsal_id = f.id 
            JOIN users u ON b.player_id = u.id
            WHERE b.id = :id AND f.owner_id = :owner_id
        ");
        $stmt_booking->execute(['id' => $booking_id, 'owner_id' => $owner_id]);
        $booking = $stmt_booking->fetch();
        
        if (!$booking) {
            set_flash_message("Booking record not found or permission denied.", "danger");
        } elseif ($booking['status'] !== 'pending') {
            set_flash_message("This booking is already processed (Status: {$booking['status']}).", "warning");
        } else {
            // Start SQL transaction
            $conn->beginTransaction();
            
            if ($action === 'approve') {
                // Award points only if points weren't modified yet (double credit prevention)
                $points_to_award = 15; // Standard approval credit
                $is_frequent_player = false;
                
                // Count historical approved bookings for this player to calculate frequent player bonus
                $stmt_hist = $conn->prepare("SELECT COUNT(*) as approved_count FROM bookings WHERE player_id = :player_id AND status = 'approved'");
                $stmt_hist->execute(['player_id' => $booking['player_id']]);
                $approved_count = intval($stmt_hist->fetch()['approved_count']);
                
                // Every 5th approved booking (e.g. 4 previously approved + this 1 = 5th) gets +20 extra points
                // So if ($approved_count + 1) % 5 === 0, player earns the frequent bonus!
                if (($approved_count + 1) % 5 === 0) {
                    $points_to_award += 20; // +20 points frequent bonus
                    $is_frequent_player = true;
                }
                
                // Update player's reward points balance
                // First ensure player has a record in reward_points (initialized at register, but safe fallback)
                $stmt_init = $conn->prepare("INSERT IGNORE INTO reward_points (player_id, points) VALUES (:player_id, 100)");
                $stmt_init->execute(['player_id' => $booking['player_id']]);
                
                $stmt_add_points = $conn->prepare("UPDATE reward_points SET points = points + :points WHERE player_id = :player_id");
                $stmt_add_points->execute([
                    'points' => $points_to_award,
                    'player_id' => $booking['player_id']
                ]);
                
                // Update booking status to 'approved' and log points awarded
                $stmt_update = $conn->prepare("UPDATE bookings SET status = 'approved', reward_points_modified = :points WHERE id = :id");
                $stmt_update->execute([
                    'points' => $points_to_award,
                    'id' => $booking_id
                ]);
                
                $conn->commit();
                
                $bonus_msg = $is_frequent_player ? " (Frequent Player Bonus of +20 XP included!)" : "";
                set_flash_message("✔ Booking for '{$booking['player_name']}' approved successfully! Player awarded +{$points_to_award} XP{$bonus_msg}.", "success");
                
            } elseif ($action === 'reject') {
                // Update status to 'rejected'
                $stmt_update = $conn->prepare("UPDATE bookings SET status = 'rejected', reward_points_modified = 0 WHERE id = :id");
                $stmt_update->execute(['id' => $booking_id]);
                
                $conn->commit();
                set_flash_message("❌ Booking request for '{$booking['player_name']}' has been rejected.", "info");
            }
        }
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        set_flash_message("Action failed: " . $e->getMessage(), "danger");
    }
    
    header("Location: bookings.php");
    exit;
}

// 2. Fetch all bookings for all futsal grounds belonging to this owner
try {
    $stmt = $conn->prepare("
        SELECT b.*, f.name as futsal_name, u.name as player_name, u.phone as player_phone, rp.points as player_total_points
        FROM bookings b 
        JOIN futsals f ON b.futsal_id = f.id 
        JOIN users u ON b.player_id = u.id
        LEFT JOIN reward_points rp ON b.player_id = rp.player_id
        WHERE f.owner_id = :owner_id 
        ORDER BY b.booking_date DESC, b.start_time DESC
    ");
    $stmt->execute(['owner_id' => $owner_id]);
    $bookings = $stmt->fetchAll();
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
            <li class="active">
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
            <li>
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
            <div class="topbar-title">Manage Futsal Reservations</div>
            <div class="topbar-right">
                <span style="font-weight: 500; font-size: 0.92rem;">Owner: <?php echo sanitize($_SESSION['user_name']); ?></span>
            </div>
        </header>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-content">
            <?php display_flash_message(); ?>

            <div class="card" style="padding: 0;">
                <div class="card-header" style="background-color: #ffffff; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.15rem; margin: 0; color: var(--primary-darkest);">Futsal Bookings Ledger</h3>
                </div>
                
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($bookings)): ?>
                        <div style="text-align: center; padding: 50px;">
                            <p class="text-muted" style="font-size: 1.05rem; margin-bottom: 15px;">No bookings have been made for your grounds yet.</p>
                            <a href="futsals.php" class="btn btn-primary btn-sm">Manage Futsal Courts</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="db-table">
                                <thead>
                                    <tr>
                                        <th>Player Details</th>
                                        <th>Customer Points</th>
                                        <th>Futsal Court</th>
                                        <th>Booking Date</th>
                                        <th>Time Slot</th>
                                        <th>Subtotal</th>
                                        <th>Status</th>
                                        <th>Points Log</th>
                                        <th style="text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $b): ?>
                                        <tr>
                                            <td>
                                                <strong style="display: block; font-size: 0.95rem;"><?php echo sanitize($b['player_name']); ?></strong>
                                                <span class="text-muted" style="font-size: 0.82rem;">📞 <?php echo sanitize($b['player_phone']); ?></span>
                                            </td>
                                            <td style="font-weight: 600; color: var(--warning-color);">
                                                ⭐ <?php echo intval($b['player_total_points']); ?> XP
                                            </td>
                                            <td style="font-weight: 500;"><?php echo sanitize($b['futsal_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($b['booking_date'])); ?></td>
                                            <td style="font-weight: 500;">
                                                <?php 
                                                    echo date('h:i A', strtotime($b['start_time'])) . ' - ' . date('h:i A', strtotime($b['end_time'])); 
                                                ?>
                                            </td>
                                            <td style="font-weight: 700;">Rs. <?php echo number_format($b['total_price']); ?></td>
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
                                                <?php if ($b['status'] === 'pending'): ?>
                                                    <div class="table-actions" style="justify-content: center;">
                                                        <form action="bookings.php" method="POST" style="display: inline-block;">
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                            <button type="submit" class="btn btn-primary btn-sm" style="padding: 4px 10px; font-size: 0.8rem; font-weight: 500;">Approve</button>
                                                        </form>
                                                        
                                                        <form action="bookings.php" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to reject this booking request?');">
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" style="padding: 4px 10px; font-size: 0.8rem; font-weight: 500;">Reject</button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="font-size: 0.85rem; color: var(--text-muted);">Processed</span>
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
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
