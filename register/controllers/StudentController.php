<?php

require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/AssessmentResult.php';

class StudentController {

    public function detail(array $params): void {
        $user = Auth::requireAuth();
        $studentId = (int)$params['id'];
        $student = Student::findById($studentId, Auth::schoolId());
        if (!$student) { Response::redirect('/dashboard'); }

        // Get courses this student is enrolled in
        $courses = Database::fetchAll(
            'SELECT DISTINCT c.* FROM courses c
             JOIN group_course_assignments gca ON c.id = gca.course_id
             JOIN student_group_members sgm ON gca.group_id = sgm.group_id
             WHERE sgm.student_id = ? AND c.school_id = ?',
            [$studentId, Auth::schoolId()]
        );

        // Get risk scores for each course
        $riskScores = [];
        foreach ($courses as $course) {
            $rs = Database::fetch(
                'SELECT * FROM risk_scores WHERE student_id = ? AND course_id = ?',
                [$studentId, $course['id']]
            );
            if ($rs) {
                $rs['course_name'] = $course['name'];
                $rs['course_code'] = $course['code'];
                $rs['explanations_array'] = json_decode($rs['explanations'], true) ?? [];
                $riskScores[] = $rs;
            }
        }

        // Get attendance history (most recent course)
        $attendanceHistory = [];
        if (!empty($courses)) {
            $attendanceHistory = Database::fetchAll(
                'SELECT ar.status, ar.notes, ss.week_number, ss.day_of_week, ss.session_date
                 FROM attendance_records ar
                 JOIN scheduled_sessions ss ON ar.session_id = ss.id
                 WHERE ar.student_id = ? AND ss.course_id = ?
                 ORDER BY ss.session_date ASC',
                [$studentId, $courses[0]['id']]
            );
        }

        // Get sentiment cache
        $sentiments = Database::fetchAll(
            'SELECT sc.*, c.name as course_name FROM sentiment_cache sc
             JOIN courses c ON sc.course_id = c.id
             WHERE sc.student_id = ?',
            [$studentId]
        );

        $title = $student['first_name'] . ' ' . $student['last_name'];
        ob_start();
        require __DIR__ . '/../views/students/detail.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }
}
