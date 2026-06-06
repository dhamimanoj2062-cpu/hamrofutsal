<?php
// owner/stats.php
// Analytical dashboard displaying earnings breakdown, booking status statistics, and ground-by-ground performance

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Restrict access
require_role('owner');

$owner_id = $_SESSION['user_id'];

try {
    // 1. Calculate General Metric Summaries
    // Total Revenue (approved only)
    $stmt_rev = $conn->prepare("
        SELECT SUM(b.total_price) as earnings 
        FROM bookings b 
        JOIN futsals f ON b.futsal_id = f.id 
        WHERE f.owner_id = :owner_id AND b.status = 'approved'
    ");
    $stmt_rev->execute(['owner_id' => $owner_id]);
    $earnings_raw = $stmt_rev->fetch()['earnings'];
    $total_earnings = $earnings_raw ? floatval($earnings_raw) : 0.0;
    
    // Count approved bookings
    $stmt_app = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM bookings b 
        JOIN futsals f ON b.futsal_id = f.id 
        WHERE f.owner_id = :owner_id AND b.status = 'approved'
    ");
    $stmt_app->execute(['owner_id' => $owner_id]);
    $count_approved = $stmt_app->fetch()['count'];
    
    // Count rejected bookings
    $stmt_rej = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM bookings b 
        JOIN futsals f ON b.futsal_id = f.id 
        WHERE f.owner_id = :owner_id AND b.status = 'rejected'
    ");
    $stmt_rej->execute(['owner_id' => $owner_id]);
    $count_rejected = $stmt_rej->fetch()['count'];
    
    // Count cancelled bookings
    $stmt_can = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM bookings b 
        JOIN futsals f ON b.futsal_id = f.id 
        WHERE f.owner_id = :owner_id AND b.status = 'cancelled'
    ");
    $stmt_can->execute(['owner_id' => $owner_id]);
    $count_cancelled = $stmt_can->fetch()['count'];

    // 2. Fetch ground-by-ground breakdown statistics
    $stmt_grounds = $conn->prepare("
        SELECT f.id, f.name, f.location, f.price_per_hour,
               COUNT(CASE WHEN b.status = 'approved' THEN 1 END) as approved_bookings,
               SUM(CASE WHEN b.status = 'approved' THEN b.total_price ELSE 0 END) as ground_revenue
        FROM futsals f 
        LEFT JOIN bookings b ON f.id = b.futsal_id
        WHERE f.owner_id = :owner_id 
        GROUP BY f.id
        ORDER BY ground_revenue DESC
    ");
    $stmt_grounds->execute(['owner_id' => $owner_id]);
    $ground_stats = $stmt_grounds->fetchAll();
    
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
            <li class="active">
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
            <div class="topbar-title">Financial & Analytics Overview</div>
            <div class="topbar-right">
                <span style="font-weight: 500; font-size: 0.92rem;">Owner: <?php echo sanitize($_SESSION['user_name']); ?></span>
            </div>
        </header>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-content">
            <?php display_flash_message(); ?>

            <div style="margin-bottom: 25px;">
                <h2 style="font-size: 1.6rem; color: var(--primary-darkest);">Business Performance Reports</h2>
                <p class="text-muted">Review booking conversion rates, status distributions, and pitch-by-pitch revenues.</p>
            </div>

            <!-- Stats metric grid -->
            <div class="stats-grid">
                <!-- Total Earnings widget -->
                <div class="stat-card" style="border-color: var(--primary-color); background-color: var(--light-green);">
                    <div class="stat-card-info">
                        <span class="stat-card-label">Cumulative Earnings</span>
                        <span class="stat-card-value">Rs. <?php echo number_format($total_earnings); ?></span>
                    </div>
                    <div class="stat-card-icon" style="background-color: #ffffff; color: var(--primary-color);">💰</div>
                </div>

                <!-- Approved match bookings count -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <span class="stat-card-label">Approved Matches</span>
                        <span class="stat-card-value"><?php echo $count_approved; ?></span>
                    </div>
                    <div class="stat-card-icon">✔</div>
                </div>

                <!-- Rejected matches widget -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <span class="stat-card-label">Rejected Matches</span>
                        <span class="stat-card-value"><?php echo $count_rejected; ?></span>
                    </div>
                    <div class="stat-card-icon danger">❌</div>
                </div>

                <!-- Cancelled matches count widget -->
                <div class="stat-card">
                    <div class="stat-card-info">
                        <span class="stat-card-label">Cancelled Matches</span>
                        <span class="stat-card-value"><?php echo $count_cancelled; ?></span>
                    </div>
                    <div class="stat-card-icon warning">📅</div>
                </div>
            </div>

            <!-- Ground by ground revenue performance table -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header" style="background-color: #ffffff; border-bottom: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.15rem; margin: 0; color: var(--primary-darkest);">Court Revenue Performance</h3>
                </div>
                
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($ground_stats)): ?>
                        <div style="text-align: center; padding: 40px;">
                            <p class="text-muted" style="font-size: 0.95rem; margin-bottom: 10px;">You have no registered grounds to display analytics for.</p>
                            <a href="futsal_add.php" class="btn btn-primary btn-sm">Add First Court</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="db-table">
                                <thead>
                                    <tr>
                                        <th>Futsal Court Name</th>
                                        <th>Location</th>
                                        <th>Price per Hour</th>
                                        <th>Approved Matches Count</th>
                                        <th>Total Earnings Generated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ground_stats as $stat): ?>
                                        <tr>
                                            <td style="font-weight: 600;"><?php echo sanitize($stat['name']); ?></td>
                                            <td>📍 <?php echo sanitize($stat['location']); ?></td>
                                            <td>Rs. <?php echo number_format($stat['price_per_hour']); ?></td>
                                            <td style="font-weight: 500;"><?php echo intval($stat['approved_bookings']); ?> Match(es)</td>
                                            <td style="font-weight: 700; color: var(--primary-color);">
                                                Rs. <?php echo number_format($stat['ground_revenue']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Explanatory note card for business reports -->
            <div class="card" style="padding: 20px; border-left: 4px solid var(--primary-color);">
                <h4 style="color: var(--primary-darkest); margin-bottom: 8px;">💡 Understanding Your Metrics</h4>
                <ul style="font-size: 0.88rem; color: var(--text-muted); padding-left: 20px; list-style: disc; display: flex; flex-direction: column; gap: 6px;">
                    <li><strong>Cumulative Earnings:</strong> Sum of prices from approved bookings only. Bookings that are cancelled or rejected do not contribute to revenue.</li>
                    <li><strong>Court Performance:</strong> Track which futsal courts are booked most frequently and allocate scheduling blocks accordingly.</li>
                    <li><strong>Point Penalties:</strong> Late-cancelled slots are automatically logged as negative adjustments to players, incentivizing squads to keep their bookings.</li>
                </ul>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
