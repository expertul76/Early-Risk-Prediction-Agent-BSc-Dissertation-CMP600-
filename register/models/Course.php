<?php
class Course {
    public static function create(array $data): int {
        return Database::insert('courses', $data);
    }

    public static function findById(int $id, int $schoolId): ?array {
        return Database::fetch('SELECT * FROM courses WHERE id = ? AND school_id = ?', [$id, $schoolId]);
    }

    public static function listBySchool(int $schoolId): array {
        return Database::fetchAll('SELECT * FROM courses WHERE school_id = ? ORDER BY created_at DESC', [$schoolId]);
    }

    public static function update(int $id, array $data, int $schoolId): void {
        Database::update('courses', $data, 'id = ? AND school_id = ?', [$id, $schoolId]);
    }

    // Assessments
    public static function getAssessments(int $courseId): array {
        return Database::fetchAll('SELECT * FROM assessments WHERE course_id = ? ORDER BY sort_order, week_due', [$courseId]);
    }

    public static function addAssessment(array $data): int {
        return Database::insert('assessments', $data);
    }

    public static function deleteAssessments(int $courseId): void {
        Database::delete('assessments', 'course_id = ?', [$courseId]);
    }
}
