<?php
$dashboardData = [
    'days' => $days,
    'classes' => $classes,
];
?>
<h2><?php echo e($week['name']); ?> - Mein Stundenplan</h2>
<p>Quota: <?php echo e((string)($profile['quota'] ?? 0)); ?></p>

<div id="teacher-dashboard"
     data-csrf="<?php echo e(csrf_token()); ?>"
     data-week-id="<?php echo e((string)$week['id']); ?>"
     data-periods="<?php echo e((string)$week['periods_per_day']); ?>"
     data-days="<?php echo e(json_encode($dashboardData['days'])); ?>"
     data-classes="<?php echo e(json_encode($dashboardData['classes'])); ?>">

    <div class="card p-3 mb-4">
        <h5>Mein Stundenplan</h5>
        <div class="table-responsive">
            <table class="table table-bordered" id="my-timetable-table">
                <thead>
                <tr>
                    <th>Stunde</th>
                    <?php foreach ($days as $day): ?>
                        <th><?php echo e($day['day_date']); ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card p-3 mb-4">
        <h5>Klassenübersicht</h5>
        <form class="row g-2" id="class-view-form">
            <div class="col-md-6">
                <label class="form-label">Klasse wählen</label>
                <select class="form-select" id="class-select" name="class_id">
                    <option value="">-</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo e((string)$class['id']); ?>"><?php echo e($class['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <div class="table-responsive mt-3">
            <table class="table table-bordered" id="class-view-table">
                <thead>
                <tr>
                    <th>Stunde</th>
                    <?php foreach ($days as $day): ?>
                        <th><?php echo e($day['day_date']); ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="slot-details-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="slot-details-title">Slot Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Vorhaben</label>
                    <textarea class="form-control" id="slot-plan-text" rows="4"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Raum</label>
                    <select class="form-select" id="slot-room-select">
                        <option value="">-</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Teilnehmende</label>
                    <ul class="list-group" id="slot-participants"></ul>
                </div>
                <div class="alert alert-info d-none" id="slot-type-summary"></div>
            </div>
            <div class="modal-footer flex-wrap gap-2">
                <button class="btn btn-outline-warning" id="slot-absence-slot">Abwesend (Slot)</button>
                <button class="btn btn-outline-warning" id="slot-absence-day">Abwesend (Tag)</button>
                <button class="btn btn-outline-danger" id="slot-trip">Klassenfahrt</button>
                <div class="ms-auto d-flex gap-2">
                    <button class="btn btn-outline-secondary" id="slot-join">Beitreten</button>
                    <button class="btn btn-outline-danger" id="slot-delete">Buchung löschen</button>
                    <button class="btn btn-primary" id="slot-save">Speichern</button>
                </div>
            </div>
        </div>
    </div>
</div>
