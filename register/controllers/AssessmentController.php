<?php

require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/AssessmentResult.php';

class AssessmentController {

    public function resultsPage(array $params): void {
        $user = Auth::requireAuth();
        $courseId = (int)$params['courseId'];
        $course = Course::findById($courseId, Auth::schoolId());
        if (!$course) { Response::redirect('/courses'); }

        $assessments = Course::getAssessments($courseId);

        // Get enrolled students
        $students = Database::fetchAll(
            'SELECT DISTINCT s.* FROM students s
             JOIN student_group_members sgm ON s.id = sgm.student_id
             JOIN group_course_assignments gca ON sgm.group_id = gca.group_id
             WHERE gca.course_id = ?
             ORDER BY s.last_name, s.first_name',
            [$courseId]
        );

        // Get existing results
        $results = AssessmentResult::getForCourse($courseId);
        $resultMap = [];
        foreach ($results as $r) {
            $resultMap[$r['student_id'] . '-' . $r['assessment_id']] = $r;
        }

        $title = 'Assessments - ' . $course['name'];
        ob_start();
        require __DIR__ . '/../views/assessments/results.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function saveResults(array $params): void {
        Auth::requireAuth();
        Auth::requireCsrf();

        $courseId = (int)$params['courseId'];
        $course = Course::findById($courseId, Auth::schoolId());
        if (!$course) { Response::redirect('/courses'); }

        $statuses = $_POST['result_status'] ?? [];
        $grades = $_POST['result_grade'] ?? [];
        $feedbacks = $_POST['result_feedback'] ?? [];

        foreach ($statuses as $key => $status) {
            // key format: studentId-assessmentId
            [$studentId, $assessmentId] = explode('-', $key);
            $grade = !empty($grades[$key]) ? (float)$grades[$key] : null;
            $feedback = $feedbacks[$key] ?? null;

            AssessmentResult::save((int)$assessmentId, (int)$studentId, $status, $grade, $feedback);
        }

        // Recalculate risk scores
        require_once __DIR__ . '/../services/RiskEngine.php';
        RiskEngine::calculateForCourse($courseId);

        Response::flash('success', 'Assessment results saved. Risk scores updated.');
        Response::redirect('/assessments/' . $courseId);
    }
}
