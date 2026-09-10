<?php
session_start();
header('Content-Type: application/json');
require_once 'config/db_connection.php';

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);
$booked = [];

if ($type === 'room' && $id > 0) {
    // Get confirmed + pending room bookings for this room
    $stmt = $conn->prepare("
        SELECT check_in, check_out FROM room_bookings 
        WHERE room_id = ? AND status IN ('confirmed', 'pending')
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $start = new DateTime($row['check_in']);
        $end = new DateTime($row['check_out']);
        $end->modify('-1 day');
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        foreach ($period as $date) {
            $booked[] = $date->format('Y-m-d');
        }
    }
} elseif ($type === 'hall' && $id > 0) {
    // Get confirmed + pending event bookings for this hall
    $stmt = $conn->prepare("
        SELECT event_date FROM event_bookings 
        WHERE hall_id = ? AND status IN ('approved', 'pending')
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $booked[] = $row['event_date'];
    }
}

echo json_encode(['success' => true, 'booked' => $booked]);
exit();
?>