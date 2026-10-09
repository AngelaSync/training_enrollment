<?php
// index.php
require_once __DIR__ . '/includes/header.php';
?>
<h1>Training Enrollment System</h1>

<p>Welcome, Administrator. Use this system to manage training courses and class
schedules, record students, enroll them into classes, and review or cancel
enrollments.</p>

<div class="btn-grid">
    <a class="btn" href="admin/courses.php">
        <strong>Courses</strong>
        <span>Add, edit and delete courses</span>
    </a>
    <a class="btn" href="admin/classes.php">
        <strong>Classes</strong>
        <span>Manage schedules, instructors and slots</span>
    </a>
    <a class="btn" href="admin/students.php">
        <strong>Record Student</strong>
        <span>Register a new student and enroll in one step</span>
    </a>
    <a class="btn" href="admin/enroll.php">
        <strong>Enroll</strong>
        <span>Enroll an existing student into a class</span>
    </a>
    <a class="btn" href="admin/enrollments.php">
        <strong>Enrollments</strong>
        <span>View all enrollments and cancel them</span>
    </a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>