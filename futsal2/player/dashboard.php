<?php
// player/dashboard.php
// Main landing screen for logged-in players

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Restrict access: only allow players
require_role('player');

$player_id = $_SESSION['user_id'];

try {
    // 1. Fetch current reward points
    $stmt_points = $conn->prepare("SELECT points FROM reward_points WHERE player_id = :player_id");
    $stmt_points->execute(['player_id' => $player_id]);
    $reward_data = $stmt_points->fetch();
    $points_balance = $reward_data ? $reward_data['points'] : 0;
    
    // 2. Fetch stats: Total bookings
    $stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE player_id = :player_id");
    $stmt_total->execute(['player_id' => $player_id]);
    $total_bookings = $stmt_total->fetch()['total'];
    
    // 3. Fetch stats: Upcoming bookings (Today or in the future and not cancelled/rejected)
    $stmt_upcoming = $conn->prepare("SELECT COUNT(*) as upcoming FROM bookings WHERE player_id = :player_id AND booking_date >= CURDATE() AND status IN ('pending', 'approved')");
    $stmt_upcoming->execute(['player_id' => $player_id]);
    $upcoming_bookings = $stmt_upcoming->fetch()['upcoming'];
    
    // 4. Fetch recent bookings table
    $stmt_list = $conn->prepare("
        SELECT b.*, f.name as futsal_name, f.location 
        FROM bookings b 
        JOIN futsals f ON b.futsal_id = f.id 
        WHERE b.player_id = :player_id 
        ORDER BY b.booking_date DESC, b.start_time DESC 
        LIMIT 5
    ");
    $stmt_list->execute(['player_id' => $player_id]);
    $recent_bookings = $stmt_list->fetchAll();
} catch (PDOException $e) {
    die("Dashboard data query failed: " . $e->getMessage());
}

// Load custom header (which includes standard styling and checks folder depth)
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
            <li class="active">
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
            <div class="topbar-title">Player Control Panel</div>
            <div class="topbar-right">
                <span style="font-weight: 500; font-size: 0.92rem;">Welcome, <?php echo sanitize($_SESSION['user_name']); ?></span>
            </div>
        </header>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-content">
            <!-- Flash alert displays here if set -->
            <?php display_flash_message(); ?>

            <div style="margin-bottom: 25px;">
                <h2 style="font-size: 1.6rem; color: var(--primary-darkest);">Hello, <?php echo sanitize(explode(' ', $_SESSION['user_name'])[0]); ?>!</h2>
                <p class="text-muted">Manage your active futsal slots and track your squad's rewards.</p>
            </div>

            <!-- Stats Metric Widgets Grid -->
            <div class="stats-grid">
                <!-- Points Widget -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <span class="stat-card-label">Reward Points</span>
                        <span class="stat-card-value"><?php echo $points_balance; ?></span>
                    </div>
                    <div class="stat-card-icon">⭐</div>
                </div>

                <!-- Upcoming Match Bookings Widget -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <span class="stat-card-label">Upcoming Matches</span>
                        <span class="stat-card-value"><?php echo $upcoming_bookings; ?></span>
                    </div>
                    <div class="stat-card-icon">📅</div>
                </div>

                <!-- Total Match Reservations Widget -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <span class="stat-card-label">Total Bookings</span>
                        <span class="stat-card-value"><?php echo $total_bookings; ?></span>
                    </div>
                    <div class="stat-card-icon">⚽</div>
                </div>
            </div>

            <!-- Recent Bookings Table Area -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 1.15rem; margin: 0; color: var(--primary-darkest);">Recent Bookings</h3>
                    <a href="history.php" class="btn btn-outline btn-sm">View All History</a>
                </div>
                
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($recent_bookings)): ?>
                        <div style="text-align: center; padding: 40px;">
                            <p class="text-muted" style="font-size: 0.95rem; margin-bottom: 15px;">You have no active or historical bookings.</p>
                            <a href="search.php" class="btn btn-primary btn-sm">Find and Book Futsal</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="db-table">
                                <thead>
                                    <tr>
                                        <th>Futsal Court</th>
                                        <th>Location</th>
                                        <th>Date</th>
                                        <th>Time Slot</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_bookings as $b): ?>
                                        <tr>
                                            <td style="font-weight: 600;"><?php echo sanitize($b['futsal_name']); ?></td>
                                            <td><?php echo sanitize($b['location']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($b['booking_date'])); ?></td>
                                            <td style="font-weight: 500;">
                                                <?php 
                                                    echo date('h:i A', strtotime($b['start_time'])) . ' - ' . date('h:i A', strtotime($b['end_time'])); 
                                                ?>
                                            </td>
                                            <td>Rs. <?php echo number_format($b['total_price']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $b['status']; ?>">
                                                    <?php echo $b['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="history.php" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 0.8rem;">Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Loyalty Promo banner inside dashboard -->
            <div style="background-color: var(--light-green); border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 20px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
                <div>
                    <h4 style="color: var(--primary-darkest); margin-bottom: 4px; font-size: 1.05rem;">Play more, earn more rewards!</h4>
                    <p class="text-muted" style="font-size: 0.88rem; max-width: 600px; margin: 0;">Get 15 reward points automatically for every match you play. Accumulate points and build your squad's reputation on HAMROFUTSAL.</p>
                </div>
                <a href="search.php" class="btn btn-primary btn-sm">Find Ground</a>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
