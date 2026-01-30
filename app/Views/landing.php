<section class="text-center">
    <h1 class="mb-3">VM3000 Projektwochen-Planer</h1>
    <p class="lead">Plane Vorhabenswochen, verwalte Räume, Klassen und Lehrkräfte – alles lokal in deiner Schule.</p>
</section>

<div class="row justify-content-center mt-4">
    <div class="col-lg-6">
        <div class="card p-4">
            <h4 class="mb-3">Lehrer-Login</h4>
            <form method="post" data-login-form>
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label">Projektwoche</label>
                    <select class="form-select" name="slug" data-slug-select>
                        <?php foreach ($weeks as $week): ?>
                            <option value="<?php echo e($week['slug']); ?>"><?php echo e($week['name']); ?> (<?php echo e($week['slug']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Initialen</label>
                    <input class="form-control" name="initials" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Passwort</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button class="btn btn-primary w-100">Login</button>
            </form>
        </div>
        <div class="text-center mt-3">
            <a class="btn btn-outline-secondary btn-sm" href="/admin/login">Admin Login</a>
        </div>
    </div>
</div>
