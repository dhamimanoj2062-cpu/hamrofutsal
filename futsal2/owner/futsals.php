<?php
// owner/futsals.php
// Lists all courts registered by the logged-in owner, supporting deleting grounds

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Restrict access
require_role('owner');

$owner_id = $_SESSION['user_id'];

// 1. Process Court Deletion Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $futsal_id = intval($_POST['futsal_id'] ?? 0);
    
    try {
        // First verify that this futsal ground belongs to the logged-in owner
        $stmt_check = $conn->prepare("SELECT name FROM futsals WHERE id = :id AND owner_id = :owner_id");
        $stmt_check->execute(['id' => $futsal_id, 'owner_id' => $owner_id]);
        $futsal = $stmt_check->fetch();
        
        if ($futsal) {
            // Delete the ground (related bookings delete automatically due to ON DELETE CASCADE)
            $stmt_delete = $conn->prepare("DELETE FROM futsals WHERE id = :id");
            $stmt_delete->execute(['id' => $futsal_id]);
            
            set_flash_message("✔ Court '{$futsal['name']}' has been deleted successfully.", "success");
        } else {
            set_flash_message("Court not found or permission denied.", "danger");
        }
    } catch (PDOException $e) {
        set_flash_message("Deletion failed: " . $e->getMessage(), "danger");
    }
    
    header("Location: futsals.php");
    exit;
}

// 2. Fetch all futsal grounds for this owner
try {
    $stmt = $conn->prepare("SELECT * FROM futsals WHERE owner_id = :owner_id ORDER BY name ASC");
    $stmt->execute(['owner_id' => $owner_id]);
    $my_futsals = $stmt->fetchAll();
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
            <li class="active">
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
            <div class="topbar-title">Manage Futsal Grounds</div>
            <div class="topbar-right">
                <span style="font-weight: 500; font-size: 0.92rem;">Owner: <?php echo sanitize($_SESSION['user_name']); ?></span>
            </div>
        </header>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-content">
            <?php display_flash_message(); ?>

            <div class="card" style="padding: 0;">
                <div class="card-header" style="background-color: #ffffff; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 1.15rem; margin: 0; color: var(--primary-darkest);">Registered Grounds Catalog</h3>
                    <a href="futsal_add.php" class="btn btn-primary btn-sm">Add Futsal Court</a>
                </div>
                
                <div class="card-body" style="padding: 0;">
                    <?php if (empty($my_futsals)): ?>
                        <div style="text-align: center; padding: 50px;">
                            <p class="text-muted" style="font-size: 1.05rem; margin-bottom: 15px;">You have not registered any futsal grounds yet.</p>
                            <a href="futsal_add.php" class="btn btn-primary btn-sm">Register First Court</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="db-table">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Pitch Visual</th>
                                        <th>Futsal Ground Name</th>
                                        <th>Location</th>
                                        <th>Price per Hour</th>
                                        <th>Contact Number</th>
                                        <th style="text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_futsals as $f): ?>
                                        <tr>
                                            <td>
                                                <div style="width: 80px; height: 50px; background-image: url('../<?php echo sanitize($f['image_path']); ?>'); background-size: cover; background-position: center; border-radius: var(--border-radius-sm); border: 1px solid var(--border-color);"></div>
                                            </td>
                                            <td style="font-weight: 600; font-size: 0.98rem;"><?php echo sanitize($f['name']); ?></td>
                                            <td>📍 <?php echo sanitize($f['location']); ?></td>
                                            <td style="font-weight: 700; color: var(--primary-color);">Rs. <?php echo number_format($f['price_per_hour']); ?></td>
                                            <td>📞 <?php echo sanitize($f['contact_number']); ?></td>
                                            <td style="text-align: center;">
                                                <div class="table-actions" style="justify-content: center;">
                                                    <!-- Edit Action Button -->
                                                    <a href="futsal_edit.php?id=<?php echo $f['id']; ?>" class="btn btn-outline btn-sm" style="padding: 4px 10px; font-size: 0.8rem;">Edit</a>
                                                    
                                                    <!-- Delete Action Button with Form Post protection -->
                                                    <form action="futsals.php" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to delete this futsal ground? All player bookings made for this ground will be deleted permanently!');" style="display: inline-block;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="futsal_id" value="<?php echo $f['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 4px 10px; font-size: 0.8rem;">Delete</button>
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
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
