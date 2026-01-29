<?php
function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login');
        exit;
    }
}

function require_teacher(): void
{
    if (empty($_SESSION['teacher_id']) || empty($_SESSION['project_week_id'])) {
        header('Location: /');
        exit;
    }
}

function login_admin(int $adminId): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $adminId;
}

function logout_admin(): void
{
    unset($_SESSION['admin_id']);
}

function login_teacher(int $teacherId, int $projectWeekId): void
{
    session_regenerate_id(true);
    $_SESSION['teacher_id'] = $teacherId;
    $_SESSION['project_week_id'] = $projectWeekId;
}

function logout_teacher(): void
{
    unset($_SESSION['teacher_id'], $_SESSION['project_week_id']);
}
