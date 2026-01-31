<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/api.php';

api_bootstrap();
[$teacherId, $weekId] = api_require_teacher();
$data = api_read_input();
api_verify_csrf($data);

$dayId = (int)($data['day'] ?? 0);
$period = (int)($data['period'] ?? 0);
$allDay = !empty($data['all_day']);
$confirm = !empty($data['confirm']);

if (!$dayId) {
    api_error('Missing day.');
}

try {
    $pdo = db();
    $dayCheck = $pdo->prepare('SELECT COUNT(*) FROM project_week_days WHERE id = ? AND project_week_id = ?');
    $dayCheck->execute([$dayId, $weekId]);
    if ((int)$dayCheck->fetchColumn() === 0) {
        api_error('Invalid day.');
    }

    $bookingsQuery = 'SELECT b.id, b.class_id, b.project_week_day_id, b.period_index FROM bookings b JOIN booking_teachers bt ON bt.booking_id = b.id WHERE bt.teacher_id = ? AND b.project_week_day_id = ?';
    $params = [$teacherId, $dayId];
    if (!$allDay) {
        $bookingsQuery .= ' AND b.period_index = ?';
        $params[] = $period;
    }
    $bookingStmt = $pdo->prepare($bookingsQuery);
    $bookingStmt->execute($params);
    $affectedBookings = $bookingStmt->fetchAll();

    if ($affectedBookings && !$confirm) {
        api_json([
            'ok' => false,
            'error' => 'Existing bookings found.',
            'needs_confirm' => true,
            'booking_count' => count($affectedBookings),
        ]);
    }

    foreach ($affectedBookings as $booking) {
        $deleteTeacher = $pdo->prepare('DELETE FROM booking_teachers WHERE booking_id = ? AND teacher_id = ?');
        $deleteTeacher->execute([$booking['id'], $teacherId]);
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM booking_teachers WHERE booking_id = ?');
        $countStmt->execute([$booking['id']]);
        if ((int)$countStmt->fetchColumn() === 0) {
            $deleteBooking = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
            $deleteBooking->execute([$booking['id']]);
            $remainingStmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE class_id = ? AND project_week_day_id = ? AND period_index = ?');
            $remainingStmt->execute([$booking['class_id'], $booking['project_week_day_id'], $booking['period_index']]);
            if ((int)$remainingStmt->fetchColumn() === 0) {
                $deletePlan = $pdo->prepare('DELETE FROM slot_plans WHERE class_id = ? AND project_week_day_id = ? AND period_index = ?');
                $deletePlan->execute([$booking['class_id'], $booking['project_week_day_id'], $booking['period_index']]);
                $deleteRoom = $pdo->prepare('DELETE FROM slot_rooms WHERE class_id = ? AND project_week_day_id = ? AND period_index = ?');
                $deleteRoom->execute([$booking['class_id'], $booking['project_week_day_id'], $booking['period_index']]);
            }
        }
    }

    if ($allDay) {
        $insert = $pdo->prepare('INSERT INTO absences (project_week_id, project_week_day_id, teacher_id, all_day, created_at) VALUES (?, ?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE all_day = 1');
        $insert->execute([$weekId, $dayId, $teacherId]);
    } else {
        if (!$period) {
            api_error('Missing period.');
        }
        $insert = $pdo->prepare('INSERT INTO absences (project_week_id, project_week_day_id, teacher_id, all_day, period_index, created_at) VALUES (?, ?, ?, 0, ?, NOW()) ON DUPLICATE KEY UPDATE all_day = 0');
        $insert->execute([$weekId, $dayId, $teacherId, $period]);
    }

    api_json(['ok' => true]);
} catch (Throwable $e) {
    api_error('Failed to set absence.');
}
