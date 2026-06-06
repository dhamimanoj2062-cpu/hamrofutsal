<?php
// includes/header.php
// Dynamically adjusts paths based on folder depth (supports root files and nested owner/player subfolders)
$is_subfolder = (strpos($_SERVER['SCRIPT_NAME'], '/owner/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/player/') !== false);
$base_path = $is_subfolder ? '../' : './';

// Include the auth file if not already included
require_once __DIR__ . '/auth.php';

// Define auth pages to conditionally skip main site wrapper elements
$auth_pages = ['register.php', 'login.php', 'reset_password.php', 'forgot_password.php', 'resend_verification.php', 'verify_email.php'];
$is_auth_page = !$is_subfolder && in_array(basename($_SERVER['SCRIPT_NAME']), $auth_pages);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAMROFUTSAL - Smart Futsal Booking & Management System</title>
    
    <!-- Link main CSS file -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/style.css">
    
    <!-- Link Dashboard CSS conditionally if in owner or player subdirectories -->
    <?php if ($is_subfolder): ?>
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/dashboard.css">
    <?php endif; ?>
    
    <!-- Link Validation JS on auth pages -->
    <?php if ($is_auth_page): ?>
    <script src="<?php echo $base_path; ?>js/validation.js" defer></script>
    <?php endif; ?>
</head>
<body>

<?php
// If in a subfolder (Dashboard), we use a sidebar layout. We will handle the navbar inside their dashboards.
// For visitor pages (root: index, about, contact, login, register), we render the main visitor navigation bar.
if (!$is_subfolder && !$is_auth_page): 
?>
<header class="header-nav">
    <div class="container navbar">
        <a href="<?php echo $base_path; ?>index.php" class="logo">
            HAMRO<span>FUTSAL</span>
        </a>
        
        <button class="mobile-toggle" id="mobile-toggle" aria-label="Toggle Navigation Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <nav class="nav-links" id="nav-links">
            <a href="<?php echo $base_path; ?>index.php" class="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : ''; ?>">Home</a>
            <a href="<?php echo $base_path; ?>about.php" class="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'about.php' ? 'active' : ''; ?>">About</a>
            <a href="<?php echo $base_path; ?>contact.php" class="<?php echo basename($_SERVER['SCRIPT_NAME']) === 'contact.php' ? 'active' : ''; ?>">Contact</a>
            
            <?php if (is_logged_in()): ?>
                <?php if ($_SESSION['user_role'] === 'player'): ?>
                    <a href="<?php echo $base_path; ?>player/dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>owner/dashboard.php">Dashboard</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
        
        <div class="nav-buttons" id="nav-buttons">
            <?php if (is_logged_in()): ?>
                <span style="font-weight: 500; color: var(--text-main); margin-right: 8px;">Hi, <?php echo sanitize($_SESSION['user_name']); ?>!</span>
                <?php if ($_SESSION['user_role'] === 'player'): ?>
                    <a href="<?php echo $base_path; ?>player/dashboard.php" class="btn btn-primary btn-sm">Go to Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>owner/dashboard.php" class="btn btn-primary btn-sm">Go to Dashboard</a>
                <?php endif; ?>
                <a href="<?php echo $base_path; ?>logout.php" class="btn btn-outline btn-sm">Logout</a>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>login.php" class="btn btn-outline btn-sm">Login</a>
                <a href="<?php echo $base_path; ?>register.php" class="btn btn-primary btn-sm">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container main-content">
    <!-- Display session flash notifications if set -->
    <?php display_flash_message(); ?>
<?php endif; ?>
