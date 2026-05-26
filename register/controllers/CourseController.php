<?php
require_once __DIR__ . '/../models/Course.php';

class CourseController {
    public function index(): void {
        $user = Auth::requireAuth();
        $courses = Course::listBySchool(Auth::schoolId());

        $title = 'Courses';
        ob_start();
        require __DIR__ . '/../views/courses/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function createPage(): void {
        $user = Auth::requireAdmin();
        $course = null;
        $assessments = [];

        $title = 'Create Course';
        ob_start();
        require __DIR__ . '/../views/courses/create.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::requireAdmin();
        Auth::requireCsrf();

        $v = new Validator($_POST);
        $v->required('name', 'Course name')
          ->required('code', 'Course code')
          ->required('num_weeks', 'Number of weeks')->numeric('num_weeks')->min('num_weeks', 1)
          ->required('start_date', 'Start date')->date('start_date');

        if (!$v->passes()) {
            Response::flash('error', $v->firstError());
            Response::redirect('/courses/create');
        }

        $courseId = Course::create([
            'school_id' => Auth::schoolId(),
            'name' => $_POST['name'],
            'code' => strtoupper(trim($_POST['code'])),
            'description' => $_POST['description'] ?? '',
            'num_weeks' => (int)$_POST['num_weeks'],
            'start_date' => $_POST['start_date'],
        ]);

        // Save assessments
        if (!empty($_POST['assessment_name'])) {
            foreach ($_POST['assessment_name'] as $i => $name) {
                if (empty(trim($name))) continue;
                Course::addAssessment([
                    'course_id' => $courseId,
                    'name' => trim($name),
                    'type' => $_POST['assessment_type'][$i] ?? 'formative',
                    'week_due' => (int)($_POST['assessment_week'][$i] ?? 1),
                    'weight' => (float)($_POST['assessment_weight'][$i] ?? 1),
                    'sort_order' => $i,
                ]);
            }
        }

        Response::flash('success', 'Course created successfully.');
        Response::redirect('/courses/' . $courseId);
    }

    public function editPage(array $params): void {
        $user = Auth::requireAdmin();
        $course = Course::findById((int)$params['id'], Auth::schoolId());
        if (!$course) { Response::redirect('/courses'); }

        $assessments = Course::getAssessments($course['id']);

        $title = 'Edit ' . $course['name'];
        ob_start();
        require __DIR__ . '/../views/courses/create.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function edit(array $params): void {
        Auth::requireAdmin();
        Auth::requireCsrf();

        $id = (int)$params['id'];
        $course = Course::findById($id, Auth::schoolId());
        if (!$course) { Response::redirect('/courses'); }

        Course::update($id, [
            'name' => $_POST['name'],
            'code' => strtoupper(trim($_POST['code'])),
            'description' => $_POST['description'] ?? '',
            'num_weeks' => (int)$_POST['num_weeks'],
            'start_date' => $_POST['start_date'],
        ], Auth::schoolId());

        // Replace assessments
        Course::deleteAssessments($id);
        if (!empty($_POST['assessment_name'])) {
            foreach ($_POST['assessment_name'] as $i => $name) {
                if (empty(trim($name))) continue;
                Course::addAssessment([
                    'course_id' => $id,
                    'name' => trim($name),
                    'type' => $_POST['assessment_type'][$i] ?? 'formative',
                    'week_due' => (int)($_POST['assessment_week'][$i] ?? 1),
                    'weight' => (float)($_POST['assessment_weight'][$i] ?? 1),
                    'sort_order' => $i,
                ]);
            }
        }

        Response::flash('success', 'Course updated.');
        Response::redirect('/courses/' . $id);
    }

    public function overview(array $params): void {
        $user = Auth::requireAuth();
        $course = Course::findById((int)$params['id'], Auth::schoolId());
        if (!$course) { Response::redirect('/courses'); }

        $assessments = Course::getAssessments($course['id']);

        // Get students enrolled via group assignments
        $students = Database::fetchAll(
            'SELECT DISTINCT s.* FROM students s
             JOIN student_group_members sgm ON s.id = sgm.student_id
             JOIN group_course_assignments gca ON sgm.group_id = gca.group_id
             WHERE gca.course_id = ?
             ORDER BY s.last_name, s.first_name',
            [$course['id']]
        );

        // Get risk scores
        $riskScores = Database::fetchAll(
            'SELECT * FROM risk_scores WHERE course_id = ?',
            [$course['id']]
        );
        $riskMap = [];
        foreach ($riskScores as $rs) { $riskMap[$rs['student_id']] = $rs; }

        // Sessions
        $totalSessions = Database::fetch('SELECT COUNT(*) as cnt FROM scheduled_sessions WHERE course_id = ?', [$course['id']])['cnt'] ?? 0;
        $completedSessions = Database::fetch('SELECT COUNT(*) as cnt FROM scheduled_sessions WHERE course_id = ? AND is_completed = 1', [$course['id']])['cnt'] ?? 0;

        $title = $course['name'];
        ob_start();
        require __DIR__ . '/../views/courses/overview.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }
}
