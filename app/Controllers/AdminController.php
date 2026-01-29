<?php
class AdminController
{
    public static function dashboard(): void
    {
        require_admin();
        $pdo = db();
        $weeks = $pdo->query('SELECT * FROM project_weeks ORDER BY id DESC')->fetchAll();
        render('admin/dashboard', [
            'weeks' => $weeks,
        ]);
    }

    public static function classes(): void
    {
        require_admin();
        $pdo = db();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $name = trim($_POST['name'] ?? '');
            $action = $_POST['action'] ?? '';
            if ($action === 'create' && $name !== '') {
                $stmt = $pdo->prepare('INSERT INTO classes (name) VALUES (?)');
                $stmt->execute([$name]);
                flash('success', 'Class created.');
            }
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM classes WHERE id = ?');
                $stmt->execute([$id]);
                flash('success', 'Class deleted.');
            }
            redirect('/admin/classes');
        }

        $classes = $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
        render('admin/classes', ['classes' => $classes]);
    }

    public static function rooms(): void
    {
        require_admin();
        $pdo = db();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $name = trim($_POST['name'] ?? '');
            $action = $_POST['action'] ?? '';
            if ($action === 'create' && $name !== '') {
                $stmt = $pdo->prepare('INSERT INTO rooms (name) VALUES (?)');
                $stmt->execute([$name]);
                flash('success', 'Room created.');
            }
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM rooms WHERE id = ?');
                $stmt->execute([$id]);
                flash('success', 'Room deleted.');
            }
            redirect('/admin/rooms');
        }

        $rooms = $pdo->query('SELECT * FROM rooms ORDER BY name')->fetchAll();
        render('admin/rooms', ['rooms' => $rooms]);
    }

    public static function projectWeeks(): void
    {
        require_admin();
        $pdo = db();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $name = trim($_POST['name'] ?? '');
                $slug = strtoupper(trim($_POST['slug'] ?? ''));
                $dates = array_filter(array_map('trim', explode(',', $_POST['dates'] ?? '')));
                $periods = (int)($_POST['periods_per_day'] ?? 6);
                $initialPassword = $_POST['initial_password'] ?? '';
                if ($name && $slug && $dates && $initialPassword) {
                    $stmt = $pdo->prepare('INSERT INTO project_weeks (name, slug, periods_per_day, teacher_initial_password_hash) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$name, $slug, $periods, password_hash($initialPassword, PASSWORD_DEFAULT)]);
                    $weekId = (int)$pdo->lastInsertId();
                    $insertDay = $pdo->prepare('INSERT INTO project_week_days (project_week_id, day_date) VALUES (?, ?)');
                    foreach ($dates as $date) {
                        $insertDay->execute([$weekId, $date]);
                    }
                    flash('success', 'Project week created.');
                }
            }
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM project_weeks WHERE id = ?');
                $stmt->execute([$id]);
                flash('success', 'Project week deleted.');
            }
            redirect('/admin/project-weeks');
        }

        $weeks = $pdo->query('SELECT * FROM project_weeks ORDER BY id DESC')->fetchAll();
        render('admin/project-weeks', ['weeks' => $weeks]);
    }

    public static function bookings(): void
    {
        require_admin();
        $pdo = db();
        $weekId = (int)($_GET['week_id'] ?? 0);
        $weeks = $pdo->query('SELECT * FROM project_weeks ORDER BY id DESC')->fetchAll();
        if ($weekId === 0 && $weeks) {
            $weekId = (int)$weeks[0]['id'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $action = $_POST['action'] ?? '';
            if ($action === 'create') {
                $dayId = (int)($_POST['day_id'] ?? 0);
                $period = (int)($_POST['period_index'] ?? 0);
                $classId = (int)($_POST['class_id'] ?? 0);
                $teacherId = (int)($_POST['teacher_id'] ?? 0);
                $roomId = (int)($_POST['room_id'] ?? 0);
                $description = trim($_POST['description'] ?? '');
                if ($dayId && $period && $classId) {
                    if ($roomId) {
                        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE project_week_day_id = ? AND period_index = ? AND room_id = ?');
                        $checkStmt->execute([$dayId, $period, $roomId]);
                        if ((int)$checkStmt->fetchColumn() > 0) {
                            flash('error', 'Room already booked for that slot.');
                            redirect('/admin/bookings?week_id=' . $weekId);
                        }
                    }
                    $stmt = $pdo->prepare('SELECT id FROM bookings WHERE project_week_day_id = ? AND period_index = ? AND class_id = ?');
                    $stmt->execute([$dayId, $period, $classId]);
                    $bookingId = $stmt->fetchColumn();
                    if (!$bookingId) {
                        $stmt = $pdo->prepare('INSERT INTO bookings (project_week_day_id, period_index, class_id, room_id, description) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute([$dayId, $period, $classId, $roomId ?: null, $description]);
                        $bookingId = $pdo->lastInsertId();
                    }
                    if ($teacherId) {
                        $stmt = $pdo->prepare('INSERT IGNORE INTO booking_teachers (booking_id, teacher_id) VALUES (?, ?)');
                        $stmt->execute([$bookingId, $teacherId]);
                    }
                }
            }
            if ($action === 'delete') {
                $bookingId = (int)($_POST['booking_id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
                $stmt->execute([$bookingId]);
            }
            redirect('/admin/bookings?week_id=' . $weekId);
        }

        $classes = $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
        $rooms = $pdo->query('SELECT * FROM rooms ORDER BY name')->fetchAll();
        $teacherStmt = $pdo->prepare('SELECT t.id, t.initials, COALESCE(wtp.quota, 0) AS quota, COUNT(DISTINCT bt.booking_id) AS booked FROM teachers t LEFT JOIN week_teacher_profiles wtp ON wtp.teacher_id = t.id AND wtp.project_week_id = ? LEFT JOIN booking_teachers bt ON bt.teacher_id = t.id LEFT JOIN bookings b ON b.id = bt.booking_id LEFT JOIN project_week_days d ON b.project_week_day_id = d.id AND d.project_week_id = ? GROUP BY t.id, t.initials, wtp.quota ORDER BY (COALESCE(wtp.quota, 0) - COUNT(DISTINCT bt.booking_id)) <= 0, t.initials');
        $teacherStmt->execute([$weekId, $weekId]);
        $teachers = $teacherStmt->fetchAll();
        $daysStmt = $pdo->prepare('SELECT * FROM project_week_days WHERE project_week_id = ? ORDER BY day_date');
        $daysStmt->execute([$weekId]);
        $days = $daysStmt->fetchAll();
        $bookingsStmt = $pdo->prepare('SELECT b.*, c.name AS class_name, r.name AS room_name FROM bookings b JOIN classes c ON b.class_id = c.id LEFT JOIN rooms r ON b.room_id = r.id WHERE b.project_week_day_id IN (SELECT id FROM project_week_days WHERE project_week_id = ?)');
        $bookingsStmt->execute([$weekId]);
        $bookings = $bookingsStmt->fetchAll();

        render('admin/bookings', [
            'weeks' => $weeks,
            'weekId' => $weekId,
            'classes' => $classes,
            'rooms' => $rooms,
            'teachers' => $teachers,
            'days' => $days,
            'bookings' => $bookings,
        ]);
    }

    public static function exports(): void
    {
        require_admin();
        $pdo = db();
        $type = $_GET['type'] ?? '';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $type . '.csv"');
        $output = fopen('php://output', 'w');
        if ($type === 'teachers') {
            fputcsv($output, ['Initials']);
            foreach ($pdo->query('SELECT initials FROM teachers ORDER BY initials') as $row) {
                fputcsv($output, [$row['initials']]);
            }
        }
        if ($type === 'classes') {
            fputcsv($output, ['Class']);
            foreach ($pdo->query('SELECT name FROM classes ORDER BY name') as $row) {
                fputcsv($output, [$row['name']]);
            }
        }
        if ($type === 'rooms') {
            fputcsv($output, ['Room']);
            foreach ($pdo->query('SELECT name FROM rooms ORDER BY name') as $row) {
                fputcsv($output, [$row['name']]);
            }
        }
        if ($type === 'overview') {
            fputcsv($output, ['Class', 'Day', 'Period', 'Teachers', 'Room', 'Description']);
            $stmt = $pdo->query('SELECT b.id, c.name AS class_name, d.day_date, b.period_index, r.name AS room_name, b.description FROM bookings b JOIN classes c ON b.class_id = c.id JOIN project_week_days d ON b.project_week_day_id = d.id LEFT JOIN rooms r ON b.room_id = r.id ORDER BY c.name, d.day_date, b.period_index');
            $teacherStmt = $pdo->prepare('SELECT t.initials FROM booking_teachers bt JOIN teachers t ON bt.teacher_id = t.id WHERE bt.booking_id = ? ORDER BY t.initials');
            foreach ($stmt as $row) {
                $teacherStmt->execute([$row['id']]);
                $teachers = array_column($teacherStmt->fetchAll(), 'initials');
                fputcsv($output, [
                    $row['class_name'],
                    $row['day_date'],
                    $row['period_index'],
                    implode(' ', $teachers),
                    $row['room_name'],
                    $row['description'],
                ]);
            }
        }
        fclose($output);
        exit;
    }
}
