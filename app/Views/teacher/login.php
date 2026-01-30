<h2>Lehrer-Login: <?php echo e($week['name']); ?></h2>
<form method="post" class="card p-4 mt-3">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
        <label class="form-label">Initialen</label>
        <input class="form-control" name="initials" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Passwort</label>
        <input type="password" class="form-control" name="password" required>
    </div>
    <button class="btn btn-primary">Login</button>
</form>
