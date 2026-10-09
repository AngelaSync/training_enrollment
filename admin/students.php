<?php
// admin/students.php -- Student recording form
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/EnrollmentRepository.php';
require_once __DIR__ . '/../classes/ClassSection.php';

$repo = new EnrollmentRepository($db);
$classes = new ClassSection($db);

$errors = [];
$success = '';
$full_name = $email = $phone = '';
$class_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $class_id  = (int) ($_POST['class_id'] ?? 0);

    // Validation
    if ($full_name === '') {
        $errors[] = 'Full name is required.';
    } elseif (mb_strlen($full_name) > 100) {
        $errors[] = 'Full name must be 100 characters or less.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email address is not valid.';
    } elseif (mb_strlen($email) > 100) {
        $errors[] = 'Email must be 100 characters or less.';
    }
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{5,30}$/', $phone)) {
        $errors[] = 'Phone may only contain digits, spaces, + - ( ) and be 5 to 30 characters.';
    }
    if ($class_id <= 0) {
        $errors[] = 'Please select a class.';
    }

    if (!$errors) {
        try {
            if ($repo->recordStudent($full_name, $email, $phone, $class_id)) {
                $success = 'Student recorded and enrolled successfully.';
                $full_name = $email = $phone = '';
                $class_id = 0;
            } else {
                $errors[] = 'No slots available for the selected class. Nothing was saved.';
            }
        } catch (Throwable $e) {
            $errors[] = 'Something went wrong. Nothing was saved.';
        }
    }
}

// Loaded after the POST so the dropdown shows the updated slot counts
$classList = $classes->allWithCourse();

$base = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Record Student</h1>

<?php if ($success): ?>
    <p class="success"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>

<?php foreach ($errors as $err): ?>
    <p class="error"><?php echo htmlspecialchars($err); ?></p>
<?php endforeach; ?>

<form method="post" action="students.php">
    <p>
        <label>Full name<br>
        <input type="text" name="full_name" maxlength="100" required
               value="<?php echo htmlspecialchars($full_name); ?>"></label>
    </p>
    <p>
        <label>Email<br>
        <input type="email" name="email" maxlength="100"
               value="<?php echo htmlspecialchars($email); ?>"></label>
    </p>
    <p>
        <label>Phone<br>
        <input type="text" name="phone" maxlength="30"
               value="<?php echo htmlspecialchars($phone); ?>"></label>
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
    <button type="submit">Record Student</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>