<?php
class AuthController
{
    public static function adminLoginForm(): void
    {
        render('admin/login');
    }

    public static function adminLogin(): void
    {
        csrf_verify();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE email = ?');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            login_admin((int)$admin['id']);
            redirect('/admin');
        }
        flash('error', 'Invalid credentials.');
        redirect('/admin/login');
    }

    public static function adminLogout(): void
    {
        logout_admin();
        redirect('/');
    }

    public static function teacherLogout(string $slug): void
    {
        logout_teacher();
        redirect('/' . $slug);
    }
}
