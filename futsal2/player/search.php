<?php
// player/search.php
// Let players search and view available futsal grounds

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Restrict access
require_role('player');

$search_query = trim($_GET['query'] ?? '');
$search_location = trim($_GET['location'] ?? '');

try {
    // Build dynamic search query securely using PDO
    $sql = "SELECT f.*, u.name as owner_name FROM futsals f JOIN users u ON f.owner_id = u.id WHERE 1=1";
    $params = [];
    
    if (!empty($search_query)) {
        $sql .= " AND f.name LIKE :query";
        $params['query'] = '%' . $search_query . '%';
    }
    
    if (!empty($search_location)) {
        $sql .= " AND f.location = :location";
        $params['location'] = $search_location;
    }
    
    $sql .= " ORDER BY f.name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $futsals = $stmt->fetchAll();
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
            <li class="active">
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
            <div class="topbar-title">Search Futsal Grounds</div>
            <div class="topbar-right">
                <span style="font-weight: 500; font-size: 0.92rem;">Welcome, <?php echo sanitize($_SESSION['user_name']); ?></span>
            </div>
        </header>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-content">
            <!-- Inline Search Form -->
            <div class="card" style="margin-bottom: 30px; padding: 20px;">
                <form action="search.php" method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div class="form-group" style="flex: 2; min-width: 250px; margin-bottom: 0;">
                        <label for="query" class="form-label">Search Ground Name</label>
                        <input type="text" id="query" name="query" class="form-control" placeholder="e.g. Elite Futsal..." value="<?php echo sanitize($search_query); ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1; min-width: 180px; margin-bottom: 0;">
                        <label for="location" class="form-label">Location / City</label>
                        <select id="location" name="location" class="form-control">
                            <option value="">All Locations</option>
                            <option value="Kathmandu" <?php echo $search_location === 'Kathmandu' ? 'selected' : ''; ?>>Kathmandu</option>
                            <option value="Lalitpur" <?php echo $search_location === 'Lalitpur' ? 'selected' : ''; ?>>Lalitpur</option>
                            <option value="Bhaktapur" <?php echo $search_location === 'Bhaktapur' ? 'selected' : ''; ?>>Bhaktapur</option>
                            <option value="Pokhara" <?php echo $search_location === 'Pokhara' ? 'selected' : ''; ?>>Pokhara</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="flex: 0 0 auto; margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary" style="height: 46px; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 0 25px;">
                            Filter
                        </button>
                    </div>
                    
                    <?php if (!empty($search_query) || !empty($search_location)): ?>
                        <div class="form-group" style="flex: 0 0 auto; margin-bottom: 0;">
                            <a href="search.php" class="btn btn-secondary" style="height: 46px; display: flex; align-items: center; justify-content: center; padding: 0 15px;">Clear Filters</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- List Results -->
            <div style="margin-bottom: 20px;">
                <h3 style="font-size: 1.2rem; color: var(--primary-darkest);">
                    <?php echo count($futsals); ?> Futsal Arena(s) Found
                </h3>
            </div>

            <?php if (empty($futsals)): ?>
                <div class="card" style="text-align: center; padding: 50px;">
                    <p class="text-muted" style="font-size: 1.05rem; margin-bottom: 10px;">No futsal arenas match your filters.</p>
                    <p style="font-size: 0.9rem;">Try clearing your search query or changing the city filter to see active courts.</p>
                </div>
            <?php else: ?>
                <div class="grid-3">
                    <?php foreach ($futsals as $f): ?>
                        <div class="card">
                            <div class="futsal-card-img" style="background-image: url('../<?php echo sanitize($f['image_path']); ?>');"></div>
                            <div class="card-body">
                                <h3 style="font-size: 1.15rem; margin-bottom: 8px;"><?php echo sanitize($f['name']); ?></h3>
                                
                                <div class="futsal-details">
                                    <div class="futsal-info-item">
                                        <svg style="width: 16px; height: 16px; fill: var(--primary-color);" viewBox="0 0 24 24"><path d="M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z" /></svg>
                                        <span><?php echo sanitize($f['location']); ?></span>
                                    </div>
                                    <div class="futsal-info-item">
                                        <svg style="width: 16px; height: 16px; fill: var(--primary-color);" viewBox="0 0 24 24"><path d="M6.62,10.79C8.06,13.62 10.38,15.94 13.21,17.38L15.41,15.18C15.69,14.9 16.08,14.82 16.43,14.93C17.55,15.3 18.75,15.5 20,15.5A1,1 0 0,1 21,16.5V20A1,1 0 0,1 20,21A17,17 0 0,1 3,4A1,1 0 0,1 4,3H7.5A1,1 0 0,1 8.5,4C8.5,5.25 8.7,6.45 9.07,7.57C9.18,7.92 9.1,8.31 8.82,8.59L6.62,10.79Z" /></svg>
                                        <span><?php echo sanitize($f['contact_number']); ?></span>
                                    </div>
                                </div>
                                
                                <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 20px; height: 60px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                    <?php echo sanitize($f['description']); ?>
                                </p>
                            </div>
                            <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <span style="font-size: 0.8rem; color: var(--text-muted); display: block;">Price per Hour</span>
                                    <span class="price-tag" style="font-size: 1.15rem;">Rs. <?php echo number_format($f['price_per_hour']); ?></span>
                                </div>
                                
                                <a href="book.php?futsal_id=<?php echo $f['id']; ?>" class="btn btn-primary btn-sm">Book Court</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
