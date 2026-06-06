<?php
// contact.php
// Public Contact Us Page

require_once 'includes/header.php';
require_once 'includes/auth.php';

$error_msg = "";
$name = $email = $subject = $message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_msg = "Please fill in all fields before sending.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } else {
        set_flash_message("Thank you, " . sanitize($name) . "! Your message has been sent successfully. We will get back to you soon.", "success");
        header("Location: contact.php");
        exit;
    }
}
?>

<!-- 1. Header Banner -->
<section class="page-header">
    <h1>Contact HAMROFUTSAL</h1>
    <p>Have questions or need support? Our team is here to assist you.</p>
</section>

<!-- 2. Main Grid Layout -->
<section class="contact-section">

    <?php if (!empty($error_msg)): ?>
        <div class="contact-error"><?php echo sanitize($error_msg); ?></div>
    <?php endif; ?>

    <div class="contact-grid">

        <!-- Card 1: Contact details -->
        <article class="card contact-info-card">
            <h3>Get In Touch</h3>
            <p class="text-muted">Feel free to contact us via phone, email, or by visiting our main office. We value your feedback and recommendations!</p>

            <div class="contact-info-list">
                <div class="contact-info-item">
                    <span class="contact-info-icon">📍</span>
                    <div>
                        <strong>Main Office</strong>
                        <span>Koteshwor-32, Kathmandu, Nepal</span>
                    </div>
                </div>

                <div class="contact-info-item">
                    <span class="contact-info-icon">📞</span>
                    <div>
                        <strong>Phone Number</strong>
                        <span>+977 1-4600000 | +977 9841234567</span>
                    </div>
                </div>

                <div class="contact-info-item">
                    <span class="contact-info-icon">✉️</span>
                    <div>
                        <strong>Email Address</strong>
                        <span>support@hamrofutsal.com</span>
                    </div>
                </div>
            </div>
        </article>

        <!-- Card 2: Contact Form -->
        <article class="card contact-form-card">
            <h3>Send Feedback</h3>
            <form action="contact.php" method="POST">
                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Samir Karki" value="<?php echo sanitize($name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="e.g. samir@gmail.com" value="<?php echo sanitize($email); ?>" required>
                </div>

                <div class="form-group">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" id="subject" name="subject" class="form-control" placeholder="What is this inquiry about?" value="<?php echo sanitize($subject); ?>" required>
                </div>

                <div class="form-group">
                    <label for="message" class="form-label">Your Message</label>
                    <textarea id="message" name="message" class="form-control" rows="5" placeholder="Write details here..." required><?php echo sanitize($message); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Send Message</button>
            </form>
        </article>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
