<?php
// about.php
// Visitor About Page

require_once 'includes/header.php';
?>

<!-- 1. Header Banner -->
<div class="page-header" style="margin-left: -5%; margin-right: -5%; margin-top: -30px;">
    <div class="container">
        <h1>About HAMROFUTSAL</h1>
        <p>Your ultimate partner for instant online futsal bookings and smart sports management.</p>
    </div>
</div>

<!-- 2. Main Content Blocks -->
<div class="container" style="max-width: 900px; line-height: 1.8;">
    <section style="margin-bottom: 40px;">
        <h2 style="color: var(--primary-darkest); margin-bottom: 15px; border-left: 4px solid var(--primary-color); padding-left: 15px;">Our Mission</h2>
        <p style="font-size: 1.05rem; color: var(--text-muted); text-align: justify;>
            At <strong>HAMROFUTSAL</strong>, we believe sports booking should be frictionless. Traditionally, booking a futsal court meant calling up multiple locations, negotiating times, dealing with incomplete logs, and suffering from sudden double bookings. We built HAMROFUTSAL to replace this chaotic system with a clean, centralized, real-time booking and business management portal.
        </p>
    </section>

    <!-- Cards Layout: Core Values -->
    <section style="margin-bottom: 50px;">
        <h2 style="color: var(--primary-darkest); margin-bottom: 25px; text-center" class="text-center">Our Core Pillars</h2>
        
        <div class="grid-3" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div class="card" style="padding: 24px;">
                <div style="font-size: 2.2rem; margin-bottom: 12px;">🛡️</div>
                <h3 style="font-size: 1.15rem; margin-bottom: 8px;">100% Secure Slots</h3>
                <p class="text-muted" style="font-size: 0.88rem;">Our custom booking validation ensures slots are checked in real-time, completely preventing overlapping reservations.</p>
            </div>
            
            <div class="card" style="padding: 24px;">
                <div style="font-size: 2.2rem; margin-bottom: 12px;">🤝</div>
                <h3 style="font-size: 1.15rem; margin-bottom: 8px;">Loyalty Program</h3>
                <p class="text-muted" style="font-size: 0.88rem;">We reward active squads. Book matches to gain points, but make sure to cancel early if you cannot make it!</p>
            </div>
            
            <div class="card" style="padding: 24px;">
                <div style="font-size: 2.2rem; margin-bottom: 12px;">📊</div>
                <h3 style="font-size: 1.15rem; margin-bottom: 8px;">Owner Overview</h3>
                <p class="text-muted" style="font-size: 0.88rem;">Futsal court owners can track booking counts, calculate monthly revenues, and manage pitches under one clean control panel.</p>
            </div>
        </div>
    </section>

    <!-- Standard Text Block -->
    <section style="background-color: var(--light-green); border-radius: var(--border-radius); padding: 30px; border: 1px solid var(--border-color); text-align: center;">
        <h3 style="color: var(--primary-darkest); margin-bottom: 10px;">Are you a Futsal Ground Owner?</h3>
        <p class="text-muted" style="font-size: 0.95rem; max-width: 600px; margin: 0 auto 20px;">
            Take your business online! Register as a Futsal Owner, add your pitches, manage your own schedule, and track customer bookings seamlessly.
        </p>
        <a href="register.php?role=owner" class="btn btn-primary btn-sm">Register as Futsal Owner</a>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
