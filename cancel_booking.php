<?php
session_start();
header('Content-Type: application/json');
require_once 'dbconnect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit;
}

$booking_id = intval($input['booking_id']);
$user_id = intval($_SESSION['user_id']);

// 1. Verify booking exists, belongs to user, and is not already cancelled
$stmt = $conn->prepare("
    SELECT b.id, b.total_amount, b.status, s.show_date, s.show_time 
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found or access denied']);
    exit;
}

$booking = $result->fetch_assoc();
$stmt->close();

if ($booking['status'] === 'Cancelled') {
    echo json_encode(['success' => false, 'message' => 'Booking is already cancelled']);
    exit;
}

// 2. Fetch total food amount for this booking
$food_stmt = $conn->prepare("SELECT COALESCE(SUM(price * quantity), 0) AS food_total FROM booking_food WHERE booking_id = ?");
$food_stmt->bind_param("i", $booking_id);
$food_stmt->execute();
$food_res = $food_stmt->get_result();
$food_row = $food_res->fetch_assoc();
$food_total = floatval($food_row['food_total']);
$food_stmt->close();

// 3. Calculate refund
$total_amount = floatval($booking['total_amount']);
$ticket_price = max(0, $total_amount - $food_total);

// Calculate hours remaining until showtime
$show_datetime = $booking['show_date'] . ' ' . $booking['show_time'];
$diff_seconds = strtotime($show_datetime) - time();
$hours_remaining = $diff_seconds / 3600;

if ($hours_remaining <= 0) {
    echo json_encode(['success' => false, 'message' => 'Movie start time has already passed. Cancellation is no longer possible.']);
    exit;
}

$refund_amount = 0;

if ($hours_remaining >= 48) {
    // 100% ticket + 100% food
    $refund_amount = $ticket_price + $food_total;
} else if ($hours_remaining >= 24) {
    // 50% ticket + 100% food
    $refund_amount = ($ticket_price * 0.5) + $food_total;
} else {
    // 0% ticket + 100% food
    $refund_amount = 0 + $food_total;
}

// Ensure refund doesn't somehow exceed total
$refund_amount = min($refund_amount, $total_amount);

// 4. Update the booking status
$update_stmt = $conn->prepare("UPDATE bookings SET status = 'Cancelled', refund_amount = ?, cancelled_at = NOW() WHERE id = ?");
$update_stmt->bind_param("di", $refund_amount, $booking_id);

if ($update_stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Booking cancelled successfully', 
        'refund_amount' => $refund_amount
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking']);
}

$update_stmt->close();
?>
