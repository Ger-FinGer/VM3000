<?php
class TeacherController
{
    public static function landing(): void
    {
        $pdo = db();
        $weeks = $pdo->query('SELECT name, slug FROM project_weeks ORDER BY id DESC')->fetchAll();
        render('landing', ['weeks' => $weeks]);
    }

    public static function handle(string $slug): void
    {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM project_weeks WHERE slug = ?');
        $stmt->execute([$slug]);
        $week = $stmt->fetch();
        if (!$week) {
            http_response_code(404);
            echo 'Project week not found.';
            return;
        }

        $action = $_GET['action'] ?? '';
        if ($action === 'logout') {
            AuthController::teacherLogout($slug);
            return;
        }

        if (empty($_SESSION['teacher_id']) || $_SESSION['project_week_id'] !== (int)$week['id']) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                self::login($week);
                return;
            }
            render('teacher/login', ['week' => $week]);
            return;
        }

        $teacherId = (int)$_SESSION['teacher_id'];
        $profileStmt = $pdo->prepare('SELECT * FROM week_teacher_profiles WHERE project_week_id = ? AND teacher_id = ?');
        $profileStmt->execute([$week['id'], $teacherId]);
        $profile = $profileStmt->fetch();

        if (!$profile || empty($profile['quota']) || empty($profile['password_set'])) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                self::setupProfile($week, $teacherId);
                return;
            }
            $classes = $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
            $usedHomeroomsStmt = $pdo->prepare('SELECT homeroom_class_id FROM week_teacher_profiles WHERE project_week_id = ? AND homeroom_class_id IS NOT NULL');
            $usedHomeroomsStmt->execute([$week['id']]);
            $usedHomerooms = array_column($usedHomeroomsStmt->fetchAll(), 'homeroom_class_id');
            render('teacher/setup', [
                'week' => $week,
                'classes' => $classes,
                'usedHomerooms' => $usedHomerooms,
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $postAction = $_POST['action'] ?? '';
            if ($postAction === 'booking_add') {
                self::addBooking($week, $teacherId);
            }
            if ($postAction === 'booking_leave') {
                self::leaveBooking($week, $teacherId);
            }
            if ($postAction === 'booking_update') {
                self::updateBooking($week, $teacherId);
            }
            if ($postAction === 'set_absent') {
                self::setAbsent($week, $teacherId);
            }
            if ($postAction === 'set_class_trip') {
                self::setClassTrip($week, $teacherId);
            }
            redirect('/' . $slug);
        }

        if ($action === 'download_csv') {
            self::downloadCsv($week, $teacherId);
            return;
        }

        self::timetable($week, $teacherId);
    }

    private static function login(array $week): void
    {
        csrf_verify();
        $pdo = db();
        $initials = strtoupper(trim($_POST['initials'] ?? ''));
        $password = $_POST['password'] ?? '';
        if ($initials === '') {
            flash('error', 'Initials required.');
            redirect('/' . $week['slug']);
        }

        $stmt = $pdo->prepare('SELECT * FROM teachers WHERE initials = ?');
        $stmt->execute([$initials]);
        $teacher = $stmt->fetch();

        if ($teacher) {
            if (!password_verify($password, $teacher['password_hash'] ?? '')) {
                flash('error', 'Invalid password.');
                redirect('/' . $week['slug']);
            }
        } else {
            if (!password_verify($password, $week['teacher_initial_password_hash'])) {
                flash('error', 'Initial password invalid.');
                redirect('/' . $week['slug']);
            }
            $stmt = $pdo->prepare('INSERT INTO teachers (initials) VALUES (?)');
            $stmt->execute([$initials]);
            $teacher = ['id' => $pdo->lastInsertId()];
        }

        login_teacher((int)$teacher['id'], (int)$week['id']);
        redirect('/' . $week['slug']);
    }

    private static function setupProfile(array $week, int $teacherId): void
    {
        csrf_verify();
        $pdo = db();
        $password = $_POST['new_password'] ?? '';
        $quota = (int)($_POST['quota'] ?? 0);
        $homeroom = $_POST['homeroom'] ?? 'no';
        $homeroomClassId = $homeroom === 'yes' ? (int)($_POST['homeroom_class_id'] ?? 0) : null;

        if ($password !== '') {
            $stmt = $pdo->prepare('UPDATE teachers SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $teacherId]);
        }

        $stmt = $pdo->prepare('INSERT INTO week_teacher_profiles (project_week_id, teacher_id, quota, homeroom_class_id, password_set) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE quota = VALUES(quota), homeroom_class_id = VALUES(homeroom_class_id), password_set = 1');
        $stmt->execute([$week['id'], $teacherId, $quota, $homeroomClassId]);
        flash('success', 'Profile saved.');
        redirect('/' . $week['slug']);
    }

    private static function timetable(array $week, int $teacherId): void
    {
        $pdo = db();
        $classOverviewId = (int)($_GET['class_id'] ?? 0);
        $daysStmt = $pdo->prepare('SELECT * FROM project_week_days WHERE project_week_id = ? ORDER BY day_date');
        $daysStmt->execute([$week['id']]);
        $days = $daysStmt->fetchAll();
        $classes = $pdo->query('SELECT * FROM classes ORDER BY name')->fetchAll();
        $rooms = $pdo->query('SELECT * FROM rooms ORDER BY name')->fetchAll();

        $bookingStmt = $pdo->prepare('SELECT b.*, c.name AS class_name, r.name AS room_name FROM bookings b JOIN classes c ON b.class_id = c.id LEFT JOIN rooms r ON b.room_id = r.id WHERE b.project_week_day_id IN (SELECT id FROM project_week_days WHERE project_week_id = ?)');
        $bookingStmt->execute([$week['id']]);
        $bookings = $bookingStmt->fetchAll();

        $bookingTeachersStmt = $pdo->prepare('SELECT t.initials FROM booking_teachers bt JOIN teachers t ON bt.teacher_id = t.id WHERE bt.booking_id = ? ORDER BY t.initials');
        $bookingTeachers = [];
        foreach ($bookings as $booking) {
            $bookingTeachersStmt->execute([$booking['id']]);
            $bookingTeachers[$booking['id']] = array_column($bookingTeachersStmt->fetchAll(), 'initials');
        }

        $profileStmt = $pdo->prepare('SELECT * FROM week_teacher_profiles WHERE project_week_id = ? AND teacher_id = ?');
        $profileStmt->execute([$week['id'], $teacherId]);
        $profile = $profileStmt->fetch();

        $absentStmt = $pdo->prepare('SELECT project_week_day_id FROM teacher_day_status WHERE project_week_id = ? AND teacher_id = ?');
        $absentStmt->execute([$week['id'], $teacherId]);
        $absentDays = array_column($absentStmt->fetchAll(), 'project_week_day_id');

        $classTripsStmt = $pdo->prepare('SELECT * FROM class_day_status WHERE project_week_id = ?');
        $classTripsStmt->execute([$week['id']]);
        $classTrips = $classTripsStmt->fetchAll();

        $classOverviewBookings = [];
        if ($classOverviewId) {
            $stmt = $pdo->prepare('SELECT b.*, d.day_date, r.name AS room_name FROM bookings b JOIN project_week_days d ON b.project_week_day_id = d.id LEFT JOIN rooms r ON b.room_id = r.id WHERE b.class_id = ? AND d.project_week_id = ?');
            $stmt->execute([$classOverviewId, $week['id']]);
            $classOverviewBookings = $stmt->fetchAll();
        }

        render('teacher/timetable', [
            'week' => $week,
            'days' => $days,
            'classes' => $classes,
            'rooms' => $rooms,
            'bookings' => $bookings,
            'bookingTeachers' => $bookingTeachers,
            'profile' => $profile,
            'absentDays' => $absentDays,
            'classTrips' => $classTrips,
            'classOverviewId' => $classOverviewId,
            'classOverviewBookings' => $classOverviewBookings,
        ]);
    }

    private static function addBooking(array $week, int $teacherId): void
    {
        $pdo = db();
        $dayId = (int)($_POST['day_id'] ?? 0);
        $period = (int)($_POST['period_index'] ?? 0);
        $classId = (int)($_POST['class_id'] ?? 0);
        if (!$dayId || !$period || !$classId) {
            return;
        }
        $stmt = $pdo->prepare('SELECT id FROM bookings WHERE project_week_day_id = ? AND period_index = ? AND class_id = ?');
        $stmt->execute([$dayId, $period, $classId]);
        $bookingId = $stmt->fetchColumn();
        if (!$bookingId) {
            $stmt = $pdo->prepare('INSERT INTO bookings (project_week_day_id, period_index, class_id) VALUES (?, ?, ?)');
            $stmt->execute([$dayId, $period, $classId]);
            $bookingId = $pdo->lastInsertId();
        }
        $stmt = $pdo->prepare('INSERT IGNORE INTO booking_teachers (booking_id, teacher_id) VALUES (?, ?)');
        $stmt->execute([$bookingId, $teacherId]);
    }

    private static function leaveBooking(array $week, int $teacherId): void
    {
        $pdo = db();
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        if (!$bookingId) {
            return;
        }
        $stmt = $pdo->prepare('DELETE FROM booking_teachers WHERE booking_id = ? AND teacher_id = ?');
        $stmt->execute([$bookingId, $teacherId]);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM booking_teachers WHERE booking_id = ?');
        $stmt->execute([$bookingId]);
        if ((int)$stmt->fetchColumn() === 0) {
            $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
            $stmt->execute([$bookingId]);
        }
    }

    private static function updateBooking(array $week, int $teacherId): void
    {
        $pdo = db();
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $roomId = (int)($_POST['room_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        if (!$bookingId) {
            return;
        }
        if ($roomId) {
            $stmt = $pdo->prepare('SELECT project_week_day_id, period_index FROM bookings WHERE id = ?');
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            if ($booking) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE project_week_day_id = ? AND period_index = ? AND room_id = ? AND id != ?');
                $stmt->execute([$booking['project_week_day_id'], $booking['period_index'], $roomId, $bookingId]);
                if ((int)$stmt->fetchColumn() > 0) {
                    flash('error', 'Room already booked for that slot.');
                    return;
                }
            }
        }
        $stmt = $pdo->prepare('UPDATE bookings SET room_id = ?, description = ? WHERE id = ?');
        $stmt->execute([$roomId ?: null, $description, $bookingId]);
    }

    private static function setAbsent(array $week, int $teacherId): void
    {
        $pdo = db();
        $dayId = (int)($_POST['day_id'] ?? 0);
        if (!$dayId) {
            return;
        }
        $stmt = $pdo->prepare('INSERT IGNORE INTO teacher_day_status (project_week_id, project_week_day_id, teacher_id, status) VALUES (?, ?, ?, "absent")');
        $stmt->execute([$week['id'], $dayId, $teacherId]);
        $stmt = $pdo->prepare('SELECT bt.booking_id FROM booking_teachers bt JOIN bookings b ON bt.booking_id = b.id WHERE bt.teacher_id = ? AND b.project_week_day_id = ?');
        $stmt->execute([$teacherId, $dayId]);
        $bookingIds = array_column($stmt->fetchAll(), 'booking_id');
        foreach ($bookingIds as $bookingId) {
            $stmt = $pdo->prepare('DELETE FROM booking_teachers WHERE booking_id = ? AND teacher_id = ?');
            $stmt->execute([$bookingId, $teacherId]);
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM booking_teachers WHERE booking_id = ?');
            $stmt->execute([$bookingId]);
            if ((int)$stmt->fetchColumn() === 0) {
                $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
                $stmt->execute([$bookingId]);
            }
        }
    }

    private static function setClassTrip(array $week, int $teacherId): void
    {
        $pdo = db();
        $dayId = (int)($_POST['day_id'] ?? 0);
        $classId = (int)($_POST['class_id'] ?? 0);
        if (!$dayId || !$classId) {
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO class_day_status (project_week_id, project_week_day_id, class_id, status) VALUES (?, ?, ?, "trip") ON DUPLICATE KEY UPDATE status = "trip"');
        $stmt->execute([$week['id'], $dayId, $classId]);
        $stmt = $pdo->prepare('SELECT id FROM bookings WHERE project_week_day_id = ? AND class_id = ?');
        $stmt->execute([$dayId, $classId]);
        $bookingIds = array_column($stmt->fetchAll(), 'id');
        foreach ($bookingIds as $bookingId) {
            $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
            $stmt->execute([$bookingId]);
        }
    }

    private static function downloadCsv(array $week, int $teacherId): void
    {
        $pdo = db();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=\"timetable.csv\"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Period', 'Class', 'Room', 'Description']);
        $stmt = $pdo->prepare('SELECT d.day_date, b.period_index, c.name AS class_name, r.name AS room_name, b.description FROM bookings b JOIN project_week_days d ON b.project_week_day_id = d.id JOIN classes c ON b.class_id = c.id LEFT JOIN rooms r ON b.room_id = r.id JOIN booking_teachers bt ON bt.booking_id = b.id WHERE bt.teacher_id = ? AND d.project_week_id = ? ORDER BY d.day_date, b.period_index');
        $stmt->execute([$teacherId, $week['id']]);
        foreach ($stmt as $row) {
            fputcsv($output, [
                $row['day_date'],
                $row['period_index'],
                $row['class_name'],
                $row['room_name'],
                $row['description'],
            ]);
        }
        fclose($output);
        exit;
    }
}
