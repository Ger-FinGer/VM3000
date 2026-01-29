<?php
$bookingMap = [];
foreach ($bookings as $booking) {
    $bookingMap[$booking['project_week_day_id']][$booking['period_index']][] = $booking;
}
$classNameById = [];
foreach ($classes as $class) {
    $classNameById[$class['id']] = $class['name'];
}
$classTripMap = [];
foreach ($classTrips as $trip) {
    $classTripMap[$trip['project_week_day_id']][] = $classNameById[$trip['class_id']] ?? (string)$trip['class_id'];
}
?>
<h2><?php echo e($week['name']); ?> - Mein Stundenplan</h2>
<p>Quota: <?php echo e((string)($profile['quota'] ?? 0)); ?></p>

<div class="card p-3 mb-4">
    <h5>Neue Buchung</h5>
    <form method="post" class="row g-2">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="booking_add">
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
            <input type="number" class="form-control" name="period_index" min="1" max="<?php echo e((string)$week['periods_per_day']); ?>" value="1">
        </div>
        <div class="col-md-4">
            <label class="form-label">Klasse</label>
            <select class="form-select" name="class_id" required>
                <?php foreach ($classes as $class): ?>
                    <option value="<?php echo e((string)$class['id']); ?>"><?php echo e($class['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary">Buchen</button>
        </div>
    </form>
</div>

<div class="table-responsive">
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Stunde</th>
            <?php foreach ($days as $day): ?>
                <th><?php echo e($day['day_date']); ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php for ($period = 1; $period <= (int)$week['periods_per_day']; $period++): ?>
            <tr>
                <td><?php echo e((string)$period); ?></td>
                <?php foreach ($days as $day): ?>
                    <td>
                        <?php if (in_array($day['id'], $absentDays, true)): ?>
                            <span class="badge bg-warning text-dark">Abwesend</span>
                        <?php else: ?>
                            <?php if (!empty($classTripMap[$day['id']])): ?>
                                <div class="mb-1 text-danger small">Klassenfahrt: <?php echo e(implode(', ', $classTripMap[$day['id']])); ?></div>
                            <?php endif; ?>
                            <?php foreach ($bookingMap[$day['id']][$period] ?? [] as $booking): ?>
                                <div class="booking-entry">
                                    <strong><?php echo e($booking['class_name']); ?></strong><br>
                                    <small><?php echo e(implode(' ', $bookingTeachers[$booking['id']] ?? [])); ?></small><br>
                                    <?php if ($booking['room_name']): ?>
                                        <span class="badge bg-info">Raum <?php echo e($booking['room_name']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($booking['description']): ?>
                                        <div class="small text-muted"><?php echo e($booking['description']); ?></div>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-secondary mt-2" data-bs-toggle="modal" data-bs-target="#booking-<?php echo e((string)$booking['id']); ?>">Bearbeiten</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>
</div>

<div class="card p-3 mb-4">
    <h5>Tag-Status</h5>
    <form method="post" class="row g-2">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="set_absent">
        <div class="col-md-4">
            <label class="form-label">Abwesend am Tag</label>
            <select class="form-select" name="day_id">
                <?php foreach ($days as $day): ?>
                    <option value="<?php echo e((string)$day['id']); ?>"><?php echo e($day['day_date']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-outline-warning" onclick="return confirm('Abwesend setzen?')">Abwesend</button>
        </div>
    </form>

    <form method="post" class="row g-2 mt-3">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="set_class_trip">
        <div class="col-md-4">
            <label class="form-label">Klassenfahrt Tag</label>
            <select class="form-select" name="day_id">
                <?php foreach ($days as $day): ?>
                    <option value="<?php echo e((string)$day['id']); ?>"><?php echo e($day['day_date']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Klasse</label>
            <select class="form-select" name="class_id">
                <?php foreach ($classes as $class): ?>
                    <option value="<?php echo e((string)$class['id']); ?>"><?php echo e($class['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-outline-danger" onclick="return confirm('Klassenfahrt setzen?')">Klassenfahrt</button>
        </div>
    </form>
</div>

<div class="card p-3 mb-4">
    <h5>Klassenübersicht</h5>
    <form method="get" class="row g-2">
        <div class="col-md-6">
            <label class="form-label">Klasse wählen</label>
            <select class="form-select" name="class_id" onchange="this.form.submit()">
                <option value="">-</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?php echo e((string)$class['id']); ?>" <?php echo $classOverviewId == $class['id'] ? 'selected' : ''; ?>><?php echo e($class['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ($classOverviewId): ?>
        <?php
        $classBookingMap = [];
        foreach ($classOverviewBookings as $booking) {
            $classBookingMap[$booking['project_week_day_id']][$booking['period_index']][] = $booking;
        }
        ?>
        <div class="table-responsive mt-3">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Stunde</th>
                        <?php foreach ($days as $day): ?>
                            <th><?php echo e($day['day_date']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($period = 1; $period <= (int)$week['periods_per_day']; $period++): ?>
                        <tr>
                            <td><?php echo e((string)$period); ?></td>
                            <?php foreach ($days as $day): ?>
                                <td>
                                    <?php foreach ($classBookingMap[$day['id']][$period] ?? [] as $booking): ?>
                                        <div class="booking-entry">
                                            <strong><?php echo e($booking['description'] ?: 'Projekt'); ?></strong><br>
                                            <small><?php echo e(implode(' ', $bookingTeachers[$booking['id']] ?? [])); ?></small><br>
                                            <?php if ($booking['room_name']): ?>
                                                <span class="badge bg-info">Raum <?php echo e($booking['room_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php foreach ($bookings as $booking): ?>
<div class="modal fade" id="booking-<?php echo e((string)$booking['id']); ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="booking_id" value="<?php echo e((string)$booking['id']); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Buchung: <?php echo e($booking['class_name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Raum</label>
                        <select class="form-select" name="room_id">
                            <option value="">-</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo e((string)$room['id']); ?>" <?php echo $booking['room_id'] == $room['id'] ? 'selected' : ''; ?>><?php echo e($room['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beschreibung</label>
                        <textarea class="form-control" name="description" rows="3"><?php echo e($booking['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button name="action" value="booking_update" class="btn btn-primary">Speichern</button>
                    <button name="action" value="booking_leave" class="btn btn-outline-danger" onclick="return confirm('Buchung verlassen?')">Verlassen</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
