<?php
// player/book.php
// Interactive scheduling and checkout interface for booking a court

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Restrict access
require_role('player');

$player_id = $_SESSION['user_id'];
$futsal_id = intval($_GET['futsal_id'] ?? 0);

if (!$futsal_id) {
    set_flash_message("Please select a futsal ground to book.", "warning");
    header("Location: search.php");
    exit;
}

try {
    // 1. Fetch court details
    $stmt = $conn->prepare("SELECT * FROM futsals WHERE id = :id");
    $stmt->execute(['id' => $futsal_id]);
    $futsal = $stmt->fetch();
    
    if (!$futsal) {
        set_flash_message("Selected futsal ground does not exist.", "danger");
        header("Location: search.php");
        exit;
    }

    // 2. Fetch Average Rating and review count
    $stmt_avg = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE futsal_id = :futsal_id");
    $stmt_avg->execute(['futsal_id' => $futsal_id]);
    $rating_stats = $stmt_avg->fetch();
    $avg_rating = $rating_stats['avg_rating'] ? round(floatval($rating_stats['avg_rating']), 1) : 0;
    $review_count = intval($rating_stats['review_count']);
    
    // 3. Fetch all reviews for this court (newest first)
    $stmt_list_rev = $conn->prepare("SELECT * FROM reviews WHERE futsal_id = :futsal_id ORDER BY created_at DESC");
    $stmt_list_rev->execute(['futsal_id' => $futsal_id]);
    $reviews = $stmt_list_rev->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$error_msg = "";

// 4. Process Review Submission Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $reviewer_name = trim($_POST['user_name'] ?? '');
    $rating = intval($_POST['rating'] ?? 5);
    $message = trim($_POST['message'] ?? '');
    
    if (empty($reviewer_name) || empty($message)) {
        $error_msg = "Please enter both a username and a review message.";
    } elseif ($rating < 1 || $rating > 5) {
        $error_msg = "Invalid star rating.";
    } else {
        try {
            $stmt_rev = $conn->prepare("INSERT INTO reviews (futsal_id, user_name, rating, message) VALUES (:futsal_id, :user_name, :rating, :message)");
            $stmt_rev->execute([
                'futsal_id' => $futsal_id,
                'user_name' => $reviewer_name,
                'rating' => $rating,
                'message' => $message
            ]);
            
            set_flash_message("✔ Thank you! Your review has been submitted successfully.", "success");
            header("Location: book.php?futsal_id=" . $futsal_id);
            exit;
        } catch (PDOException $e) {
            $error_msg = "Failed to submit review: " . $e->getMessage();
        }
    }
}

// 5. Process Reservation Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['submit_review'])) {
    $booking_date = trim($_POST['booking_date'] ?? '');
    $start_time_short = trim($_POST['start_time'] ?? ''); // e.g., '08:00'
    
    // Server-side validation
    if (empty($booking_date) || empty($start_time_short)) {
        $error_msg = "Please select both a date and an available time slot.";
    } elseif ($booking_date < date('Y-m-d')) {
        $error_msg = "You cannot book slots in the past.";
    } else {
        // Format time strings for standard database TIME format (HH:MM:SS)
        $start_time = $start_time_short . ':00';
        $start_hour = intval(explode(':', $start_time_short)[0]);
        $end_hour = $start_hour + 1;
        $end_time = sprintf("%02d:00:00", $end_hour);
        
        try {
            // Strict DB overlap check (double booking prevention)
            $stmt_check = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM bookings 
                WHERE futsal_id = :futsal_id 
                AND booking_date = :booking_date 
                AND start_time = :start_time 
                AND status IN ('pending', 'approved')
            ");
            $stmt_check->execute([
                'futsal_id' => $futsal_id,
                'booking_date' => $booking_date,
                'start_time' => $start_time
            ]);
            
            $already_taken = $stmt_check->fetch()['count'] > 0;
            
            if ($already_taken) {
                $error_msg = "Sorry! This time slot has just been booked by another user. Please select another slot.";
            } else {
                // Ground pricing calculations
                $total_price = $futsal['price_per_hour'];
                
                // Save reservation in bookings table (status defaults to 'pending')
                $stmt_insert = $conn->prepare("
                    INSERT INTO bookings (player_id, futsal_id, booking_date, start_time, end_time, total_price, status) 
                    VALUES (:player_id, :futsal_id, :booking_date, :start_time, :end_time, :total_price, 'pending')
                ");
                $stmt_insert->execute([
                    'player_id' => $player_id,
                    'futsal_id' => $futsal_id,
                    'booking_date' => $booking_date,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'total_price' => $total_price
                ]);
                
                set_flash_message("Your booking request has been submitted! It is currently pending approval by the owner.", "success");
                header("Location: history.php");
                exit;
            }
        } catch (PDOException $e) {
            $error_msg = "Failed to reserve slot: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';

// Define the hourly slots list (6 AM to 9 PM start times)
$time_slots = [
    '06:00' => '06:00 AM - 07:00 AM',
    '07:00' => '07:00 AM - 08:00 AM',
    '08:00' => '08:00 AM - 09:00 AM',
    '09:00' => '09:00 AM - 10:00 AM',
    '10:00' => '10:00 AM - 11:00 AM',
    '11:00' => '11:00 AM - 12:00 PM',
    '12:00' => '12:00 PM - 01:00 PM',
    '13:00' => '01:00 PM - 02:00 PM',
    '14:00' => '02:00 PM - 03:00 PM',
    '15:00' => '03:00 PM - 04:00 PM',
    '16:00' => '04:00 PM - 05:00 PM',
    '17:00' => '05:00 PM - 06:00 PM',
    '18:00' => '06:00 PM - 07:00 PM',
    '19:00' => '07:00 PM - 08:00 PM',
    '20:00' => '08:00 PM - 09:00 PM',
    '21:00' => '09:00 PM - 10:00 PM'
];
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
            <div class="topbar-title">Book a Court Slot</div>
            <div class="topbar-right">
                <span style="font-weight: 500; font-size: 0.92rem;">Welcome, <?php echo sanitize($_SESSION['user_name']); ?></span>
            </div>
        </header>

        <!-- Dashboard Content Grid -->
        <div class="dashboard-content">
            <?php if (!empty($error_msg)): ?>
                <div style="background-color: #fce8e6; color: #c5221f; border: 1px solid #c5221f; padding: 12px 16px; border-radius: 8px; margin-bottom: 25px; font-weight: 500; font-size: 0.95rem;">
                    <?php echo sanitize($error_msg); ?>
                </div>
            <?php endif; ?>

            <!-- Ground Information Overview Column & Scheduler Panel -->
            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; align-items: start; flex-wrap: wrap;">
                
                <!-- Left Side: Court Summary Detail Box -->
                <div class="card">
                    <div class="futsal-card-img" style="background-image: url('../<?php echo sanitize($futsal['image_path']); ?>'); height: 180px;"></div>
                    <div class="card-body">
                        <h3 style="font-size: 1.3rem; margin-bottom: 8px; color: var(--primary-darkest);"><?php echo sanitize($futsal['name']); ?></h3>
                        
                        <div class="futsal-details" style="margin-bottom: 20px;">
                            <div class="futsal-info-item">
                                📍 <span><?php echo sanitize($futsal['location']); ?></span>
                            </div>
                            <div class="futsal-info-item">
                                📞 <span><?php echo sanitize($futsal['contact_number']); ?></span>
                            </div>
                            <div class="futsal-info-item">
                                💰 <span style="font-weight: 600; color: var(--primary-color);">Rs. <?php echo number_format($futsal['price_per_hour']); ?> / hour</span>
                            </div>
                            <div class="futsal-info-item">
                                ⭐ <span style="font-weight: 600; color: var(--warning-color);">
                                    <?php echo $review_count > 0 ? "{$avg_rating} / 5 ({$review_count} Reviews)" : "No reviews yet"; ?>
                                </span>
                            </div>
                        </div>
                        
                        <h4 style="font-size: 0.95rem; margin-bottom: 5px;">Description</h4>
                        <p class="text-muted" style="font-size: 0.88rem; text-align: justify; line-height: 1.5;">
                            <?php echo sanitize($futsal['description']); ?>
                        </p>
                    </div>
                    <div class="card-footer" style="text-align: center;">
                        <a href="search.php" class="btn btn-secondary btn-sm btn-block">Back to Grounds List</a>
                    </div>
                </div>
                
                <!-- Right Side: Scheduling Calendar & Interactive Slots Grid -->
                <div class="card" style="padding: 25px;">
                    <h3 style="font-size: 1.2rem; color: var(--primary-darkest); margin-bottom: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Select Booking Time</h3>
                    
                    <form action="book.php?futsal_id=<?php echo $futsal_id; ?>" method="POST">
                        <input type="hidden" id="futsal_id" value="<?php echo $futsal_id; ?>">
                        <input type="hidden" id="price_per_hour" value="<?php echo $futsal['price_per_hour']; ?>">
                        
                        <!-- Calendar Input Date Picker -->
                        <div class="form-group">
                            <label for="booking_date" class="form-label">Booking Date</label>
                            <!-- Locks minimum date selection strictly to today's date dynamically -->
                            <input type="date" id="booking_date" name="booking_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <!-- Dynamic Hourly Booking Slots Grid -->
                        <div class="form-group">
                            <label class="form-label">Available Slots (1 Hour Slots)</label>
                            
                            <div class="slot-grid" id="slot-container">
                                <?php foreach ($time_slots as $start => $label): ?>
                                    <div class="slot-option">
                                        <input type="radio" id="slot-<?php echo $start; ?>" name="start_time" value="<?php echo $start; ?>">
                                        <label for="slot-<?php echo $start; ?>" class="slot-label">
                                            <span style="display: block; font-weight: 600; font-size: 0.85rem;"><?php echo date('h:i A', strtotime($start)); ?></span>
                                            <span class="slot-status-label" style="display: block; font-size: 0.65rem; color: var(--text-muted); margin-top: 2px;">Available</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Confirmation summary shown dynamically by JS -->
                        <div id="booking-summary" style="margin-bottom: 25px; text-align: center;">
                            <p class="text-muted" style="font-size: 0.9rem;">Please select a date and an available time slot above.</p>
                        </div>
                        
                        <button type="submit" id="submit-booking-btn" class="btn btn-primary btn-block" style="height: 48px; font-weight: 600;" disabled>
                            Confirm Booking Request
                        </button>
                    </form>
                </div>
                
            </div>

            <!-- Reviews and Ratings Section -->
            <div class="card" style="margin-top: 30px; padding: 25px;">
                <h3 style="font-size: 1.25rem; color: var(--primary-darkest); margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Player Reviews & Ratings</h3>
                
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; align-items: start; flex-wrap: wrap;">
                    
                    <!-- Left Column: Reviews List -->
                    <div>
                        <h4 style="font-size: 1rem; margin-bottom: 15px; color: var(--text-main);">Recent Customer Feedback</h4>
                        
                        <?php if (empty($reviews)): ?>
                            <div style="padding: 35px; text-align: center; border: 1px dashed var(--border-color); border-radius: var(--border-radius-sm); background-color: var(--background-color);">
                                <p class="text-muted" style="margin: 0; font-size: 0.92rem;">No reviews have been written for this court yet. Share your experience by submitting one!</p>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 15px; max-height: 450px; overflow-y: auto; padding-right: 8px;">
                                <?php foreach ($reviews as $r): ?>
                                    <div style="background-color: var(--background-color); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 15px;">
                                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; gap: 10px;">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div style="width: 34px; height: 34px; border-radius: 50%; background-color: var(--primary-color); color: #ffffff; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 0.9rem;">
                                                    <?php echo strtoupper(sanitize(substr($r['user_name'], 0, 1))); ?>
                                                </div>
                                                <div>
                                                    <strong style="font-size: 0.92rem; display: block; color: var(--text-main);"><?php echo sanitize($r['user_name']); ?></strong>
                                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('M d, Y h:i A', strtotime($r['created_at'])); ?></span>
                                                </div>
                                            </div>
                                            <!-- Green Stars Widget -->
                                            <div style="color: var(--primary-color); font-size: 1.15rem; letter-spacing: 1px;">
                                                <?php 
                                                    echo str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']); 
                                                ?>
                                            </div>
                                        </div>
                                        <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0; line-height: 1.5; text-align: justify; white-space: pre-line;">
                                            <?php echo sanitize($r['message']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Right Column: Add Review Form -->
                    <div style="background-color: var(--background-color); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 20px;">
                        <h4 style="font-size: 1rem; margin-bottom: 15px; color: var(--primary-darkest);">Submit Your Review</h4>
                        
                        <form action="book.php?futsal_id=<?php echo $futsal_id; ?>" method="POST">
                            <input type="hidden" name="submit_review" value="1">
                            
                            <div class="form-group">
                                <label for="user_name" class="form-label">Your Name</label>
                                <input type="text" id="user_name" name="user_name" class="form-control" value="<?php echo sanitize($_SESSION['user_name']); ?>" required>
                            </div>
                            
                            <!-- Interlocking Star selector styled with CSS -->
                            <div class="form-group">
                                <label class="form-label">Rate Court (1 to 5 Stars)</label>
                                <div class="star-rating" id="star-rating">
                                    <span class="star-btn" data-value="1">★</span>
                                    <span class="star-btn" data-value="2">★</span>
                                    <span class="star-btn" data-value="3">★</span>
                                    <span class="star-btn" data-value="4">★</span>
                                    <span class="star-btn" data-value="5">★</span>
                                </div>
                                <input type="hidden" name="rating" id="rating_input" value="5" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="message" class="form-label">Review Message</label>
                                <textarea id="message" name="message" class="form-control" rows="4" placeholder="Share your match experience, turf quality, facilities..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block btn-sm" style="font-weight:600;">Submit Review</button>
                        </form>
                    </div>
                    
                </div>
            </div>
            
        </div>
    </main>
</div>

<!-- Inline CSS stars styling -->
<style>
.star-rating {
    display: flex;
    gap: 6px;
    font-size: 2.1rem;
    color: #cbd5e1; /* Gray state */
    margin-bottom: 5px;
    user-select: none;
}
.star-rating .star-btn {
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}
.star-rating .star-btn:hover {
    transform: scale(1.15);
}
.star-rating .star-btn.active {
    color: var(--primary-color); /* Bright flat green to match color theme */
}
</style>

<!-- Simple client JS handling selection -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const stars = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('rating_input');
    
    if (stars.length && ratingInput) {
        stars.forEach(star => {
            star.addEventListener('click', function () {
                const val = parseInt(this.getAttribute('data-value'));
                ratingInput.value = val;
                
                // Update active classes for all stars
                stars.forEach(s => {
                    const sVal = parseInt(s.getAttribute('data-value'));
                    if (sVal <= val) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
        
        // Trigger a click on the 5th star on initial load to set it as default
        const defaultStar = document.querySelector('.star-btn[data-value="5"]');
        if (defaultStar) defaultStar.click();
    }
});
</script>

<!-- Load custom booking JavaScript helper -->
<script src="../js/booking.js"></script>

<?php require_once '../includes/footer.php'; ?>
