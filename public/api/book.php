<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/api.php';

api_bootstrap();
[$teacherId, $weekId] = api_require_teacher();
$data = api_read_input();
api_verify_csrf($data);

$action = $data['action'] ?? '';

try {
    $pdo = db();

    if ($action === 'matrix') {
        $classId = (int)($data['class_id'] ?? 0);
        if (!$classId) {
            api_error('Missing class.');
        }

        $weekStmt = $pdo->prepare('SELECT periods_per_day FROM project_weeks WHERE id = ?');
        $weekStmt->execute([$weekId]);
        $week = $weekStmt->fetch();
        if (!$week) {
            api_error('Week not found.');
        }
        $periods = (int)$week['periods_per_day'];

        $daysStmt = $pdo->prepare('SELECT id, day_date FROM project_week_days WHERE project_week_id = ? ORDER BY day_date');
        $daysStmt->execute([$weekId]);
        $days = $daysStmt->fetchAll();
        $dayIds = array_column($days, 'id');
        if (!$dayIds) {
            api_json(['ok' => true, 'days' => [], 'periods' => $periods, 'slots' => []]);
        }

        $placeholders = implode(',', array_fill(0, count($dayIds), '?'));
        $bookingStmt = $pdo->prepare('SELECT b.id, b.project_week_day_id, b.period_index, b.booking_type FROM bookings b WHERE b.class_id = ? AND b.project_week_day_id IN (' . $placeholders . ')');
        $bookingStmt->execute(array_merge([$classId], $dayIds));
        $bookings = $bookingStmt->fetchAll();

        $bookingBySlot = [];
        foreach ($bookings as $booking) {
            $bookingBySlot[$booking['project_week_day_id']][$booking['period_index']][] = $booking;
        }

        $myBookingStmt = $pdo->prepare('SELECT b.id, b.project_week_day_id, b.period_index FROM bookings b JOIN booking_teachers bt ON bt.booking_id = b.id WHERE bt.teacher_id = ? AND b.class_id = ? AND b.project_week_day_id IN (' . $placeholders . ')');
        $myBookingStmt->execute(array_merge([$teacherId, $classId], $dayIds));
        $myBookings = $myBookingStmt->fetchAll();
        $myBookingMap = [];
        foreach ($myBookings as $booking) {
            $myBookingMap[$booking['project_week_day_id']][$booking['period_index']] = (int)$booking['id'];
        }

        $planStmt = $pdo->prepare('SELECT project_week_day_id, period_index, description FROM slot_plans WHERE class_id = ? AND project_week_day_id IN (' . $placeholders . ')');
        $planStmt->execute(array_merge([$classId], $dayIds));
        $planMap = [];
        foreach ($planStmt as $row) {
            $planMap[$row['project_week_day_id']][$row['period_index']] = $row['description'];
        }

        $roomStmt = $pdo->prepare('SELECT sr.project_week_day_id, sr.period_index, sr.room_id, r.name FROM slot_rooms sr JOIN rooms r ON sr.room_id = r.id WHERE sr.class_id = ? AND sr.project_week_day_id IN (' . $placeholders . ')');
        $roomStmt->execute(array_merge([$classId], $dayIds));
        $roomMap = [];
        foreach ($roomStmt as $row) {
            $roomMap[$row['project_week_day_id']][$row['period_index']] = [
                'room_id' => (int)$row['room_id'],
                'room_name' => $row['name'],
            ];
        }

        $absenceStmt = $pdo->prepare('SELECT project_week_day_id, period_index, all_day FROM absences WHERE project_week_id = ? AND teacher_id = ? AND project_week_day_id IN (' . $placeholders . ')');
        $absenceStmt->execute(array_merge([$weekId, $teacherId], $dayIds));
        $absenceMap = [];
        foreach ($absenceStmt as $row) {
            if ((int)$row['all_day'] === 1) {
                $absenceMap[$row['project_week_day_id']]['all_day'] = true;
            } else {
                $absenceMap[$row['project_week_day_id']][(int)$row['period_index']] = true;
            }
        }

        $slots = [];
        foreach ($days as $day) {
            $dayId = (int)$day['id'];
            for ($period = 1; $period <= $periods; $period++) {
                $slotBookings = $bookingBySlot[$dayId][$period] ?? [];
                $hasBooking = !empty($slotBookings);
                $mineId = $myBookingMap[$dayId][$period] ?? null;
                $isAbsent = !empty($absenceMap[$dayId]['all_day']) || !empty($absenceMap[$dayId][$period]);
                $state = 'free';
                if ($mineId) {
                    $state = 'mine';
                } elseif ($hasBooking) {
                    $state = 'taken';
                } elseif ($isAbsent) {
                    $state = 'busy';
                }

                $isTrip = false;
                foreach ($slotBookings as $booking) {
                    if ($booking['booking_type'] === 'trip') {
                        $isTrip = true;
                        break;
                    }
                }

                $planText = $planMap[$dayId][$period] ?? '';
                $roomInfo = $roomMap[$dayId][$period] ?? null;

                $slots[] = [
                    'day' => $dayId,
                    'period' => $period,
                    'state' => $state,
                    'booking_id' => $mineId,
                    'plan_text' => $planText,
                    'room_id' => $roomInfo['room_id'] ?? null,
                    'room_name' => $roomInfo['room_name'] ?? null,
                    'is_trip' => $isTrip,
                ];
            }
        }

        api_json([
            'ok' => true,
            'days' => $days,
            'periods' => $periods,
            'slots' => $slots,
        ]);
    }

    $classId = (int)($data['class_id'] ?? 0);
    $dayId = (int)($data['day'] ?? 0);
    $period = (int)($data['period'] ?? 0);
    if (!$classId || !$dayId || !$period) {
        api_error('Missing booking data.');
    }

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

    $bookingStmt = $pdo->prepare('SELECT id FROM bookings WHERE project_week_day_id = ? AND period_index = ? AND class_id = ? LIMIT 1');
    $bookingStmt->execute([$dayId, $period, $classId]);
    $bookingId = $bookingStmt->fetchColumn();
    if (!$bookingId) {
        $insert = $pdo->prepare('INSERT INTO bookings (project_week_day_id, period_index, class_id, booking_type) VALUES (?, ?, ?, "normal")');
        $insert->execute([$dayId, $period, $classId]);
        $bookingId = $pdo->lastInsertId();
    }

    $joinStmt = $pdo->prepare('INSERT IGNORE INTO booking_teachers (booking_id, teacher_id) VALUES (?, ?)');
    $joinStmt->execute([$bookingId, $teacherId]);

    api_json(['ok' => true, 'booking_id' => (int)$bookingId]);
} catch (Throwable $e) {
    api_error('Booking failed.');
}
