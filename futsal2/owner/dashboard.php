<?php
// owner/dashboard.php
// Business hub for futsal owners

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Restrict access
require_role('owner');

$owner_id = $_SESSION['user_id'];

try {
    // 1. Count total futsals managed by this owner
    $stmt_count = $conn->prepare("SELECT COUNT(*) as total_courts FROM futsals WHERE owner_id = :owner_id");
    $stmt_count->execute(['owner_id' => $owner_id]);
    $total_courts = $stmt_count->fetch()['total_courts'];
    
    // 2. Fetch pending bookings count for all futsals owned by this owner
    $stmt_pending = $conn->prepare("
        SELECT COUNT(*) as pending_count 
        FROM bookings b 
        JOIN futsals f ON b.futsal_id = f.id 
        WHERE f.owner_id = :owner_id AND b.status = 'pending'
    ");
    $stmt_pending->execute(['owner_id' => $owner_id]);
    $pending_count = $stmt_pending->fetch()['pending_count'];
    
    // 3. Fetch approved bookings count
    $stmt_approved = $conn->prepare("
        SELECT COUNT(*) as approved_count 
        FROM bookings b 
        JOIN futsals f ON b.futsal_id = f.id 
        WHERE f.owner_id = :owner_id AND b.status = 'approved'
    ");
    $stmt_approved->execute(['owner_id' => $owner_id]);
    $approved_count = $stmt_approved->fetch()['approved_count'];
    
    // 4. Calculate total revenue (Sum of total_price for all approved bookings)
    $stmt_revenue = $conn->prepare("
        SELECT SUM(b.total_price) as earnings 
        FROM bookings b 
        JOIN futsals f ON b.futsal_id = f.id 
        WHERE f.owner_id = :owner_id AND b.status = 'approved'
    ");
    $stmt_revenue->execute(['owner_id' => $owner_id]);
    $earnings_raw = $stmt_revenue->fetch()['earnings'];
    $total_earnings = $earnings_raw ? floatval($earnings_raw) : 0.0;
    
    // 5. Fetch actual pending bookings list for table view
    $stmt_list = $conn->prepare("
        SELECT b.*, f.name as futsal_name, u.name as player_name, u.phone as player_phone 
        FROM bookings b 
        JOIN futsals f ON b.futsal_id = f.id 
        JOIN users u ON b.player_id = u.id 
        WHERE f.owner_id = :owner_id AND b.status = 'pending' 
        ORDER BY b.booking_date ASC, b.start_time ASC
    ");
    $stmt_list->execute(['owner_id' => $owner_id]);
    $pending_bookings = $stmt_list->fetchAll();
    
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
            <li class="active">
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
            <div class="topbar-title">Owner Administration Hub</div>
            <div class="topbar-right">
                <span style="font-weight: 500; font-size: 0.92rem;">Owner: <?php echo sanitize($_SESSION['user_name']); ?></span>
            </div>
        </header>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-content">
            <?php display_flash_message(); ?>

            <div style="margin-bottom: 25px;">
                <h2 style="font-size: 1.6rem; color: var(--primary-darkest);">Business Overview</h2>
                <p class="text-muted">Monitor court schedule capacities, approve booking requests, and track financial earnings.</p>
            </div>

            <!-- Business Stat Widgets Grid -->
            <div class="stats-grid">
                <!-- Total Grounds Widget -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <span class="stat-card-label">Grounds Managed</span>
                        <span class="stat-card-value"><?php echo $total_courts; ?></span>
                    </div>
                    <div class="stat-card-icon">🏟️</div>
                </div>

                <!-- Pending Approvals Widget -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <span class="stat-card-label">Pending Requests</span>
                        <span class="stat-card-value"><?php echo $pending_count; ?></span>
                    </div>
                    <div class="stat-card-icon warning">⏳</div>
                </div>

                <!-- Confirmed bookings Widget -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <span class="stat-card-label">Approved Matches</span>
                        <span class="stat-card-value"><?php echo $approved_count; ?></span>
                    </div>
                    <div class="stat-card-icon">✔</div>
                </div>

                <!-- Total Revenue Widget -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <span class="stat-card-label">Total Revenue</span>
                        <span class="stat-card-value" style="font-size: 1.55rem; white-space: nowrap;">Rs. <?php echo number_format($total_earnings); ?></span>
                    </div>
                    <div class="stat-card-icon">💰</div>
                </div>
            </div>

            <!-- Pending Bookings list table -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 1.15rem; margin: 0; color: var(--primary-darkest);">Pending Booking Requests</h3>
                    <a href="bookings.php" class="btn btn-outline btn-sm">View All Bookings</a>
                </div>
                
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($pending_bookings)): ?>
                        <div style="text-align: center; padding: 40px;">
                            <p class="text-muted" style="font-size: 0.95rem; margin-bottom: 10px;">No pending booking requests at the moment.</p>
                            <p style="font-size: 0.85rem;">When players book your grounds online, their requests will appear here for review.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="db-table">
                                <thead>
                                    <tr>
                                        <th>Player Name</th>
                                        <th>Contact</th>
                                        <th>Futsal Court</th>
                                        <th>Booking Date</th>
                                        <th>Time Slot</th>
                                        <th>Hourly Pricing</th>
                                        <th style="text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_bookings as $b): ?>
                                        <tr>
                                            <td style="font-weight: 600;"><?php echo sanitize($b['player_name']); ?></td>
                                            <td><?php echo sanitize($b['player_phone']); ?></td>
                                            <td style="font-weight: 500;"><?php echo sanitize($b['futsal_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($b['booking_date'])); ?></td>
                                            <td style="font-weight: 600; color: var(--primary-color);">
                                                <?php 
                                                    echo date('h:i A', strtotime($b['start_time'])) . ' - ' . date('h:i A', strtotime($b['end_time'])); 
                                                ?>
                                            </td>
                                            <td style="font-weight: 600;">Rs. <?php echo number_format($b['total_price']); ?></td>
                                            <td style="text-align: center;">
                                                <div class="table-actions" style="justify-content: center;">
                                                    <!-- Approve Button Link Form -->
                                                    <form action="bookings.php" method="POST" style="display: inline-block;">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm" style="padding: 5px 12px; font-size: 0.8rem; font-weight: 600; border-radius: var(--border-radius-sm);">Approve</button>
                                                    </form>
                                                    <!-- Reject Button Link Form -->
                                                    <form action="bookings.php" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to reject this booking request?');">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 5px 12px; font-size: 0.8rem; font-weight: 600; border-radius: var(--border-radius-sm);">Reject</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Link shortcut block -->
            <div style="background-color: var(--light-green); border: 1px solid var(--border-color); border-radius: var(--border-radius); padding: 20px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
                <div>
                    <h4 style="color: var(--primary-darkest); margin-bottom: 4px; font-size: 1.05rem;">Need to add another futsal field?</h4>
                    <p class="text-muted" style="font-size: 0.88rem; max-width: 600px; margin: 0;">Add new court locations, describe court amenities, and update pricing profiles anytime.</p>
                </div>
                <a href="futsal_add.php" class="btn btn-primary btn-sm">Add New Court</a>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
