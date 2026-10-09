<?php
// admin/courses.php -- Manage courses (CRUD)
require_once __DIR__ . '/../config/db.php';
/** @var PDO $db */
require_once __DIR__ . '/../classes/Course.php';

$courses = new Course($db);

$errors  = [];
$message = '';

// Form values (used for the add form, or the edit form when ?edit=ID)
$edit_id     = 0;
$code        = '';
$name        = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['course_id'] ?? 0);
        if ($id <= 0) {
            $errors[] = 'Invalid course.';
        } else {
            try {
                $courses->delete($id);
                $message = 'Course deleted.';
            } catch (PDOException $e) {
                if (($e->errorInfo[1] ?? 0) == 1451) {
                    $errors[] = 'Cannot delete this course because it still has classes. Delete or move its classes first.';
                } else {
                    $errors[] = 'Something went wrong. Nothing was deleted.';
                }
            }
        }
    } elseif ($action === 'create' || $action === 'update') {
        $edit_id     = (int) ($_POST['course_id'] ?? 0);
        $code        = trim($_POST['course_code'] ?? '');
        $name        = trim($_POST['course_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validation
        if ($code === '') {
            $errors[] = 'Course code is required.';
        } elseif (mb_strlen($code) > 20) {
            $errors[] = 'Course code must be 20 characters or less.';
        }
        if ($name === '') {
            $errors[] = 'Course name is required.';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Course name must be 100 characters or less.';
        }
        if ($action === 'update' && ($edit_id <= 0 || !$courses->find($edit_id))) {
            $errors[] = 'Course to edit was not found.';
        }

        if (!$errors) {
            try {
                if ($action === 'create') {
                    $courses->create($code, $name, $description);
                    $message = 'Course added.';
                } else {
                    $courses->update($edit_id, $code, $name, $description);
                    $message = 'Course updated.';
                }
                $edit_id = 0;
                $code = $name = $description = '';
            } catch (PDOException $e) {
                if (($e->errorInfo[1] ?? 0) == 1062) {
                    $errors[] = 'That course code already exists. Use a different code.';
                } else {
                    $errors[] = 'Something went wrong. Nothing was saved.';
                }
            }
        }
    }
} elseif (isset($_GET['edit'])) {
    // Load a course into the form for editing
    $course = $courses->find((int) $_GET['edit']);
    if ($course) {
        $edit_id     = (int) $course['course_id'];
        $code        = $course['course_code'];
        $name        = $course['course_name'];
        $description = $course['description'] ?? '';
    } else {
        $errors[] = 'Course not found.';
    }
}

// Loaded last so the list shows the latest data
$list = $courses->all();

$base = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Courses</h1>

<?php if ($message): ?>
    <p class="success"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>
<?php foreach ($errors as $err): ?>
    <p class="error"><?php echo htmlspecialchars($err); ?></p>
<?php endforeach; ?>

<h2><?php echo $edit_id ? 'Edit Course' : 'Add Course'; ?></h2>
<form method="post" action="courses.php">
    <input type="hidden" name="action" value="<?php echo $edit_id ? 'update' : 'create'; ?>">
    <input type="hidden" name="course_id" value="<?php echo $edit_id; ?>">
    <p>
        <label>Course code<br>
        <input type="text" name="course_code" maxlength="20" required
               value="<?php echo htmlspecialchars($code); ?>"></label>
    </p>
    <p>
        <label>Course name<br>
        <input type="text" name="course_name" maxlength="100" required
               value="<?php echo htmlspecialchars($name); ?>"></label>
    </p>
    <p>
        <label>Description<br>
        <textarea name="description" rows="3" cols="40"><?php echo htmlspecialchars($description); ?></textarea></label>
    </p>
    <button type="submit"><?php echo $edit_id ? 'Update Course' : 'Add Course'; ?></button>
    <?php if ($edit_id): ?>
        <a href="courses.php">Cancel edit</a>
    <?php endif; ?>
</form>

<h2>All Courses</h2>
<?php if (!$list): ?>
    <p>No courses yet.</p>
<?php else: ?>
<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr><th>ID</th><th>Code</th><th>Name</th><th>Description</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($list as $c): ?>
        <tr>
            <td><?php echo (int) $c['course_id']; ?></td>
            <td><?php echo htmlspecialchars($c['course_code']); ?></td>
            <td><?php echo htmlspecialchars($c['course_name']); ?></td>
            <td><?php echo htmlspecialchars($c['description'] ?? ''); ?></td>
            <td>
                <a href="courses.php?edit=<?php echo (int) $c['course_id']; ?>">Edit</a>
                <form method="post" action="courses.php" style="display:inline"
                      onsubmit="return confirm('Delete this course?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="course_id" value="<?php echo (int) $c['course_id']; ?>">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>