<?php
// classes/EnrollmentRepository.php
// Records students, enrollments and slot updates inside database transactions.
class EnrollmentRepository {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    // Record a NEW student AND enroll them into a class.
    // Returns true on success, false when the class has no slots left.
    public function recordStudent($full_name, $email, $phone, $class_id) {
        try {
            $this->db->beginTransaction();

            // Lock the class row so two requests can't take the last slot
            $stmt = $this->db->prepare(
                "SELECT slots FROM classes WHERE class_id = :id FOR UPDATE");
            $stmt->execute([':id' => $class_id]);
            $class = $stmt->fetch();

            if (!$class || (int) $class['slots'] <= 0) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare(
                "INSERT INTO students (full_name, email, phone)
                 VALUES (:full_name, :email, :phone)");
            $stmt->execute([
                ':full_name' => $full_name,
                ':email'     => $email !== '' ? $email : null,
                ':phone'     => $phone !== '' ? $phone : null
            ]);
            $student_id = $this->db->lastInsertId();

            $stmt = $this->db->prepare(
                "INSERT INTO enrollments (student_id, class_id)
                 VALUES (:student_id, :class_id)");
            $stmt->execute([':student_id' => $student_id, ':class_id' => $class_id]);

            $stmt = $this->db->prepare(
                "UPDATE classes SET slots = slots - 1 WHERE class_id = :id");
            $stmt->execute([':id' => $class_id]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Enroll an EXISTING student into a class.
    // Returns true on success, false when full.
    // Throws RuntimeException if the student is already enrolled (duplicate).
    public function enroll($student_id, $class_id) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT slots FROM classes WHERE class_id = :id FOR UPDATE");
            $stmt->execute([':id' => $class_id]);
            $class = $stmt->fetch();

            if (!$class || (int) $class['slots'] <= 0) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM enrollments
                 WHERE student_id = :student_id AND class_id = :class_id
                   AND status = 'active'");
            $stmt->execute([':student_id' => $student_id, ':class_id' => $class_id]);
            if ((int) $stmt->fetchColumn() > 0) {
                $this->db->rollBack();
                throw new RuntimeException('Student is already enrolled in this class.');
            }

            $stmt = $this->db->prepare(
                "INSERT INTO enrollments (student_id, class_id)
                 VALUES (:student_id, :class_id)");
            $stmt->execute([':student_id' => $student_id, ':class_id' => $class_id]);

            $stmt = $this->db->prepare(
                "UPDATE classes SET slots = slots - 1 WHERE class_id = :id");
            $stmt->execute([':id' => $class_id]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Cancel an enrollment and give the slot back.
    // Returns false if it doesn't exist or is already cancelled.
    public function cancel($enrollment_id) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT class_id, status FROM enrollments
                 WHERE enrollment_id = :id FOR UPDATE");
            $stmt->execute([':id' => $enrollment_id]);
            $enrollment = $stmt->fetch();

            if (!$enrollment || $enrollment['status'] !== 'active') {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare(
                "UPDATE enrollments SET status = 'cancelled'
                 WHERE enrollment_id = :id");
            $stmt->execute([':id' => $enrollment_id]);

            $stmt = $this->db->prepare(
                "UPDATE classes SET slots = slots + 1 WHERE class_id = :id");
            $stmt->execute([':id' => $enrollment['class_id']]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function allWithDetails() {
        $sql = "SELECT e.enrollment_id, e.enrollment_date, e.status,
                       s.student_id, s.full_name, s.email,
                       c.class_id, c.class_code, c.schedule, c.instructor,
                       co.course_name
                FROM enrollments e
                INNER JOIN students s ON e.student_id = s.student_id
                INNER JOIN classes c  ON e.class_id = c.class_id
                INNER JOIN courses co ON c.course_id = co.course_id
                ORDER BY e.enrollment_date DESC, e.enrollment_id DESC";
        return $this->db->query($sql)->fetchAll();
    }

        // Overall counts for the report summary cards
    public function totals() {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM students) AS students,
                    (SELECT COUNT(*) FROM courses)  AS courses,
                    (SELECT COUNT(*) FROM classes)  AS classes,
                    (SELECT COUNT(*) FROM enrollments WHERE status = 'active')    AS active_enrollments,
                    (SELECT COUNT(*) FROM enrollments WHERE status = 'cancelled') AS cancelled_enrollments";
        return $this->db->query($sql)->fetch();
    }

    // One row per class, including classes with no enrollments (LEFT JOIN)
    public function classSummary() {
        $sql = "SELECT c.class_id, co.course_name, c.class_code, c.schedule,
                       c.instructor, c.slots AS slots_left,
                       COALESCE(SUM(e.status = 'active'), 0)    AS active_count,
                       COALESCE(SUM(e.status = 'cancelled'), 0) AS cancelled_count
                FROM classes c
                INNER JOIN courses co ON c.course_id = co.course_id
                LEFT JOIN enrollments e ON e.class_id = c.class_id
                GROUP BY c.class_id, co.course_name, c.class_code,
                         c.schedule, c.instructor, c.slots
                ORDER BY co.course_name, c.class_code";
        return $this->db->query($sql)->fetchAll();
    }
}