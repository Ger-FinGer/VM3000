<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/api.php';

api_bootstrap();
[$teacherId, $weekId] = api_require_teacher();
$data = api_read_input();
api_verify_csrf($data);

try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT b.id AS booking_id, b.class_id, c.name AS class_name, b.project_week_day_id, d.day_date, b.period_index, b.booking_type FROM bookings b JOIN booking_teachers bt ON bt.booking_id = b.id JOIN classes c ON b.class_id = c.id JOIN project_week_days d ON b.project_week_day_id = d.id WHERE bt.teacher_id = ? AND d.project_week_id = ? ORDER BY d.day_date, b.period_index');
    $stmt->execute([$teacherId, $weekId]);
    $bookings = $stmt->fetchAll();

    $bookingIds = array_column($bookings, 'booking_id');
    $planMap = [];
    $roomMap = [];
    if ($bookingIds) {
        $slotStmt = $pdo->prepare('SELECT b.id, b.class_id, b.project_week_day_id, b.period_index FROM bookings b WHERE b.id IN (' . implode(',', array_fill(0, count($bookingIds), '?')) . ')');
        $slotStmt->execute($bookingIds);
        $slots = $slotStmt->fetchAll();
        $slotKeys = [];
        foreach ($slots as $slot) {
            $slotKeys[] = [$slot['class_id'], $slot['project_week_day_id'], $slot['period_index']];
        }
        foreach ($slotKeys as $slotKey) {
            [$classId, $dayId, $period] = $slotKey;
            $planStmt = $pdo->prepare('SELECT description FROM slot_plans WHERE class_id = ? AND project_week_day_id = ? AND period_index = ?');
            $planStmt->execute([$classId, $dayId, $period]);
            $planMap[$classId][$dayId][$period] = $planStmt->fetchColumn() ?: '';

            $roomStmt = $pdo->prepare('SELECT sr.room_id, r.name FROM slot_rooms sr JOIN rooms r ON sr.room_id = r.id WHERE sr.class_id = ? AND sr.project_week_day_id = ? AND sr.period_index = ?');
            $roomStmt->execute([$classId, $dayId, $period]);
            $roomMap[$classId][$dayId][$period] = $roomStmt->fetch();
        }
    }

    $items = [];
    foreach ($bookings as $booking) {
        $planText = $planMap[$booking['class_id']][$booking['project_week_day_id']][$booking['period_index']] ?? '';
        $roomRow = $roomMap[$booking['class_id']][$booking['project_week_day_id']][$booking['period_index']] ?? null;
        $items[] = [
            'booking_id' => (int)$booking['booking_id'],
            'class_id' => (int)$booking['class_id'],
            'class_name' => $booking['class_name'],
            'day' => (int)$booking['project_week_day_id'],
            'day_date' => $booking['day_date'],
            'period' => (int)$booking['period_index'],
            'is_trip' => $booking['booking_type'] === 'trip',
            'plan_text' => $planText,
            'room_id' => $roomRow['room_id'] ?? null,
            'room_name' => $roomRow['name'] ?? null,
        ];
    }

    api_json(['ok' => true, 'bookings' => $items]);
} catch (Throwable $e) {
    api_error('Failed to load timetable.');
}
