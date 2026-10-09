<?php
// admin/reports.php -- Summary reports
require_once __DIR__ . '/../config/db.php';
/** @var PDO $db */
require_once __DIR__ . '/../classes/EnrollmentRepository.php';

$repo    = new EnrollmentRepository($db);
$totals  = $repo->totals();
$summary = $repo->classSummary();

$base = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Reports</h1>

<h2>Overview</h2>
<div class="stats">
    <div class="stat"><strong><?php echo (int) $totals['students']; ?></strong><span>Students</span></div>
    <div class="stat"><strong><?php echo (int) $totals['courses']; ?></strong><span>Courses</span></div>
    <div class="stat"><strong><?php echo (int) $totals['classes']; ?></strong><span>Classes</span></div>
    <div class="stat"><strong><?php echo (int) $totals['active_enrollments']; ?></strong><span>Active enrollments</span></div>
    <div class="stat"><strong><?php echo (int) $totals['cancelled_enrollments']; ?></strong><span>Cancelled enrollments</span></div>
</div>

<h2>Enrollments per Class</h2>
<?php if (!$summary): ?>
    <p>No classes yet.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>Course</th><th>Class</th><th>Schedule</th><th>Instructor</th>
            <th>Active</th><th>Cancelled</th><th>Slots left</th><th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($summary as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['course_name']); ?></td>
            <td><?php echo htmlspecialchars($r['class_code']); ?></td>
            <td><?php echo htmlspecialchars($r['schedule'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['instructor'] ?? ''); ?></td>
            <td><?php echo (int) $r['active_count']; ?></td>
            <td><?php echo (int) $r['cancelled_count']; ?></td>
            <td><?php echo (int) $r['slots_left']; ?></td>
            <td><?php echo (int) $r['slots_left'] === 0 ? 'FULL' : 'Open'; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>