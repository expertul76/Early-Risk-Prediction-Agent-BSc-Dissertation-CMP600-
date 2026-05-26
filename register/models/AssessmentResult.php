<?php

class AssessmentResult {
    public static function getForCourse(int $courseId): array {
        return Database::fetchAll(
            'SELECT ar.*, a.name as assessment_name, a.type, a.week_due
             FROM assessment_results ar
             JOIN assessments a ON ar.assessment_id = a.id
             WHERE a.course_id = ?',
            [$courseId]
        );
    }

    public static function getForStudent(int $studentId, int $courseId): array {
        return Database::fetchAll(
            'SELECT ar.*, a.name as assessment_name, a.type, a.week_due, a.weight
             FROM assessment_results ar
             JOIN assessments a ON ar.assessment_id = a.id
             WHERE ar.student_id = ? AND a.course_id = ?
             ORDER BY a.sort_order',
            [$studentId, $courseId]
        );
    }

    public static function save(int $assessmentId, int $studentId, string $status, ?float $grade, ?string $feedback): void {
        $existing = Database::fetch(
            'SELECT id FROM assessment_results WHERE assessment_id = ? AND student_id = ?',
            [$assessmentId, $studentId]
        );

        $data = [
            'status' => $status,
            'grade' => $grade,
            'feedback' => $feedback,
            'submitted_at' => in_array($status, ['completed', 'late']) ? date('Y-m-d H:i:s') : null,
        ];

        if ($existing) {
            Database::update('assessment_results', $data, 'id = ?', [$existing['id']]);
        } else {
            Database::insert('assessment_results', array_merge($data, [
                'assessment_id' => $assessmentId,
                'student_id' => $studentId,
            ]));
        }
    }
}
