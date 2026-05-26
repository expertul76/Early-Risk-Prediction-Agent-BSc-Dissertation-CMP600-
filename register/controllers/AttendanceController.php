<?php

require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Course.php';

class AttendanceController {

    public function sessions(array $params): void {
        $user = Auth::requireAuth();
        $courseId = (int)$params['courseId'];
        $course = Course::findById($courseId, Auth::schoolId());
        if (!$course) { Response::redirect('/courses'); }

        $sessions = Attendance::getSessionsForCourse($courseId);

        $title = 'Attendance - ' . $course['name'];
        ob_start();
        require __DIR__ . '/../views/attendance/sessions.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function registerPage(array $params): void {
        $user = Auth::requireAuth();
        $sessionId = (int)$params['id'];
        $session = Attendance::getSession($sessionId);
        if (!$session || $session['school_id'] != Auth::schoolId()) {
            Response::redirect('/dashboard');
        }

        $students = Attendance::getStudentsForSession($sessionId, $session['group_id']);

        $title = 'Register - Week ' . $session['week_number'] . ' ' . $session['day_of_week'];
        ob_start();
        require __DIR__ . '/../views/attendance/register.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function saveRegister(array $params): void {
        Auth::requireAuth();
        Auth::requireCsrf();

        $sessionId = (int)$params['id'];
        $session = Attendance::getSession($sessionId);
        if (!$session || $session['school_id'] != Auth::schoolId()) {
            Response::redirect('/dashboard');
        }

        $userId = (int)Auth::user()['id'];
        $statuses = $_POST['status'] ?? [];
        $notes = $_POST['notes'] ?? [];

        foreach ($statuses as $studentId => $status) {
            $validStatuses = ['attending', 'late', 'not_attending', 'authorised_absence'];
            if (!in_array($status, $validStatuses)) continue;

            Attendance::saveRecord(
                $sessionId,
                (int)$studentId,
                $status,
                $notes[$studentId] ?? null,
                $userId
            );
        }

        Response::flash('success', 'Attendance saved.');
        Response::redirect('/attendance/session/' . $sessionId);
    }

    public function complete(array $params): void {
        Auth::requireAuth();
        Auth::requireCsrf();

        $sessionId = (int)$params['id'];
        $session = Attendance::getSession($sessionId);
        if (!$session || $session['school_id'] != Auth::schoolId()) {
            Response::redirect('/dashboard');
        }

        $userId = (int)Auth::user()['id'];
        Attendance::completeSession($sessionId, $userId);

        // Trigger risk recalculation for this course
        require_once __DIR__ . '/../services/RiskEngine.php';
        RiskEngine::calculateForCourse($session['course_id']);

        Response::flash('success', 'Session completed. Risk scores updated.');
        Response::redirect('/attendance/' . $session['course_id']);
    }
}
