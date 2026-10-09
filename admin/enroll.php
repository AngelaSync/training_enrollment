<?php
// admin/enroll.php -- Enroll an EXISTING student into a class
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$repo     = new EnrollmentRepository($db);
$students = new Student($db);
$classes  = new ClassSection($db);

$errors   = [];
$success  = '';
$student_id = 0;
$class_id   = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int) ($_POST['student_id'] ?? 0);
    $class_id   = (int) ($_POST['class_id'] ?? 0);

    // Validation
    if ($student_id <= 0) {
        $errors[] = 'Please select a student.';
    } elseif (!$students->find($student_id)) {
        $errors[] = 'Selected student does not exist.';
    }
    if ($class_id <= 0) {
        $errors[] = 'Please select a class.';
    }

    if (!$errors) {
        try {
            if ($repo->enroll($student_id, $class_id)) {
                $success = 'Student enrolled successfully.';
                $student_id = $class_id = 0;
            } else {
                $errors[] = 'No slots available for the selected class. Nothing was saved.';
            }
        } catch (RuntimeException $e) {
            // Duplicate enrollment (thrown by EnrollmentRepository::enroll)
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            $errors[] = 'Something went wrong. Nothing was saved.';
        }
    }
}

// Loaded after the POST so the dropdown shows updated slot counts
$studentList = $students->all();
$classList   = $classes->allWithCourse();

$base = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Enroll Existing Student</h1>

<?php if ($success): ?>
    <p class="success"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<?php foreach ($errors as $err): ?>
    <p class="error"><?php echo htmlspecialchars($err); ?></p>
<?php endforeach; ?>

<?php if (!$studentList): ?>
    <p>No students yet. <a href="students.php">Record a student</a> first.</p>
<?php else: ?>
<form method="post" action="enroll.php">
    <p>
        <label>Student<br>
        <select name="student_id" required>
            <option value="">-- Select a student --</option>
            <?php foreach ($studentList as $s): ?>
                <option value="<?php echo (int) $s['student_id']; ?>"
                    <?php echo $student_id === (int) $s['student_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s['full_name']); ?>
                </option>
            <?php endforeach; ?>
        </select></label>
    </p>
    <p>
        <label>Class<br>
        <select name="class_id" required>
            <option value="">-- Select a class --</option>
            <?php foreach ($classList as $c): ?>
                <option value="<?php echo (int) $c['class_id']; ?>"
                    <?php echo $class_id === (int) $c['class_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(
                        $c['course_name'] . ' - ' . $c['class_code'] .
                        ' (' . $c['schedule'] . ') - ' . $c['slots'] . ' slot(s) left'
                    ); ?>
                </option>
            <?php endforeach; ?>
        </select></label>
    </p>
    <button type="submit">Enroll</button>
</form>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>