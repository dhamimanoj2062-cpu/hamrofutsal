<?php
// index.php
// Public visitor homepage for HAMROFUTSAL

require_once 'includes/header.php';
require_once 'includes/db.php';
?>

<!-- 1. Hero Showcase Block -->
<section class="container">
    <div class="hero-showcase">
        <div class="hero-showcase-overlay"></div>
        <div class="hero-showcase-content">
            <h1>Book Your Futsal Pitch Instantly</h1>
            <p>Skip the calls and prevent double-booking. Find available futsal courts in your city, select your favorite hourly slot, and book online in seconds!</p>
            <div class="hero-showcase-buttons">
                <?php if (is_logged_in()): ?>
                    <?php if ($_SESSION['user_role'] === 'player'): ?>
                        <a href="player/search.php" class="btn-pill-primary">
                            Book a Court Now
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block;vertical-align:middle;margin-left:4px;"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                        <a href="player/dashboard.php" class="btn-link-white">Go to Dashboard</a>
                    <?php else: ?>
                        <a href="owner/dashboard.php" class="btn-pill-primary">
                            Manage Your Grounds
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block;vertical-align:middle;margin-left:4px;"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn-pill-primary">
                        Get Started Now
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block;vertical-align:middle;margin-left:4px;"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                    <a href="about.php" class="btn-link-white">Learn More</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- 2. Search Booking Section -->
<section class="search-section container">
    <div class="search-container">
        <h3>Find Available Grounds</h3>
        <form action="<?php echo is_logged_in() ? 'player/search.php' : 'login.php'; ?>" method="GET">
            <div class="form-group">
                <label for="search-query" class="form-label">Futsal Name</label>
                <input type="text" id="search-query" name="query" class="form-control" placeholder="Search by name...">
            </div>
            <div class="form-group">
                <label for="search-location" class="form-label">Location / City</label>
                <select id="search-location" name="location" class="form-control">
                    <option value="">All Locations</option>
                    <option value="Kathmandu">Kathmandu</option>
                    <option value="Lalitpur">Lalitpur</option>
                    <option value="Bhaktapur">Bhaktapur</option>
                    <option value="Pokhara">Pokhara</option>
                </select>
            </div>
            <div class="form-group" style="flex:0 0 auto;">
                <button type="submit" class="btn btn-primary btn-block" style="height:46px;display:flex;align-items:center;gap:8px;">
                    <svg style="width:18px;height:18px;fill:white;" viewBox="0 0 24 24"><path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z"/></svg>
                    Search Futsal
                </button>
            </div>
        </form>
    </div>
</section>

<!-- 3. Features Section -->
<section class="features-section">
    <div class="container">
        <div class="section-header">
            <h2>Why Choose HAMROFUTSAL?</h2>
            <p>We provide the smoothest online booking experience for players and court managers.</p>
        </div>

        <div class="grid-3">
            <div class="feature-box">
                <div class="feature-icon">⚡</div>
                <h3>Real-Time Booking</h3>
                <p class="text-muted">Instantly search available hours and reserve your court immediately. Zero double bookings, guaranteed.</p>
            </div>

            <div class="feature-box">
                <div class="feature-icon">⭐</div>
                <h3>Reward Points Program</h3>
                <p class="text-muted">Earn points for every match you play! Accumulate points for rewards, but avoid last-minute cancellations!</p>
            </div>

            <div class="feature-box">
                <div class="feature-icon">📱</div>
                <h3>Fully Responsive</h3>
                <p class="text-muted">Book on the go! Our modern, flat user interface is fully optimized for smartphones, tablets, and desktops.</p>
            </div>
        </div>
    </div>
</section>

<!-- 4. Reward System Section -->
<section class="reward-promo-section">
    <div class="container reward-promo-box">
        <div class="reward-promo-content">
            <span class="reward-promo-badge">LOYALTY PROGRAM</span>
            <h2>Earn Points as You Play</h2>
            <p class="text-muted" style="margin-top:15px;margin-bottom:20px;font-size:1.05rem;">
                Our smart reward engine automatically awards <strong>+15 points</strong> to players for every successfully completed or approved booking. Frequent users earn an additional <strong>+20 points</strong> on every 5th booking!
            </p>
            <div class="reward-warning-box">
                <strong>⚠️ Warning:</strong> Canceling a booking within 6 hours of your scheduled time slot will automatically deduct <strong>20 points</strong> from your loyalty balance! Play fair and earn high.
            </div>
            <a href="register.php" class="btn btn-primary">Join &amp; Get 100 Bonus Points</a>
        </div>

        <div class="points-visual-card">
            <div class="points-circle">100</div>
            <h3>Registration Bonus</h3>
            <p class="text-muted" style="font-size:0.9rem;">Sign up today as a player and instantly receive 100 free points to kick off your loyalty progress!</p>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
