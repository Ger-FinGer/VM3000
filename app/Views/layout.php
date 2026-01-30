<?php
?><!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VM3000</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="/">VM3000</a>
        <div class="d-flex gap-3">
            <?php if (!empty($_SESSION['teacher_id'])): ?>
                <a class="nav-link text-white" href="?action=download_csv">Mein Stundenplan</a>
                <a class="nav-link text-white" href="?action=logout">Logout</a>
            <?php endif; ?>
            <?php if (!empty($_SESSION['admin_id'])): ?>
                <a class="nav-link text-white" href="/admin">Admin</a>
                <a class="nav-link text-white" href="/admin/logout">Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="container">
    <?php if ($message = flash('success')): ?>
        <div class="alert alert-success"><?php echo e($message); ?></div>
    <?php endif; ?>
    <?php if ($message = flash('error')): ?>
        <div class="alert alert-danger"><?php echo e($message); ?></div>
    <?php endif; ?>
    <?php include $viewPath; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/app.js"></script>
</body>
</html>
