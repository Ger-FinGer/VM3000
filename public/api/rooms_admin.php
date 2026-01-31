<?php
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/api.php';

api_bootstrap();
$adminId = api_require_admin();
$data = api_read_input();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_verify_csrf($data);
}

try {
    $pdo = db();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $rooms = $pdo->query('SELECT id, name, active FROM rooms ORDER BY name')->fetchAll();
        api_json(['ok' => true, 'rooms' => $rooms]);
    }

    $action = $data['action'] ?? '';
    if ($action === 'create') {
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') {
            api_error('Name required.');
        }
        $stmt = $pdo->prepare('INSERT INTO rooms (name, active, created_at) VALUES (?, 1, NOW())');
        $stmt->execute([$name]);
        api_json(['ok' => true]);
    }

    if ($action === 'delete') {
        $roomId = (int)($data['id'] ?? 0);
        if (!$roomId) {
            api_error('Room missing.');
        }
        $check = $pdo->prepare('SELECT COUNT(*) FROM slot_rooms WHERE room_id = ?');
        $check->execute([$roomId]);
        if ((int)$check->fetchColumn() > 0) {
            api_error('Room is reserved and cannot be deleted.');
        }
        $stmt = $pdo->prepare('DELETE FROM rooms WHERE id = ?');
        $stmt->execute([$roomId]);
        api_json(['ok' => true]);
    }

    if ($action === 'toggle') {
        $roomId = (int)($data['id'] ?? 0);
        $active = (int)($data['active'] ?? 1);
        if (!$roomId) {
            api_error('Room missing.');
        }
        $stmt = $pdo->prepare('UPDATE rooms SET active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $roomId]);
        api_json(['ok' => true]);
    }

    api_error('Unknown action.');
} catch (Throwable $e) {
    api_error('Room update failed.');
}
