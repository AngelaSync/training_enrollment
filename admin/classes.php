<?php
// admin/classes.php -- Manage class schedules and slots
require_once __DIR__ . '/../config/db.php';
/** @var PDO $db */
require_once __DIR__ . '/../classes/ClassSection.php';
require_once __DIR__ . '/../classes/Course.php';

$classes = new ClassSection($db);
$courses = new Course($db);

$errors  = [];
$message = '';

// Form values (add form, or edit form when ?edit=ID)
$edit_id    = 0;
$course_id  = 0;
$code       = '';
$schedule   = '';
$instructor = '';
$slots      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['class_id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'Invalid class.';
        } else {
            try {
                $classes->delete($id);
                $message = 'Class deleted.';
            } catch (PDOException $e) {
                if (($e->errorInfo[1] ?? 0) == 1451) {
                    $errors[] = 'Cannot delete this class because it has enrollment records.';
                } else {
                    $errors[] = 'Something went wrong. Nothing was deleted.';
                }
            }
        }
    } elseif ($action === 'create' || $action === 'update') {
        $edit_id    = (int) ($_POST['class_id'] ?? 0);
        $course_id  = (int) ($_POST['course_id'] ?? 0);
        $code       = trim($_POST['class_code'] ?? '');
        $schedule   = trim($_POST['schedule'] ?? '');
        $instructor = trim($_POST['instructor'] ?? '');
        $slots      = trim($_POST['slots'] ?? '');

        // Validation
        if ($course_id <= 0 || !$courses->find($course_id)) {
            $errors[] = 'Please select a valid course.';
        }
        if ($code === '') {
            $errors[] = 'Class code is required.';
        } elseif (mb_strlen($code) > 20) {
            $errors[] = 'Class code must be 20 characters or less.';
        }
        if (mb_strlen($schedule) > 100) {
            $errors[] = 'Schedule must be 100 characters or less.';
        }
        if (mb_strlen($instructor) > 100) {
            $errors[] = 'Instructor must be 100 characters or less.';
        }
        if (filter_var($slots, FILTER_VALIDATE_INT) === false || (int) $slots < 0) {
            $errors[] = 'Slots must be a whole number, 0 or more.';
        }
        if ($action === 'update' && ($edit_id <= 0 || !$classes->find($edit_id))) {
            $errors[] = 'Class to edit was not found.';
        }

        if (!$errors) {
            try {
                if ($action === 'create') {
                    $classes->create($course_id, $code, $schedule, $instructor, (int) $slots);
                    $message = 'Class added.';
                } else {
                    $classes->update($edit_id, $course_id, $code, $schedule, $instructor, (int) $slots);
                    $message = 'Class updated.';
                }
                $edit_id = $course_id = 0;
                $code = $schedule = $instructor = $slots = '';
            } catch (PDOException $e) {
                $errors[] = 'Something went wrong. Nothing was saved.';
            }
        }
    }
} elseif (isset($_GET['edit'])) {
    $class = $classes->find((int) $_GET['edit']);
    if ($class) {
        $edit_id    = (int) $class['class_id'];
        $course_id  = (int) $class['course_id'];
        $code       = $class['class_code'];
        $schedule   = $class['schedule'] ?? '';
        $instructor = $class['instructor'] ?? '';
        $slots      = (string) $class['slots'];
    } else {
        $errors[] = 'Class not found.';
    }
}

// Loaded last so the list shows the latest data
$list       = $classes->allWithCourse();
$courseList = $courses->all();

$base = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Classes</h1>

<?php if ($message): ?>
    <p class="success"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>
<?php foreach ($errors as $err): ?>
    <p class="error"><?php echo htmlspecialchars($err); ?></p>
<?php endforeach; ?>

<?php if (!$courseList): ?>
    <p>No courses yet. <a href="courses.php">Add a course</a> before creating classes.</p>
<?php else: ?>
<h2><?php echo $edit_id ? 'Edit Class' : 'Add Class'; ?></h2>
<form method="post" action="classes.php">
    <input type="hidden" name="action" value="<?php echo $edit_id ? 'update' : 'create'; ?>">
    <input type="hidden" name="class_id" value="<?php echo $edit_id; ?>">
    <p>
        <label>Course<br>
        <select name="course_id" required>
            <option value="">-- Select a course --</option>
            <?php foreach ($courseList as $co): ?>
                <option value="<?php echo (int) $co['course_id']; ?>"
                    <?php echo $course_id === (int) $co['course_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($co['course_code'] . ' - ' . $co['course_name']); ?>
                </option>
            <?php endforeach; ?>
        </select></label>
    </p>
    <p>
        <label>Class code<br>
        <input type="text" name="class_code" maxlength="20" required
               value="<?php echo htmlspecialchars($code); ?>"></label>
    </p>
    <p>
        <label>Schedule<br>
        <input type="text" name="schedule" maxlength="100"
               placeholder="e.g. Mon/Wed 9:00-10:30"
               value="<?php echo htmlspecialchars($schedule); ?>"></label>
    </p>
    <p>
        <label>Instructor<br>
        <input type="text" name="instructor" maxlength="100"
               value="<?php echo htmlspecialchars($instructor); ?>"></label>
    </p>
    <p>
        <label>Slots available<br>
        <input type="number" name="slots" min="0" required
               value="<?php echo htmlspecialchars($slots); ?>"></label>
    </p>
    <button type="submit"><?php echo $edit_id ? 'Update Class' : 'Add Class'; ?></button>
    <?php if ($edit_id): ?>
        <a href="classes.php">Cancel edit</a>
    <?php endif; ?>
</form>
<?php endif; ?>

<h2>All Classes</h2>
<?php if (!$list): ?>
    <p>No classes yet.</p>
<?php else: ?>
<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th><th>Course</th><th>Class</th><th>Schedule</th>
            <th>Instructor</th><th>Slots left</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($list as $c): ?>
        <tr>
            <td><?php echo (int) $c['class_id']; ?></td>
            <td><?php echo htmlspecialchars($c['course_name']); ?></td>
            <td><?php echo htmlspecialchars($c['class_code']); ?></td>
            <td><?php echo htmlspecialchars($c['schedule'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($c['instructor'] ?? ''); ?></td>
            <td><?php echo (int) $c['slots']; ?></td>
            <td>
                <a href="classes.php?edit=<?php echo (int) $c['class_id']; ?>">Edit</a>
                <form method="post" action="classes.php" style="display:inline"
                      onsubmit="return confirm('Delete this class?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="class_id" value="<?php echo (int) $c['class_id']; ?>">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>