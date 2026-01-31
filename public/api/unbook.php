<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/api.php';

api_bootstrap();
[$teacherId, $weekId] = api_require_teacher();
$data = api_read_input();
api_verify_csrf($data);

$bookingId = (int)($data['booking_id'] ?? 0);
$classId = (int)($data['class_id'] ?? 0);
$dayId = (int)($data['day'] ?? 0);
$period = (int)($data['period'] ?? 0);

try {
    $pdo = db();

    if (!$bookingId && $classId && $dayId && $period) {
        $stmt = $pdo->prepare('SELECT b.id FROM bookings b JOIN booking_teachers bt ON bt.booking_id = b.id WHERE bt.teacher_id = ? AND b.class_id = ? AND b.project_week_day_id = ? AND b.period_index = ? LIMIT 1');
        $stmt->execute([$teacherId, $classId, $dayId, $period]);
        $bookingId = (int)$stmt->fetchColumn();
    }

    if (!$bookingId) {
        api_error('Booking not found.');
    }

    $slotStmt = $pdo->prepare('SELECT class_id, project_week_day_id, period_index FROM bookings WHERE id = ?');
    $slotStmt->execute([$bookingId]);
    $slot = $slotStmt->fetch();
    if (!$slot) {
        api_error('Booking not found.');
    }

    $deleteTeacher = $pdo->prepare('DELETE FROM booking_teachers WHERE booking_id = ? AND teacher_id = ?');
    $deleteTeacher->execute([$bookingId, $teacherId]);

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM booking_teachers WHERE booking_id = ?');
    $countStmt->execute([$bookingId]);
    if ((int)$countStmt->fetchColumn() === 0) {
        $deleteBooking = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
        $deleteBooking->execute([$bookingId]);
    }

    $remainingStmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE class_id = ? AND project_week_day_id = ? AND period_index = ?');
    $remainingStmt->execute([$slot['class_id'], $slot['project_week_day_id'], $slot['period_index']]);
    if ((int)$remainingStmt->fetchColumn() === 0) {
        $deletePlan = $pdo->prepare('DELETE FROM slot_plans WHERE class_id = ? AND project_week_day_id = ? AND period_index = ?');
        $deletePlan->execute([$slot['class_id'], $slot['project_week_day_id'], $slot['period_index']]);
        $deleteRoom = $pdo->prepare('DELETE FROM slot_rooms WHERE class_id = ? AND project_week_day_id = ? AND period_index = ?');
        $deleteRoom->execute([$slot['class_id'], $slot['project_week_day_id'], $slot['period_index']]);
    }

    api_json(['ok' => true]);
} catch (Throwable $e) {
    api_error('Unbooking failed.');
}
