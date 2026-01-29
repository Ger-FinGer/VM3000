<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

if ($path === '/') {
    TeacherController::landing();
    return;
}

if ($path === '/admin/login' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    AuthController::adminLoginForm();
    return;
}
if ($path === '/admin/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    AuthController::adminLogin();
    return;
}
if ($path === '/admin/logout') {
    AuthController::adminLogout();
    return;
}
if ($path === '/admin') {
    AdminController::dashboard();
    return;
}
if ($path === '/admin/classes') {
    AdminController::classes();
    return;
}
if ($path === '/admin/rooms') {
    AdminController::rooms();
    return;
}
if ($path === '/admin/project-weeks') {
    AdminController::projectWeeks();
    return;
}
if ($path === '/admin/bookings') {
    AdminController::bookings();
    return;
}
if ($path === '/admin/export') {
    AdminController::exports();
    return;
}

$slug = ltrim($path, '/');
TeacherController::handle($slug);
