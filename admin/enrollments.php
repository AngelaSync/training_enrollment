<?php
// admin/enrollments.php
require_once __DIR__ . '/../config/db.php';
/** @var PDO $db */
require_once __DIR__ . '/../classes/EnrollmentRepository.php';

$repo = new EnrollmentRepository($db);

$message = '';
$error   = '';

// Cancel request (POST, because it changes data)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $enrollment_id = (int) $_POST['cancel_id'];

    if ($enrollment_id <= 0) {
        $error = 'Invalid enrollment.';
    } else {
        try {
            if ($repo->cancel($enrollment_id)) {
                $message = 'Enrollment cancelled and the slot was restored.';
            } else {
                $error = 'Enrollment not found or already cancelled.';
            }
        } catch (Throwable $e) {
            $error = 'Something went wrong. Nothing was changed.';
        }
    }
}

// Loaded after the POST so the table shows the updated status
$rows = $repo->allWithDetails();

$base = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Enrollments</h1>

<?php if ($message): ?>
    <p class="success"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>
<?php if ($error): ?>
    <p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<?php if (!$rows): ?>
    <p>No enrollments yet. <a href="students.php">Record a student</a> to get started.</p>
<?php else: ?>
<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Student</th>
            <th>Email</th>
            <th>Course</th>
            <th>Class</th>
            <th>Schedule</th>
            <th>Instructor</th>
            <th>Enrolled</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo (int) $r['enrollment_id']; ?></td>
            <td><?php echo htmlspecialchars($r['full_name']); ?></td>
            <td><?php echo htmlspecialchars($r['email'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['course_name']); ?></td>
            <td><?php echo htmlspecialchars($r['class_code']); ?></td>
            <td><?php echo htmlspecialchars($r['schedule'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['instructor'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['enrollment_date']); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
            <td>
                <?php if ($r['status'] === 'active'): ?>
                    <form method="post" action="enrollments.php" style="display:inline"
                          onsubmit="return confirm('Cancel this enrollment?');">
                        <input type="hidden" name="cancel_id"
                               value="<?php echo (int) $r['enrollment_id']; ?>">
                        <button type="submit">Cancel</button>
                    </form>
                <?php else: ?>
                    &mdash;
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>