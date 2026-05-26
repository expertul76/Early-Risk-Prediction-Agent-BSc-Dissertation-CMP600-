<?php
class StudentGroup {
    public static function create(array $data): int {
        return Database::insert('student_groups', $data);
    }

    public static function findById(int $id, int $schoolId): ?array {
        return Database::fetch('SELECT * FROM student_groups WHERE id = ? AND school_id = ?', [$id, $schoolId]);
    }

    public static function listBySchool(int $schoolId): array {
        return Database::fetchAll(
            'SELECT sg.*,
                    (SELECT COUNT(*) FROM student_group_members sgm WHERE sgm.group_id = sg.id) as student_count
             FROM student_groups sg WHERE sg.school_id = ? ORDER BY sg.name',
            [$schoolId]
        );
    }

    public static function getStudents(int $groupId): array {
        return Database::fetchAll(
            'SELECT s.* FROM students s
             JOIN student_group_members sgm ON s.id = sgm.student_id
             WHERE sgm.group_id = ?
             ORDER BY s.last_name, s.first_name',
            [$groupId]
        );
    }

    public static function addStudent(int $groupId, int $studentId): void {
        $existing = Database::fetch(
            'SELECT * FROM student_group_members WHERE group_id = ? AND student_id = ?',
            [$groupId, $studentId]
        );
        if (!$existing) {
            Database::insert('student_group_members', ['group_id' => $groupId, 'student_id' => $studentId]);
        }
    }

    public static function removeStudent(int $groupId, int $studentId): void {
        Database::delete('student_group_members', 'group_id = ? AND student_id = ?', [$groupId, $studentId]);
    }

    public static function getAssignments(int $groupId): array {
        return Database::fetchAll(
            'SELECT gca.*, c.name as course_name, c.code as course_code, c.num_weeks
             FROM group_course_assignments gca
             JOIN courses c ON gca.course_id = c.id
             WHERE gca.group_id = ?',
            [$groupId]
        );
    }
}
