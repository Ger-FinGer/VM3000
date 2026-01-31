<?php
function api_bootstrap(): void
{
    header('Content-Type: application/json; charset=utf-8');
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

function api_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_error(string $message, int $status = 200): void
{
    api_json(['ok' => false, 'error' => $message], $status);
}

function api_require_teacher(): array
{
    if (empty($_SESSION['teacher_id']) || empty($_SESSION['project_week_id'])) {
        api_error('Unauthorized', 401);
    }
    return [(int)$_SESSION['teacher_id'], (int)$_SESSION['project_week_id']];
}

function api_require_admin(): int
{
    if (empty($_SESSION['admin_id'])) {
        api_error('Unauthorized', 401);
    }
    return (int)$_SESSION['admin_id'];
}

function api_read_input(): array
{
    $data = [];
    if (!empty($_POST)) {
        $data = $_POST;
    }
    $raw = file_get_contents('php://input');
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $data = array_merge($data, $decoded);
        }
    }
    return $data;
}

function api_verify_csrf(array $data): void
{
    $token = $data['csrf'] ?? $data['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        api_error('Invalid CSRF token', 403);
    }
}
