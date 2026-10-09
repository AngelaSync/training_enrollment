<?php
// classes/ClassSection.php
class ClassSection {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }

    public function allWithCourse() {
        $sql = "SELECT c.*, co.course_name
                FROM classes c
                JOIN courses co ON c.course_id = co.course_id
                ORDER BY co.course_name, c.class_code";
        return $this->db->query($sql)->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM classes WHERE class_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create($course_id, $code, $schedule, $instructor, $slots) {
        $stmt = $this->db->prepare(
            "INSERT INTO classes (course_id, class_code, schedule, instructor, slots)
             VALUES (:course_id, :code, :schedule, :instructor, :slots)");
        $stmt->execute([
            ':course_id'  => $course_id,
            ':code'       => $code,
            ':schedule'   => $schedule,
            ':instructor' => $instructor,
            ':slots'      => $slots
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $course_id, $code, $schedule, $instructor, $slots) {
        $stmt = $this->db->prepare(
            "UPDATE classes
             SET course_id = :course_id, class_code = :code, schedule = :schedule,
                 instructor = :instructor, slots = :slots
             WHERE class_id = :id");
        return $stmt->execute([
            ':course_id'  => $course_id,
            ':code'       => $code,
            ':schedule'   => $schedule,
            ':instructor' => $instructor,
            ':slots'      => $slots,
            ':id'         => $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM classes WHERE class_id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getSlots($class_id) {
        $stmt = $this->db->prepare("SELECT slots FROM classes WHERE class_id = :id");
        $stmt->execute([':id' => $class_id]);
        $row = $stmt->fetch();
        return $row ? (int) $row['slots'] : 0;
    }
}