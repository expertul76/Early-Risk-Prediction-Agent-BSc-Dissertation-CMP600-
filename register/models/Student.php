<?php
class Student {
    public static function create(array $data): int {
        return Database::insert('students', $data);
    }

    public static function findById(int $id, int $schoolId): ?array {
        return Database::fetch('SELECT * FROM students WHERE id = ? AND school_id = ?', [$id, $schoolId]);
    }

    public static function findByExtId(string $extId, int $schoolId): ?array {
        return Database::fetch('SELECT * FROM students WHERE student_ext_id = ? AND school_id = ?', [$extId, $schoolId]);
    }

    public static function listBySchool(int $schoolId): array {
        return Database::fetchAll('SELECT * FROM students WHERE school_id = ? ORDER BY last_name, first_name', [$schoolId]);
    }
}
