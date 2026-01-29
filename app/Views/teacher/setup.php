<h2>Profil einrichten: <?php echo e($week['name']); ?></h2>
<form method="post" class="card p-4 mt-3">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
        <label class="form-label">Neues Passwort</label>
        <input type="password" class="form-control" name="new_password" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Stunden-Quota</label>
        <input type="number" class="form-control" name="quota" min="1" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Klassenlehrkraft?</label>
        <select class="form-select" name="homeroom" id="homeroom-select">
            <option value="no">Nein</option>
            <option value="yes">Ja</option>
        </select>
    </div>
    <div class="mb-3" id="homeroom-class">
        <label class="form-label">Klasse wählen</label>
        <select class="form-select" name="homeroom_class_id">
            <option value="">-</option>
            <?php foreach ($classes as $class): ?>
                <?php if (in_array($class['id'], $usedHomerooms, true)) continue; ?>
                <option value="<?php echo e((string)$class['id']); ?>"><?php echo e($class['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn btn-primary">Speichern</button>
</form>
