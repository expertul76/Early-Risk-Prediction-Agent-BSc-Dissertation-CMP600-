<?php
require_once __DIR__ . '/../models/StudentGroup.php';
require_once __DIR__ . '/../models/Student.php';
require_once __DIR__ . '/../models/Course.php';

class GroupController {
    public function index(): void {
        $user = Auth::requireAuth();
        $groups = StudentGroup::listBySchool(Auth::schoolId());

        $title = 'Student Groups';
        ob_start();
        require __DIR__ . '/../views/groups/index.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function createPage(): void {
        Auth::requireAuth();
        $title = 'Create Student Group';
        ob_start();
        require __DIR__ . '/../views/groups/create.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function create(): void {
        Auth::requireAuth();
        Auth::requireCsrf();

        $v = new Validator($_POST);
        $v->required('name', 'Group name');

        if (!$v->passes()) {
            Response::flash('error', $v->firstError());
            Response::redirect('/groups/create');
        }

        $groupId = StudentGroup::create([
            'school_id' => Auth::schoolId(),
            'name' => $_POST['name'],
        ]);

        Response::flash('success', 'Group created.');
        Response::redirect('/groups/' . $groupId);
    }

    public function students(array $params): void {
        $user = Auth::requireAuth();
        $group = StudentGroup::findById((int)$params['id'], Auth::schoolId());
        if (!$group) { Response::redirect('/groups'); }

        $students = StudentGroup::getStudents($group['id']);
        $assignments = StudentGroup::getAssignments($group['id']);
        $courses = Course::listBySchool(Auth::schoolId());

        $title = $group['name'];
        ob_start();
        require __DIR__ . '/../views/groups/students.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function addStudent(array $params): void {
        Auth::requireAuth();
        Auth::requireCsrf();

        $groupId = (int)$params['id'];
        $group = StudentGroup::findById($groupId, Auth::schoolId());
        if (!$group) { Response::redirect('/groups'); }

        $v = new Validator($_POST);
        $v->required('first_name', 'First name')->required('last_name', 'Last name');

        if (!$v->passes()) {
            Response::flash('error', $v->firstError());
            Response::redirect('/groups/' . $groupId);
        }

        $studentId = Student::create([
            'school_id' => Auth::schoolId(),
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'] ?? null,
            'student_ext_id' => $_POST['student_id'] ?? null,
        ]);

        StudentGroup::addStudent($groupId, $studentId);
        Response::flash('success', 'Student added.');
        Response::redirect('/groups/' . $groupId);
    }

    public function importCsv(array $params): void {
        Auth::requireAuth();
        Auth::requireCsrf();

        $groupId = (int)$params['id'];
        $group = StudentGroup::findById($groupId, Auth::schoolId());
        if (!$group) { Response::redirect('/groups'); }

        $file = $_FILES['csv_file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            Response::flash('error', 'Please upload a valid CSV file.');
            Response::redirect('/groups/' . $groupId);
        }

        $content = file_get_contents($file['tmp_name']);
        // Remove BOM
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $lines = array_filter(explode("\n", $content), fn($l) => trim($l) !== '');
        if (count($lines) < 2) {
            Response::flash('error', 'CSV file is empty or has no data rows.');
            Response::redirect('/groups/' . $groupId);
        }

        // Parse header
        $delimiter = strpos($lines[0], ';') !== false ? ';' : ',';
        $headers = array_map('trim', array_map('strtolower', str_getcsv($lines[0], $delimiter)));

        // Find column indexes
        $fnIdx = array_search('first_name', $headers) !== false ? array_search('first_name', $headers) : array_search('first name', $headers);
        $lnIdx = array_search('last_name', $headers) !== false ? array_search('last_name', $headers) : array_search('last name', $headers);
        $emIdx = array_search('email', $headers) !== false ? array_search('email', $headers) : (array_search('email address', $headers) !== false ? array_search('email address', $headers) : false);
        $idIdx = array_search('student_id', $headers) !== false ? array_search('student_id', $headers) : (array_search('student id', $headers) !== false ? array_search('student id', $headers) : false);

        if ($fnIdx === false || $lnIdx === false) {
            Response::flash('error', 'CSV must have "first_name" and "last_name" columns.');
            Response::redirect('/groups/' . $groupId);
        }

        $imported = 0;
        for ($i = 1; $i < count($lines); $i++) {
            $row = str_getcsv($lines[$i], $delimiter);
            $firstName = trim($row[$fnIdx] ?? '');
            $lastName = trim($row[$lnIdx] ?? '');
            if (!$firstName || !$lastName) continue;

            $email = ($emIdx !== false) ? trim($row[$emIdx] ?? '') : null;
            $extId = ($idIdx !== false) ? trim($row[$idIdx] ?? '') : null;

            $studentId = Student::create([
                'school_id' => Auth::schoolId(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email ?: null,
                'student_ext_id' => $extId ?: null,
            ]);

            StudentGroup::addStudent($groupId, $studentId);
            $imported++;
        }

        Response::flash('success', "{$imported} students imported.");
        Response::redirect('/groups/' . $groupId);
    }

    public function assignCoursePage(array $params): void {
        $user = Auth::requireAuth();
        $group = StudentGroup::findById((int)$params['id'], Auth::schoolId());
        if (!$group) { Response::redirect('/groups'); }

        $courses = Course::listBySchool(Auth::schoolId());

        $title = 'Assign Course - ' . $group['name'];
        ob_start();
        require __DIR__ . '/../views/groups/assign-course.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    public function assignCourse(array $params): void {
        Auth::requireAuth();
        Auth::requireCsrf();

        $groupId = (int)$params['id'];
        $group = StudentGroup::findById($groupId, Auth::schoolId());
        if (!$group) { Response::redirect('/groups'); }

        $courseId = (int)$_POST['course_id'];
        $course = Course::findById($courseId, Auth::schoolId());
        if (!$course) {
            Response::flash('error', 'Invalid course.');
            Response::redirect('/groups/' . $groupId . '/assign-course');
        }

        $days = $_POST['days'] ?? [];
        if (empty($days)) {
            Response::flash('error', 'Select at least one day.');
            Response::redirect('/groups/' . $groupId . '/assign-course');
        }

        // Check if already assigned
        $existing = Database::fetch(
            'SELECT id FROM group_course_assignments WHERE group_id = ? AND course_id = ?',
            [$groupId, $courseId]
        );
        if ($existing) {
            Response::flash('error', 'This group is already assigned to this course.');
            Response::redirect('/groups/' . $groupId);
        }

        $assignmentId = Database::insert('group_course_assignments', [
            'group_id' => $groupId,
            'course_id' => $courseId,
            'days_of_week' => json_encode($days),
            'sessions_per_week' => count($days),
        ]);

        // Generate scheduled sessions
        require_once __DIR__ . '/../services/SessionGenerator.php';
        SessionGenerator::generate($assignmentId, $course);

        Response::flash('success', 'Group assigned to ' . $course['name'] . '. Sessions have been generated.');
        Response::redirect('/groups/' . $groupId);
    }
}
