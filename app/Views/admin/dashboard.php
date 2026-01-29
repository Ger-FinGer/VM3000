<h2>Admin Dashboard</h2>
<div class="list-group mt-3">
    <a class="list-group-item" href="/admin/classes">Klassen verwalten</a>
    <a class="list-group-item" href="/admin/rooms">Räume verwalten</a>
    <a class="list-group-item" href="/admin/project-weeks">Projektwochen verwalten</a>
    <a class="list-group-item" href="/admin/bookings">Buchungen verwalten</a>
    <a class="list-group-item" href="/admin/export?type=teachers">CSV: Lehrer</a>
    <a class="list-group-item" href="/admin/export?type=classes">CSV: Klassen</a>
    <a class="list-group-item" href="/admin/export?type=rooms">CSV: Räume</a>
    <a class="list-group-item" href="/admin/export?type=overview">CSV: Gesamtübersicht</a>
</div>

<h3 class="mt-4">Projektwochen</h3>
<table class="table">
    <thead><tr><th>Name</th><th>Slug</th><th>Aktionen</th></tr></thead>
    <tbody>
    <?php foreach ($weeks as $week): ?>
        <tr>
            <td><?php echo e($week['name']); ?></td>
            <td><?php echo e($week['slug']); ?></td>
            <td><a href="/admin/bookings?week_id=<?php echo e((string)$week['id']); ?>" class="btn btn-sm btn-outline-primary">Buchungen</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
