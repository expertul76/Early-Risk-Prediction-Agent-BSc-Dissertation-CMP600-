<?php

class Attendance {
    public static function getSessionsForCourse(int $courseId): array {
        return Database::fetchAll(
            'SELECT ss.*, u.first_name as completed_by_name, u.last_name as completed_by_surname
             FROM scheduled_sessions ss
             LEFT JOIN users u ON ss.completed_by = u.id
             WHERE ss.course_id = ?
             ORDER BY ss.session_date ASC, ss.day_of_week ASC',
            [$courseId]
        );
    }

    public static function getSession(int $sessionId): ?array {
        return Database::fetch(
            'SELECT ss.*, c.name as course_name, c.code as course_code, c.school_id,
                    gca.group_id, sg.name as group_name
             FROM scheduled_sessions ss
             JOIN courses c ON ss.course_id = c.id
             JOIN group_course_assignments gca ON ss.assignment_id = gca.id
             JOIN student_groups sg ON gca.group_id = sg.id
             WHERE ss.id = ?',
            [$sessionId]
        );
    }

    public static function getStudentsForSession(int $sessionId, int $groupId): array {
        return Database::fetchAll(
            'SELECT s.*, ar.status, ar.notes
             FROM students s
             JOIN student_group_members sgm ON s.id = sgm.student_id
             LEFT JOIN attendance_records ar ON ar.student_id = s.id AND ar.session_id = ?
             WHERE sgm.group_id = ?
             ORDER BY s.last_name, s.first_name',
            [$sessionId, $groupId]
        );
    }

    public static function saveRecord(int $sessionId, int $studentId, string $status, ?string $notes, int $markedBy): void {
        $existing = Database::fetch(
            'SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?',
            [$sessionId, $studentId]
        );

        if ($existing) {
            Database::update('attendance_records', [
                'status' => $status,
                'notes' => $notes,
                'marked_by' => $markedBy,
                'marked_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$existing['id']]);
        } else {
            Database::insert('attendance_records', [
                'session_id' => $sessionId,
                'student_id' => $studentId,
                'status' => $status,
                'notes' => $notes,
                'marked_by' => $markedBy,
            ]);
        }
    }

    public static function completeSession(int $sessionId, int $userId): void {
        Database::update('scheduled_sessions', [
            'is_completed' => 1,
            'completed_by' => $userId,
            'completed_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$sessionId]);
    }
}
