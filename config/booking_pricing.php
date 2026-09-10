<?php
/**
 * Shared booking validation + pricing logic.
 *
 * Both create-payment-intent.php (which charges the card) and the finalize endpoints
 * (room-booking-process.php / event-booking-process.php, which insert the booking row
 * after payment succeeds) call these SAME functions, so the price a customer is charged
 * always matches the price the booking is saved with — nothing is ever trusted from the
 * browser.
 */

/**
 * Validates + prices a ROOM booking.
 * Returns: ['success'=>bool, 'error'=>string|null, 'total'=>float, 'nights'=>int, 'room'=>array|null]
 */
function validate_and_price_room_booking($conn, $room_id, $checkin, $checkout, $guests) {
    $today = date('Y-m-d');

    if ($room_id <= 0) {
        return ['success' => false, 'error' => 'Invalid room selected.'];
    }
    if (empty($checkin) || empty($checkout)) {
        return ['success' => false, 'error' => 'Please select check-in and check-out dates.'];
    }
    if ($checkin < $today) {
        return ['success' => false, 'error' => 'Check-in cannot be in the past.'];
    }
    if ($checkin >= $checkout) {
        return ['success' => false, 'error' => 'Check-out must be after check-in.'];
    }

    $room_stmt = $conn->prepare("SELECT id, name, price, max_guests FROM rooms WHERE id = ? AND status = 'active'");
    $room_stmt->bind_param("i", $room_id);
    $room_stmt->execute();
    $room = $room_stmt->get_result()->fetch_assoc();
    if (!$room) {
        return ['success' => false, 'error' => 'Room not found.'];
    }
    if ($guests < 1 || $guests > $room['max_guests']) {
        return ['success' => false, 'error' => "Max guests allowed: {$room['max_guests']}"];
    }

    $check_stmt = $conn->prepare("
        SELECT COUNT(*) as booked
        FROM room_bookings
        WHERE room_id = ?
        AND status IN ('confirmed', 'pending')
        AND (
            (check_in <= ? AND check_out >= ?) OR
            (check_in BETWEEN ? AND ?) OR
            (check_out BETWEEN ? AND ?)
        )
    ");
    $check_stmt->bind_param("issssss", $room_id, $checkin, $checkin, $checkin, $checkout, $checkin, $checkout);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    if ($result['booked'] > 0) {
        return ['success' => false, 'error' => 'Selected dates are not available.'];
    }

    $date1 = new DateTime($checkin);
    $date2 = new DateTime($checkout);
    $nights = $date1->diff($date2)->days;
    $total_price = round($nights * $room['price'], 2);

    return [
        'success' => true,
        'error' => null,
        'total' => $total_price,
        'nights' => $nights,
        'room' => $room,
    ];
}

/**
 * Validates + prices an EVENT HALL / PACKAGE booking (both use the event_bookings table).
 * Returns: ['success'=>bool, 'error'=>string|null, 'total'=>float, 'hall'=>array|null, 'package'=>array|null]
 */
function validate_and_price_event_booking($conn, $hall_id, $package_id, $event_date, $guests) {
    if ($hall_id <= 0) {
        return ['success' => false, 'error' => 'Please select an event hall.'];
    }
    if ($package_id <= 0) {
        return ['success' => false, 'error' => 'Please select a valid package.'];
    }
    if (empty($event_date)) {
        return ['success' => false, 'error' => 'Please select an event date.'];
    }
    if ($event_date < date('Y-m-d')) {
        return ['success' => false, 'error' => 'Event date cannot be in the past.'];
    }

    $hall_stmt = $conn->prepare("SELECT id, name, capacity, base_price FROM event_halls WHERE id = ? AND status = 'active'");
    $hall_stmt->bind_param("i", $hall_id);
    $hall_stmt->execute();
    $hall = $hall_stmt->get_result()->fetch_assoc();
    if (!$hall) {
        return ['success' => false, 'error' => 'Invalid hall selected.'];
    }

    if ($guests < 1 || $guests > $hall['capacity']) {
        return ['success' => false, 'error' => "Number of guests must be between 1 and {$hall['capacity']}."];
    }

    $check_stmt = $conn->prepare("
        SELECT COUNT(*) as booked FROM event_bookings
        WHERE hall_id = ? AND event_date = ? AND status IN ('pending', 'approved')
    ");
    $check_stmt->bind_param("is", $hall_id, $event_date);
    $check_stmt->execute();
    $check = $check_stmt->get_result()->fetch_assoc();
    if ($check['booked'] > 0) {
        return ['success' => false, 'error' => 'This hall is already booked on the selected date. Please choose another date.'];
    }

    $pkg_stmt = $conn->prepare("SELECT id, name, price FROM packages WHERE id = ? AND status = 'active'");
    $pkg_stmt->bind_param("i", $package_id);
    $pkg_stmt->execute();
    $package = $pkg_stmt->get_result()->fetch_assoc();
    if (!$package) {
        return ['success' => false, 'error' => 'Invalid package selected.'];
    }

    // ✅ FIXED: total = hall base price + package price
    $total = round($hall['base_price'] + $package['price'], 2);

    return [
        'success' => true,
        'error' => null,
        'total' => $total,
        'hall' => $hall,
        'package' => $package,
    ];
}
?>