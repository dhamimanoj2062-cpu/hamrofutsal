<?php
// includes/footer.php
// Dynamically adjusts paths based on folder depth (supports root files and nested owner/player subfolders)
$is_subfolder = (strpos($_SERVER['SCRIPT_NAME'], '/owner/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/player/') !== false);
$base_path = $is_subfolder ? '../' : './';
?>

<?php if (!$is_subfolder): ?>
</main> <!-- End visitor main container -->

<?php
$auth_pages = ['register.php', 'login.php', 'reset_password.php', 'forgot_password.php', 'resend_verification.php', 'verify_email.php'];
$is_auth_page = !$is_subfolder && in_array(basename($_SERVER['SCRIPT_NAME']), $auth_pages);
if (!$is_auth_page):
?>
<footer class="footer">
    <div class="container footer-content">
        <div>
            <a href="<?php echo $base_path; ?>index.php" class="footer-logo">
                HAMRO<span>FUTSAL</span>
            </a>
            <p style="margin-top: 10px; max-width: 350px; font-size: 0.85rem;">
                A clean, modern, and simple futsal booking and management platform helping players schedule courts and owners grow their business.
            </p>
        </div>
        
        <div class="footer-links">
            <a href="<?php echo $base_path; ?>index.php">Home</a>
            <a href="<?php echo $base_path; ?>about.php">About Us</a>
            <a href="<?php echo $base_path; ?>contact.php">Contact Us</a>
            <a href="<?php echo $base_path; ?>login.php">Login</a>
            <a href="<?php echo $base_path; ?>register.php">Register</a>
        </div>
    </div>
    
    <div class="container footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> HAMROFUTSAL. Designed for simple and modern futsal court booking.</p>
    </div>
</footer>
<?php endif; ?>
<?php endif; ?>

<!-- Include the main JavaScript file for navigation and dynamic behaviors -->
<script src="<?php echo $base_path; ?>js/main.js"></script>
</body>
</html>
