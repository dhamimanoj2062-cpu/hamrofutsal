<?php
// database/setup.php
// Automatic database seeder and setup tool for HAMROFUTSAL
// Navigate to: http://localhost/futsal2/database/setup.php to set up database instantly!

$host = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP password is empty

echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 25px; border: 1px solid #10b981; border-radius: 12px; background-color: #e6f4ea; color: #064e3b;'>";
echo "<h2 style='margin-top: 0; color: #047857;'>HAMROFUTSAL - DB Setup Utility</h2>";

try {
    // 1. Connect to MySQL Server (without specifying DB first to create it)
    $conn = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS hamrofutsal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: #059669; font-weight: 500;'>✔ Database 'hamrofutsal' initialized.</p>";
    
    // 3. Connect to specific database
    $conn->exec("USE hamrofutsal");
    
    // Drop old tables first to ensure clean, up-to-date schema definitions
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $conn->exec("DROP TABLE IF EXISTS reviews, bookings, reward_points, owner_details, futsals, users;");
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
    echo "<p style='color: #059669; font-weight: 500;'>✔ Cleared old tables to ensure fresh schema compatibility.</p>";
    
    // 4. Create Tables
    // Users
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('player', 'owner') NOT NULL,
        phone VARCHAR(20) UNIQUE NOT NULL,
        email_verified TINYINT(1) DEFAULT 0,
        verification_token VARCHAR(255) DEFAULT NULL,
        verification_sent_at TIMESTAMP NULL,
        reset_token VARCHAR(255) DEFAULT NULL,
        token_expiry DATETIME DEFAULT NULL,
        login_attempts INT DEFAULT 0,
        locked_until DATETIME DEFAULT NULL,
        last_login TIMESTAMP NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✔ Table 'users' verified.<br>";

    // Owner Details
    $conn->exec("CREATE TABLE IF NOT EXISTS owner_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        owner_id INT UNIQUE NOT NULL,
        business_name VARCHAR(100) DEFAULT NULL,
        pan_number VARCHAR(50) DEFAULT NULL,
        bank_details TEXT DEFAULT NULL,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✔ Table 'owner_details' verified.<br>";

    // Futsals
    $conn->exec("CREATE TABLE IF NOT EXISTS futsals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        owner_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        location VARCHAR(255) NOT NULL,
        price_per_hour DECIMAL(10, 2) NOT NULL,
        contact_number VARCHAR(20) NOT NULL,
        description TEXT,
        image_path VARCHAR(255) DEFAULT 'assets/images/default_futsal.jpg',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✔ Table 'futsals' verified.<br>";

    // Reward Points
    $conn->exec("CREATE TABLE IF NOT EXISTS reward_points (
        id INT AUTO_INCREMENT PRIMARY KEY,
        player_id INT UNIQUE NOT NULL,
        points INT DEFAULT 100,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✔ Table 'reward_points' verified.<br>";

    // Bookings
    $conn->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        player_id INT NOT NULL,
        futsal_id INT NOT NULL,
        booking_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        total_price DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
        reward_points_modified INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (futsal_id) REFERENCES futsals(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✔ Table 'bookings' verified.<br>";

    // Reviews
    $conn->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        futsal_id INT NOT NULL,
        user_name VARCHAR(100) NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (futsal_id) REFERENCES futsals(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✔ Table 'reviews' verified.<br>";

    // 5. Seed Pre-hashed Dummy Accounts
    // Passwords are pre-hashed for:
    // owner@gmail.com -> owner123
    // player@gmail.com -> player123
    $ownerPassword = password_hash('owner123', PASSWORD_DEFAULT);
    $playerPassword = password_hash('player123', PASSWORD_DEFAULT);
    
    // Check if Owner already exists, if not seed
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = 'owner@gmail.com'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        // Seed Owner
        $stmtInsert = $conn->prepare("INSERT INTO users (name, email, password, role, phone, email_verified) VALUES ('Rohan Shrestha', 'owner@gmail.com', :password, 'owner', '9841567890', 1)");
        $stmtInsert->execute(['password' => $ownerPassword]);
        $ownerId = $conn->lastInsertId();
        
        $stmtOwnerDetail = $conn->prepare("INSERT INTO owner_details (owner_id, business_name, pan_number, bank_details) VALUES (:owner_id, 'Hamro Sports Arena Group', '123456789', 'Nabil Bank - A/C 0123456789012')");
        $stmtOwnerDetail->execute(['owner_id' => $ownerId]);
        
        // Seed Futsal courts for Owner
        $stmtFutsal1 = $conn->prepare("INSERT INTO futsals (owner_id, name, location, price_per_hour, contact_number, description, image_path) VALUES (:owner_id, 'Elite Futsal Arena', 'Kathmandu', 1500.00, '9841567890', 'Standard A-Grade turf with premium lighting, clean locker rooms, and a viewer gallery.', 'assets/images/default_futsal.jpg')");
        $stmtFutsal1->execute(['owner_id' => $ownerId]);
        $futsalId1 = $conn->lastInsertId();
        
        $stmtFutsal2 = $conn->prepare("INSERT INTO futsals (owner_id, name, location, price_per_hour, contact_number, description, image_path) VALUES (:owner_id, 'Champions Futsal Court', 'Lalitpur', 1200.00, '9801234567', 'Beautiful indoor pitch, best for 5v5 matches. Heavy-duty rubber-filled artificial grass.', 'assets/images/default_futsal.jpg')");
        $stmtFutsal2->execute(['owner_id' => $ownerId]);
        $futsalId2 = $conn->lastInsertId();

        // Seed Futsal reviews for these courts
        $stmtReview1 = $conn->prepare("INSERT INTO reviews (futsal_id, user_name, rating, message) VALUES (:futsal_id, 'Sujan Pokhrel', 5, 'Absolutely premium pitch! The turf quality is pro-grade, and the lighting is perfect for night matches.')");
        $stmtReview1->execute(['futsal_id' => $futsalId1]);
        
        $stmtReview2 = $conn->prepare("INSERT INTO reviews (futsal_id, user_name, rating, message) VALUES (:futsal_id, 'Anil Basnet', 4, 'Great indoor pitch. Very responsive staff, clean locker rooms, but booking gets filled very fast. Highly recommended!')");
        $stmtReview2->execute(['futsal_id' => $futsalId1]);
        
        $stmtReview3 = $conn->prepare("INSERT INTO reviews (futsal_id, user_name, rating, message) VALUES (:futsal_id, 'Kiran Thapa', 5, 'Best 5v5 ground in town. The rubber filling is perfect, no slippery spots!')");
        $stmtReview3->execute(['futsal_id' => $futsalId2]);

        echo "<p style='color: #059669; font-weight: 500;'>✔ Seeded Owner 'owner@gmail.com' (password: owner123), 2 grounds, and court reviews.</p>";
    }
    
    // Check if Player already exists, if not seed
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = 'player@gmail.com'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        // Seed Player
        $stmtInsert = $conn->prepare("INSERT INTO users (name, email, password, role, phone, email_verified) VALUES ('Samir Karki', 'player@gmail.com', :password, 'player', '9813567890', 1)");
        $stmtInsert->execute(['password' => $playerPassword]);
        $playerId = $conn->lastInsertId();
        
        // Seed starting reward points
        $stmtPoints = $conn->prepare("INSERT INTO reward_points (player_id, points) VALUES (:player_id, 130)");
        $stmtPoints->execute(['player_id' => $playerId]);
        
        // Find seeded futsal
        $stmtFutsal = $conn->prepare("SELECT id FROM futsals LIMIT 1");
        $stmtFutsal->execute();
        $futsal = $stmtFutsal->fetch();
        
        if ($futsal) {
            // Seed a past booking that is approved (+15 points accounted for, hence starting 130 points: 100 sign up + 15 approved + 15 approved)
            $stmtBooking1 = $conn->prepare("INSERT INTO bookings (player_id, futsal_id, booking_date, start_time, end_time, total_price, status, reward_points_modified) VALUES (:player_id, :futsal_id, CURDATE() - INTERVAL 1 DAY, '17:00:00', '18:00:00', 1500.00, 'approved', 1)");
            $stmtBooking1->execute(['player_id' => $playerId, 'futsal_id' => $futsal['id']]);
            
            $stmtBooking2 = $conn->prepare("INSERT INTO bookings (player_id, futsal_id, booking_date, start_time, end_time, total_price, status, reward_points_modified) VALUES (:player_id, :futsal_id, CURDATE() - INTERVAL 2 DAY, '10:00:00', '11:00:00', 1500.00, 'approved', 1)");
            $stmtBooking2->execute(['player_id' => $playerId, 'futsal_id' => $futsal['id']]);
            
            // Seed a pending booking for tomorrow
            $stmtBooking3 = $conn->prepare("INSERT INTO bookings (player_id, futsal_id, booking_date, start_time, end_time, total_price, status, reward_points_modified) VALUES (:player_id, :futsal_id, CURDATE() + INTERVAL 1 DAY, '18:00:00', '19:00:00', 1500.00, 'pending', 0)");
            $stmtBooking3->execute(['player_id' => $playerId, 'futsal_id' => $futsal['id']]);
        }
        
        echo "<p style='color: #059669; font-weight: 500;'>✔ Seeded Player 'player@gmail.com' (password: player123) with preseeded points and bookings.</p>";
    }
    
    echo "<h3 style='color: #047857; margin-bottom: 5px;'>Database configuration successful!</h3>";
    echo "You can now log in using the preseeded accounts:<br>";
    echo "<b>Player Account:</b> player@gmail.com (Password: player123)<br>";
    echo "<b>Owner Account:</b> owner@gmail.com (Password: owner123)<br><br>";
    echo "<a href='../index.php' style='display: inline-block; background-color: #10b981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Go to Homepage</a>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: #c5221f; margin-bottom: 5px;'>Installation Error!</h3>";
    echo "<p style='color: #c5221f; font-size: 0.95rem;'>" . $e->getMessage() . "</p>";
    echo "<p>Please ensure that MySQL is running inside XAMPP before executing this setup.</p>";
}

echo "</div>";
?>
