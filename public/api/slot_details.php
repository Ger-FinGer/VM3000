<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/api.php';

api_bootstrap();
[$teacherId, $weekId] = api_require_teacher();
$data = api_read_input();
api_verify_csrf($data);

$classId = (int)($data['class_id'] ?? 0);
$dayId = (int)($data['day'] ?? 0);
$period = (int)($data['period'] ?? 0);
if (!$classId || !$dayId || !$period) {
    api_error('Missing slot data.');
}

try {
    $pdo = db();
    $dayCheck = $pdo->prepare('SELECT COUNT(*) FROM project_week_days WHERE id = ? AND project_week_id = ?');
    $dayCheck->execute([$dayId, $weekId]);
    if ((int)$dayCheck->fetchColumn() === 0) {
        api_error('Invalid day.');
    }

    $bookingStmt = $pdo->prepare('SELECT id, booking_type FROM bookings WHERE project_week_day_id = ? AND period_index = ? AND class_id = ?');
    $bookingStmt->execute([$dayId, $period, $classId]);
    $bookings = $bookingStmt->fetchAll();
    $bookingIds = array_column($bookings, 'id');
    $slotHasBooking = !empty($bookingIds);
    $slotTypeSummary = null;
    foreach ($bookings as $booking) {
        if ($booking['booking_type'] === 'trip') {
            $slotTypeSummary = 'trip';
            break;
        }
    }
    if ($slotHasBooking && $slotTypeSummary === null) {
        $slotTypeSummary = 'normal';
    }

    $participants = [];
    $isBookedByMe = false;
    $myBookingId = null;
    if ($bookingIds) {
        $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
        $participantStmt = $pdo->prepare('SELECT DISTINCT t.id, t.initials FROM booking_teachers bt JOIN teachers t ON bt.teacher_id = t.id WHERE bt.booking_id IN (' . $placeholders . ') ORDER BY t.initials');
        $participantStmt->execute($bookingIds);
        $participants = $participantStmt->fetchAll();

        $mineStmt = $pdo->prepare('SELECT b.id FROM bookings b JOIN booking_teachers bt ON bt.booking_id = b.id WHERE bt.teacher_id = ? AND b.project_week_day_id = ? AND b.period_index = ? AND b.class_id = ? LIMIT 1');
        $mineStmt->execute([$teacherId, $dayId, $period, $classId]);
        $myBookingId = $mineStmt->fetchColumn();
        $isBookedByMe = (bool)$myBookingId;
    }

    $planStmt = $pdo->prepare('SELECT description FROM slot_plans WHERE class_id = ? AND project_week_day_id = ? AND period_index = ?');
    $planStmt->execute([$classId, $dayId, $period]);
    $planText = $planStmt->fetchColumn() ?: '';

    $roomStmt = $pdo->prepare('SELECT sr.room_id, r.name FROM slot_rooms sr JOIN rooms r ON sr.room_id = r.id WHERE sr.class_id = ? AND sr.project_week_day_id = ? AND sr.period_index = ?');
    $roomStmt->execute([$classId, $dayId, $period]);
    $roomRow = $roomStmt->fetch();

    $roomsStmt = $pdo->query('SELECT id, name FROM rooms WHERE active = 1 ORDER BY name');
    $rooms = $roomsStmt->fetchAll();

    $canEdit = !empty($_SESSION['admin_id']) || $isBookedByMe;

    api_json([
        'ok' => true,
        'plan_text' => $planText,
        'room_id' => $roomRow['room_id'] ?? null,
        'room_name' => $roomRow['name'] ?? null,
        'rooms' => $rooms,
        'participants' => $participants,
        'can_edit' => $canEdit,
        'is_booked_by_me' => $isBookedByMe,
        'my_booking_id' => $myBookingId ? (int)$myBookingId : null,
        'slot_has_any_booking' => $slotHasBooking,
        'slot_type_summary' => $slotTypeSummary,
    ]);
} catch (Throwable $e) {
    api_error('Failed to load slot details.');
}
