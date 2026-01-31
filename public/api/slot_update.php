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
$planText = trim((string)($data['plan_text'] ?? ''));
$roomId = (int)($data['room_id'] ?? 0);
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

    $bookingStmt = $pdo->prepare('SELECT id FROM bookings WHERE project_week_day_id = ? AND period_index = ? AND class_id = ?');
    $bookingStmt->execute([$dayId, $period, $classId]);
    $bookingIds = array_column($bookingStmt->fetchAll(), 'id');
    if (!$bookingIds) {
        api_error('No booking for slot.');
    }

    $isBookedStmt = $pdo->prepare('SELECT COUNT(*) FROM bookings b JOIN booking_teachers bt ON bt.booking_id = b.id WHERE bt.teacher_id = ? AND b.project_week_day_id = ? AND b.period_index = ? AND b.class_id = ?');
    $isBookedStmt->execute([$teacherId, $dayId, $period, $classId]);
    $isBookedByMe = (int)$isBookedStmt->fetchColumn() > 0;
    $canEdit = !empty($_SESSION['admin_id']) || $isBookedByMe;
    if (!$canEdit) {
        api_error('Not allowed.');
    }

    if ($planText !== '') {
        $planStmt = $pdo->prepare('INSERT INTO slot_plans (class_id, project_week_day_id, period_index, description, updated_by, updated_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE description = VALUES(description), updated_by = VALUES(updated_by), updated_at = NOW()');
        $planStmt->execute([$classId, $dayId, $period, $planText, $teacherId]);
    } else {
        $deletePlan = $pdo->prepare('DELETE FROM slot_plans WHERE class_id = ? AND project_week_day_id = ? AND period_index = ?');
        $deletePlan->execute([$classId, $dayId, $period]);
    }

    if ($roomId) {
        $roomCheck = $pdo->prepare('SELECT id FROM rooms WHERE id = ? AND active = 1');
        $roomCheck->execute([$roomId]);
        if (!$roomCheck->fetchColumn()) {
            api_error('Room not available.');
        }
        $roomStmt = $pdo->prepare('INSERT INTO slot_rooms (class_id, project_week_day_id, period_index, room_id, updated_by, updated_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE room_id = VALUES(room_id), updated_by = VALUES(updated_by), updated_at = NOW()');
        $roomStmt->execute([$classId, $dayId, $period, $roomId, $teacherId]);
    } else {
        $deleteRoom = $pdo->prepare('DELETE FROM slot_rooms WHERE class_id = ? AND project_week_day_id = ? AND period_index = ?');
        $deleteRoom->execute([$classId, $dayId, $period]);
    }

    api_json(['ok' => true]);
} catch (Throwable $e) {
    api_error('Failed to update slot.');
}
