<?php
// player/check_slots.php
// AJAX API endpoint: fetches booked and pending slots for a specific date and futsal ground

header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/db.php';

// Prevent direct access by guest users
if (!is_logged_in() || $_SESSION['user_role'] !== 'player') {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

$futsal_id = intval($_GET['futsal_id'] ?? 0);
$booking_date = trim($_GET['date'] ?? '');

if (empty($futsal_id) || empty($booking_date)) {
    echo json_encode(['error' => 'Missing grounds ID or date parameters.']);
    exit;
}

try {
    // Query active bookings (exclude rejected or cancelled ones) for the specified date and futsal
    $stmt = $conn->prepare("
        SELECT start_time, status 
        FROM bookings 
        WHERE futsal_id = :futsal_id 
        AND booking_date = :booking_date 
        AND status IN ('pending', 'approved')
    ");
    $stmt->execute([
        'futsal_id' => $futsal_id,
        'booking_date' => $booking_date
    ]);
    
    $booked_slots = $stmt->fetchAll();
    
    // Map data to a simple associative array: time_string => status
    $mapped_slots = [];
    foreach ($booked_slots as $slot) {
        // format time to HH:MM format for simpler matching (e.g. '07:00:00' -> '07:00')
        $time_formatted = substr($slot['start_time'], 0, 5);
        $mapped_slots[$time_formatted] = $slot['status'];
    }
    
    // Return slots mapping to AJAX client
    echo json_encode([
        'success' => true,
        'date' => $booking_date,
        'booked_slots' => $mapped_slots
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
