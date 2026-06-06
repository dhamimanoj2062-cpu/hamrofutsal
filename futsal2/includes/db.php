<?php
// includes/db.php
// Database connection configuration

$host = 'localhost';
$db_name = 'hamrofutsal';
$username = 'root';
$password = ''; // Default MySQL password for XAMPP is empty

try {
    // Establish a secure PDO connection to the MySQL database
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    // Set the PDO error mode to Exception so SQL errors are easy to debug
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative arrays for simpler data handling
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If the database connection fails, terminate execution and show a clean error message
    // Helpful student tip: make sure MySQL is running in your XAMPP Control Panel!
    die("Database connection failed: " . $e->getMessage());
}
?>
