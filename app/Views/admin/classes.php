<h2>Klassen</h2>
<form method="post" class="card p-3 mb-4">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="create">
    <div class="row g-2">
        <div class="col">
            <input class="form-control" name="name" placeholder="Klassenname" required>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary">Hinzufügen</button>
        </div>
    </div>
</form>
<table class="table">
    <thead><tr><th>Name</th><th>Aktion</th></tr></thead>
    <tbody>
    <?php foreach ($classes as $class): ?>
        <tr>
            <td><?php echo e($class['name']); ?></td>
            <td>
                <form method="post" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo e((string)$class['id']); ?>">
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Klasse löschen?')">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
