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
    api_error('Missing trip data.');
}

try {
    $pdo = db();
    $dayCheck = $pdo->prepare('SELECT COUNT(*) FROM project_week_days WHERE id = ? AND project_week_id = ?');
    $dayCheck->execute([$dayId, $weekId]);
    if ((int)$dayCheck->fetchColumn() === 0) {
        api_error('Invalid day.');
    }

    $absenceCheck = $pdo->prepare('SELECT COUNT(*) FROM absences WHERE project_week_id = ? AND teacher_id = ? AND project_week_day_id = ? AND (all_day = 1 OR period_index = ?)');
    $absenceCheck->execute([$weekId, $teacherId, $dayId, $period]);
    if ((int)$absenceCheck->fetchColumn() > 0) {
        api_error('You are marked absent for this slot.');
    }

    $bookingStmt = $pdo->prepare('SELECT id, booking_type FROM bookings WHERE project_week_day_id = ? AND period_index = ? AND class_id = ? LIMIT 1');
    $bookingStmt->execute([$dayId, $period, $classId]);
    $booking = $bookingStmt->fetch();
    if ($booking) {
        if ($booking['booking_type'] !== 'trip') {
            $update = $pdo->prepare('UPDATE bookings SET booking_type = "trip" WHERE id = ?');
            $update->execute([$booking['id']]);
        }
        $bookingId = $booking['id'];
    } else {
        $insert = $pdo->prepare('INSERT INTO bookings (project_week_day_id, period_index, class_id, booking_type) VALUES (?, ?, ?, "trip")');
        $insert->execute([$dayId, $period, $classId]);
        $bookingId = $pdo->lastInsertId();
    }

    $joinStmt = $pdo->prepare('INSERT IGNORE INTO booking_teachers (booking_id, teacher_id) VALUES (?, ?)');
    $joinStmt->execute([$bookingId, $teacherId]);

    api_json(['ok' => true, 'booking_id' => (int)$bookingId]);
} catch (Throwable $e) {
    api_error('Failed to set trip.');
}
