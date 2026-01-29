<h2>Projektwochen</h2>
<form method="post" class="card p-3 mb-4">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="create">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Slug</label>
            <input class="form-control" name="slug" placeholder="TSS" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Daten (Komma getrennt, YYYY-MM-DD)</label>
            <input class="form-control" name="dates" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Stunden/Tag</label>
            <input type="number" min="1" max="12" class="form-control" name="periods_per_day" value="6">
        </div>
        <div class="col-md-4">
            <label class="form-label">Lehrer Initial-Passwort</label>
            <input type="password" class="form-control" name="initial_password" required>
        </div>
    </div>
    <button class="btn btn-primary mt-3">Anlegen</button>
</form>

<table class="table">
    <thead><tr><th>Name</th><th>Slug</th><th>Aktion</th></tr></thead>
    <tbody>
    <?php foreach ($weeks as $week): ?>
        <tr>
            <td><?php echo e($week['name']); ?></td>
            <td><?php echo e($week['slug']); ?></td>
            <td>
                <form method="post" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo e((string)$week['id']); ?>">
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Projektwoche löschen?')">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
