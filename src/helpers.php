<?php
function render(string $view, array $data = []): void
{
    extract($data);
    $viewPath = dirname(__DIR__) . '/app/Views/' . $view . '.php';
    $layout = dirname(__DIR__) . '/app/Views/layout.php';
    if (!file_exists($viewPath)) {
        throw new RuntimeException('View not found: ' . $viewPath);
    }
    include $layout;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

function config(string $key, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config.php';
    }
    return $config[$key] ?? $default;
}
