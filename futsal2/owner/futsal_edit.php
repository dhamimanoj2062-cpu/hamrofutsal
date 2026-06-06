<?php
// owner/futsal_edit.php
// Prefills and edits registered futsal ground profiles

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Restrict access
require_role('owner');

$owner_id = $_SESSION['user_id'];
$futsal_id = intval($_GET['id'] ?? 0);

if (!$futsal_id) {
    set_flash_message("Please select a futsal ground to edit.", "warning");
    header("Location: futsals.php");
    exit;
}

try {
    // 1. Fetch court details and verify ownership
    $stmt = $conn->prepare("SELECT * FROM futsals WHERE id = :id AND owner_id = :owner_id");
    $stmt->execute(['id' => $futsal_id, 'owner_id' => $owner_id]);
    $futsal = $stmt->fetch();
    
    if (!$futsal) {
        set_flash_message("Futsal ground not found or permission denied.", "danger");
        header("Location: futsals.php");
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$error_msg = "";

// 2. Process profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $price_per_hour = floatval($_POST['price_per_hour'] ?? 0.0);
    $contact_number = trim($_POST['contact_number'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    $image_path = $futsal['image_path']; // Keep existing image path initially
    
    if (empty($name) || empty($location) || empty($price_per_hour) || empty($contact_number)) {
        $error_msg = "Please fill in all required fields (Name, Location, Price, Contact).";
    } elseif ($price_per_hour <= 0) {
        $error_msg = "Price per hour must be greater than zero.";
    } else {
        try {
            // Process Image replacement if uploaded
            if (isset($_FILES['futsal_image']) && $_FILES['futsal_image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['futsal_image']['tmp_name'];
                $file_name = $_FILES['futsal_image']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_extensions = ['jpg', 'jpeg', 'png'];
                
                if (!in_array($file_ext, $allowed_extensions)) {
                    $error_msg = "Invalid image file type. Only JPG, JPEG, and PNG are allowed.";
                } else {
                    $new_file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
                    $destination_dir = '../assets/images/';
                    $destination_path = $destination_dir . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $destination_path)) {
                        $image_path = 'assets/images/' . $new_file_name;
                        
                        // Optional student-bonus: Delete old image from server if it wasn't the default placeholder!
                        if ($futsal['image_path'] !== 'assets/images/default_futsal.jpg' && file_exists('../' . $futsal['image_path'])) {
                            unlink('../' . $futsal['image_path']);
                        }
                    }
                }
            }
            
            if (empty($error_msg)) {
                // Perform SQL Update
                $stmt_update = $conn->prepare("
                    UPDATE futsals 
                    SET name = :name, location = :location, price_per_hour = :price, contact_number = :contact, description = :description, image_path = :image 
                    WHERE id = :id AND owner_id = :owner_id
                ");
                
                $stmt_update->execute([
                    'name' => $name,
                    'location' => $location,
                    'price' => $price_per_hour,
                    'contact' => $contact_number,
                    'description' => $description,
                    'image' => $image_path,
                    'id' => $futsal_id,
                    'owner_id' => $owner_id
                ]);
                
                set_flash_message("✔ Court '{$name}' details have been updated successfully!", "success");
                header("Location: futsals.php");
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = "Database update failed: " . $e->getMessage();
        }
    }
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
            <div class="topbar-title">Edit Futsal Court Profile</div>
            <div class="topbar-right">
                <span style="font-weight: 500; font-size: 0.92rem;">Owner: <?php echo sanitize($_SESSION['user_name']); ?></span>
            </div>
        </header>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-content" style="max-width: 800px;">
            <?php if (!empty($error_msg)): ?>
                <div style="background-color: #fce8e6; color: #c5221f; border: 1px solid #c5221f; padding: 12px 16px; border-radius: 8px; margin-bottom: 25px; font-weight: 500; font-size: 0.95rem;">
                    <?php echo sanitize($error_msg); ?>
                </div>
            <?php endif; ?>

            <div class="card" style="padding: 30px;">
                <h3 style="font-size: 1.2rem; color: var(--primary-darkest); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Edit '<?php echo sanitize($futsal['name']); ?>'</h3>
                
                <form action="futsal_edit.php?id=<?php echo $futsal_id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name" class="form-label">Futsal Court Name *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo sanitize($futsal['name']); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location" class="form-label">Location / City *</label>
                            <select id="location" name="location" class="form-control" required>
                                <option value="Kathmandu" <?php echo $futsal['location'] === 'Kathmandu' ? 'selected' : ''; ?>>Kathmandu</option>
                                <option value="Lalitpur" <?php echo $futsal['location'] === 'Lalitpur' ? 'selected' : ''; ?>>Lalitpur</option>
                                <option value="Bhaktapur" <?php echo $futsal['location'] === 'Bhaktapur' ? 'selected' : ''; ?>>Bhaktapur</option>
                                <option value="Pokhara" <?php echo $futsal['location'] === 'Pokhara' ? 'selected' : ''; ?>>Pokhara</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price_per_hour" class="form-label">Price per Hour (Rs.) *</label>
                            <input type="number" id="price_per_hour" name="price_per_hour" class="form-control" value="<?php echo intval($futsal['price_per_hour']); ?>" min="1" step="50" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_number" class="form-label">Business Contact Number *</label>
                        <input type="tel" id="contact_number" name="contact_number" class="form-control" value="<?php echo sanitize($futsal['contact_number']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description / Court Amenities</label>
                        <textarea id="description" name="description" class="form-control" rows="5"><?php echo sanitize($futsal['description']); ?></textarea>
                    </div>
                    
                    <!-- Previews current picture and provides file upload replacement -->
                    <div class="form-group" style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                        <div style="flex-shrink: 0;">
                            <span class="form-label" style="display: block; margin-bottom: 5px;">Current Turf Image</span>
                            <div style="width: 120px; height: 80px; background-image: url('../<?php echo sanitize($futsal['image_path']); ?>'); background-size: cover; background-position: center; border-radius: var(--border-radius-sm); border: 1px solid var(--border-color);"></div>
                        </div>
                        
                        <div style="flex: 1; min-width: 200px;">
                            <label for="futsal_image" class="form-label">Replace Turf Image (Optional)</label>
                            <input type="file" id="futsal_image" name="futsal_image" class="form-control" accept=".jpg, .jpeg, .png" style="padding: 7px 10px;">
                            <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 4px;">Supported: JPG, JPEG, PNG. Leaves original photo unchanged if empty.</span>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 12px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="futsals.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>
