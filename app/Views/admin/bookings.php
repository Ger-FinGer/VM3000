<h2>Buchungen verwalten</h2>
<form method="get" class="mb-3">
    <label class="form-label">Projektwoche</label>
    <select name="week_id" class="form-select" onchange="this.form.submit()">
<?php foreach ($weeks as $week): ?>
            <option value="<?php echo e((string)$week['id']); ?>" <?php echo $week['id'] == $weekId ? 'selected' : ''; ?>><?php echo e($week['name']); ?></option>
        <?php endforeach; ?>
    </select>
</form>

<?php
$dayMap = [];
foreach ($days as $day) {
    $dayMap[$day['id']] = $day['day_date'];
}
?>

<div class="card p-3 mb-4">
    <h5>Neue Buchung</h5>
    <form method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Tag</label>
                <select class="form-select" name="day_id" required>
                    <?php foreach ($days as $day): ?>
                        <option value="<?php echo e((string)$day['id']); ?>"><?php echo e($day['day_date']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Stunde</label>
                <input type="number" class="form-control" name="period_index" min="1" value="1" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Klasse</label>
                <select class="form-select" name="class_id" required>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo e((string)$class['id']); ?>"><?php echo e($class['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Lehrer</label>
                <select class="form-select" name="teacher_id">
                    <option value="">-</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <?php
                        $remaining = (int)$teacher['quota'] - (int)$teacher['booked'];
                        $style = $remaining <= 0 ? 'text-danger' : '';
                        ?>
                        <option value="<?php echo e((string)$teacher['id']); ?>" class="<?php echo $style; ?>">
                            <?php echo e($teacher['initials']); ?> (<?php echo e((string)$remaining); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Raum</label>
                <select class="form-select" name="room_id">
                    <option value="">-</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?php echo e((string)$room['id']); ?>"><?php echo e($room['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">Beschreibung</label>
                <input class="form-control" name="description">
            </div>
        </div>
        <button class="btn btn-primary mt-3">Speichern</button>
    </form>
</div>

<table class="table">
    <thead><tr><th>Tag</th><th>Stunde</th><th>Klasse</th><th>Raum</th><th>Beschreibung</th><th>Aktion</th></tr></thead>
    <tbody>
    <?php foreach ($bookings as $booking): ?>
        <tr>
            <td><?php echo e($dayMap[$booking['project_week_day_id']] ?? (string)$booking['project_week_day_id']); ?></td>
            <td><?php echo e($booking['period_index']); ?></td>
            <td><?php echo e($booking['class_name']); ?></td>
            <td><?php echo e($booking['room_name'] ?? '-'); ?></td>
            <td><?php echo e($booking['description'] ?? ''); ?></td>
            <td>
                <form method="post" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="booking_id" value="<?php echo e((string)$booking['id']); ?>">
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Buchung löschen?')">Löschen</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
