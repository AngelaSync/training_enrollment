<?php
// includes/header.php
$base = $base ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Training Enrollment System</title>
    <link rel="stylesheet" href="<?php echo $base; ?>css/style.css">
</head>
<body>
<nav>
    <a href="<?php echo $base; ?>index.php">Home</a> |
    <a href="<?php echo $base; ?>admin/courses.php">Courses</a> |
    <a href="<?php echo $base; ?>admin/classes.php">Classes</a> |
    <a href="<?php echo $base; ?>admin/students.php">Record Student</a> |
    <a href="<?php echo $base; ?>admin/enroll.php">Enroll</a> |
    <a href="<?php echo $base; ?>admin/enrollments.php">Enrollments</a> |
    <a href="<?php echo $base; ?>admin/reports.php">Reports</a>
</nav>
<main>