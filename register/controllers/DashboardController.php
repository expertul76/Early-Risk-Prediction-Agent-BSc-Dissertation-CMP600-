<?php

class DashboardController {

    public function index(): void {
        $user = Auth::requireAuth();
        $schoolId = Auth::schoolId();

        // Get course count
        $courseCount = Database::fetch(
            'SELECT COUNT(*) as cnt FROM courses WHERE school_id = ?', [$schoolId]
        )['cnt'] ?? 0;

        // Get student count
        $studentCount = Database::fetch(
            'SELECT COUNT(*) as cnt FROM students WHERE school_id = ?', [$schoolId]
        )['cnt'] ?? 0;

        // Get staff count
        $staffCount = Database::fetch(
            'SELECT COUNT(*) as cnt FROM users WHERE school_id = ? AND is_active = 1', [$schoolId]
        )['cnt'] ?? 0;

        // Get high risk count
        $highRiskCount = Database::fetch(
            'SELECT COUNT(*) as cnt FROM risk_scores rs
             JOIN courses c ON rs.course_id = c.id
             WHERE c.school_id = ? AND rs.classification = ?',
            [$schoolId, 'high']
        )['cnt'] ?? 0;

        // Get courses with stats
        $courses = Database::fetchAll(
            'SELECT c.*,
                    (SELECT COUNT(DISTINCT sgm.student_id)
                     FROM group_course_assignments gca
                     JOIN student_group_members sgm ON gca.group_id = sgm.group_id
                     WHERE gca.course_id = c.id) as student_count,
                    (SELECT COUNT(*) FROM scheduled_sessions ss WHERE ss.course_id = c.id AND ss.is_completed = 1) as completed_sessions,
                    (SELECT COUNT(*) FROM scheduled_sessions ss WHERE ss.course_id = c.id) as total_sessions
             FROM courses c WHERE c.school_id = ? AND c.is_active = 1 ORDER BY c.created_at DESC',
            [$schoolId]
        );

        // Get top risk students across all courses
        $topRiskStudents = Database::fetchAll(
            'SELECT rs.*, s.first_name, s.last_name, s.email, c.name as course_name, c.code as course_code
             FROM risk_scores rs
             JOIN students s ON rs.student_id = s.id
             JOIN courses c ON rs.course_id = c.id
             WHERE c.school_id = ? AND rs.classification IN (?, ?)
             ORDER BY rs.composite_score ASC
             LIMIT 10',
            [$schoolId, 'high', 'medium']
        );

        $title = 'Dashboard';
        ob_start();
        require __DIR__ . '/../views/dashboard/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }
}
