<h2>Räume</h2>
<form method="post" class="card p-3 mb-4">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="create">
    <div class="row g-2">
        <div class="col">
            <input class="form-control" name="name" placeholder="Raumname" required>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary">Hinzufügen</button>
        </div>
    </div>
</form>
<table class="table">
    <thead><tr><th>Name</th><th>Status</th><th>Aktion</th></tr></thead>
    <tbody>
    <?php foreach ($rooms as $room): ?>
        <tr>
            <td><?php echo e($room['name']); ?></td>
            <td>
                <form method="post" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?php echo e((string)$room['id']); ?>">
                    <input type="hidden" name="active" value="<?php echo $room['active'] ? '0' : '1'; ?>">
                    <button class="btn btn-sm <?php echo $room['active'] ? 'btn-outline-success' : 'btn-outline-secondary'; ?>">
                        <?php echo $room['active'] ? 'Aktiv' : 'Inaktiv'; ?>
                    </button>
                </form>
            </td>
            <td>
                <form method="post" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo e((string)$room['id']); ?>">
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Raum löschen?')">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
