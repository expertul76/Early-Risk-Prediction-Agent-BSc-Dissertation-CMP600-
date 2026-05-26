<?php
require_once __DIR__ . '/auth_helper.php';

$user = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];

// Route: GET /api/reports.php = list, POST = create
// GET /api/reports.php?id=X = load one, PUT = update, DELETE = delete

$reportId = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($reportId) {
            loadReport($user, (int)$reportId);
        } else {
            listReports($user);
        }
        break;
    case 'POST':
        saveReport($user);
        break;
    case 'PUT':
        if (!$reportId) jsonResponse(['error' => 'Report ID required'], 400);
        updateReport($user, (int)$reportId);
        break;
    case 'DELETE':
        if (!$reportId) jsonResponse(['error' => 'Report ID required'], 400);
        deleteReport($user, (int)$reportId);
        break;
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

// ===== LIST REPORTS =====
function listReports(array $user): void {
    $reports = DB::fetchAll(
        'SELECT r.id, r.name, r.created_at, r.updated_at,
                COUNT(DISTINCT rc.id) as course_count,
                COUNT(DISTINCT rs.id) as student_count,
                (SELECT COUNT(*) FROM report_students rs2
                 JOIN report_courses rc2 ON rs2.report_course_id = rc2.id
                 WHERE rc2.report_id = r.id
                 AND JSON_EXTRACT(rs2.risk_score, "$.classification") = "high"
                ) as high_risk_count
         FROM reports r
         LEFT JOIN report_courses rc ON rc.report_id = r.id
         LEFT JOIN report_students rs ON rs.report_course_id = rc.id
         WHERE r.user_id = ?
         GROUP BY r.id
         ORDER BY r.updated_at DESC',
        [$user['id']]
    );

    // Add course names to each report
    foreach ($reports as &$report) {
        $courses = DB::fetchAll(
            'SELECT course_code, course_length, weeks_loaded, last_upload FROM report_courses WHERE report_id = ?',
            [$report['id']]
        );
        $report['courses'] = $courses;
    }

    jsonResponse(['reports' => $reports]);
}

// ===== LOAD FULL REPORT =====
function loadReport(array $user, int $reportId): void {
    $report = DB::fetch(
        'SELECT id, name, created_at, updated_at FROM reports WHERE id = ? AND user_id = ?',
        [$reportId, $user['id']]
    );

    if (!$report) {
        jsonResponse(['error' => 'Report not found'], 404);
    }

    $courses = DB::fetchAll(
        'SELECT id, course_code, course_name, course_length, weeks_loaded, last_upload
         FROM report_courses WHERE report_id = ?',
        [$reportId]
    );

    $students = [];
    foreach ($courses as $course) {
        $rows = DB::fetchAll(
            'SELECT student_ext_id, first_name, last_name, email,
                    student_data, risk_score, sentiment, teacher_review
             FROM report_students WHERE report_course_id = ?',
            [$course['id']]
        );

        foreach ($rows as $row) {
            $studentData = json_decode($row['student_data'], true);
            $student = [
                'id' => $row['student_ext_id'],
                'firstName' => $row['first_name'],
                'lastName' => $row['last_name'],
                'email' => $row['email'],
                'courseId' => $course['course_code'],
                'courseLength' => $course['course_length'],
                'attendance' => $studentData['attendance'] ?? [],
                'assessments' => $studentData['assessments'] ?? [],
                'existingMetrics' => $studentData['existingMetrics'] ?? null,
            ];

            if ($row['risk_score']) {
                $student['riskScore'] = json_decode($row['risk_score'], true);
            }
            if ($row['sentiment']) {
                $student['sentimentAnalysis'] = json_decode($row['sentiment'], true);
            }
            if ($row['teacher_review']) {
                $student['teacherReview'] = json_decode($row['teacher_review'], true);
            }

            $students[] = $student;
        }
    }

    $report['courses'] = $courses;
    $report['students'] = $students;

    jsonResponse(['report' => $report]);
}

// ===== SAVE NEW REPORT =====
function saveReport(array $user): void {
    $input = jsonInput();
    $name = trim($input['name'] ?? '');
    $students = $input['students'] ?? [];

    if (!$name) {
        jsonResponse(['error' => 'Report name is required'], 400);
    }
    if (empty($students)) {
        jsonResponse(['error' => 'No student data to save'], 400);
    }

    $pdo = DB::get();
    $pdo->beginTransaction();

    try {
        // Create report
        $reportId = DB::insert(
            'INSERT INTO reports (user_id, name) VALUES (?, ?)',
            [$user['id'], $name]
        );

        // Group students by course
        $byCourse = groupStudentsByCourse($students);

        foreach ($byCourse as $courseCode => $courseStudents) {
            $first = $courseStudents[0];
            $maxWeek = 0;
            foreach ($courseStudents as $s) {
                foreach ($s['attendance'] ?? [] as $w) {
                    $maxWeek = max($maxWeek, $w['week'] ?? 0);
                }
            }

            $courseId = DB::insert(
                'INSERT INTO report_courses (report_id, course_code, course_name, course_length, weeks_loaded)
                 VALUES (?, ?, ?, ?, ?)',
                [$reportId, $courseCode, $courseCode, $first['courseLength'] ?? 16, $maxWeek]
            );

            insertStudents($courseId, $courseStudents);
        }

        $pdo->commit();
        jsonResponse(['success' => true, 'reportId' => (int)$reportId], 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Failed to save report: ' . $e->getMessage()], 500);
    }
}

// ===== UPDATE REPORT (merge new CSV data) =====
function updateReport(array $user, int $reportId): void {
    $report = DB::fetch(
        'SELECT id FROM reports WHERE id = ? AND user_id = ?',
        [$reportId, $user['id']]
    );

    if (!$report) {
        jsonResponse(['error' => 'Report not found'], 404);
    }

    $input = jsonInput();
    $students = $input['students'] ?? [];

    if (empty($students)) {
        jsonResponse(['error' => 'No student data to update'], 400);
    }

    $pdo = DB::get();
    $pdo->beginTransaction();

    try {
        $byCourse = groupStudentsByCourse($students);
        $matchedCourses = [];
        $newCourses = [];

        foreach ($byCourse as $courseCode => $courseStudents) {
            // Check if this course already exists in the report
            $existingCourse = DB::fetch(
                'SELECT id, weeks_loaded FROM report_courses WHERE report_id = ? AND course_code = ?',
                [$reportId, $courseCode]
            );

            if ($existingCourse) {
                // UPDATE existing course — merge student data
                $matchedCourses[] = $courseCode;
                mergeStudents($existingCourse['id'], $courseStudents, (int)$existingCourse['weeks_loaded']);

                // Update weeks_loaded
                $maxWeek = (int)$existingCourse['weeks_loaded'];
                foreach ($courseStudents as $s) {
                    foreach ($s['attendance'] ?? [] as $w) {
                        $maxWeek = max($maxWeek, $w['week'] ?? 0);
                    }
                }

                DB::query(
                    'UPDATE report_courses SET weeks_loaded = ?, last_upload = NOW() WHERE id = ?',
                    [$maxWeek, $existingCourse['id']]
                );
            } else {
                // NEW course in this report
                $newCourses[] = $courseCode;
                $first = $courseStudents[0];
                $maxWeek = 0;
                foreach ($courseStudents as $s) {
                    foreach ($s['attendance'] ?? [] as $w) {
                        $maxWeek = max($maxWeek, $w['week'] ?? 0);
                    }
                }

                $courseId = DB::insert(
                    'INSERT INTO report_courses (report_id, course_code, course_name, course_length, weeks_loaded)
                     VALUES (?, ?, ?, ?, ?)',
                    [$reportId, $courseCode, $courseCode, $first['courseLength'] ?? 16, $maxWeek]
                );

                insertStudents($courseId, $courseStudents);
            }
        }

        // Update report timestamp
        DB::query('UPDATE reports SET updated_at = NOW() WHERE id = ?', [$reportId]);

        $pdo->commit();

        jsonResponse([
            'success' => true,
            'matched_courses' => $matchedCourses,
            'new_courses' => $newCourses,
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Failed to update report: ' . $e->getMessage()], 500);
    }
}

// ===== DELETE REPORT =====
function deleteReport(array $user, int $reportId): void {
    $report = DB::fetch(
        'SELECT id FROM reports WHERE id = ? AND user_id = ?',
        [$reportId, $user['id']]
    );

    if (!$report) {
        jsonResponse(['error' => 'Report not found'], 404);
    }

    DB::query('DELETE FROM reports WHERE id = ?', [$reportId]);
    jsonResponse(['success' => true]);
}

// ===== HELPERS =====

function groupStudentsByCourse(array $students): array {
    $byCourse = [];
    foreach ($students as $s) {
        $code = $s['courseId'] ?? 'UNKNOWN';
        $byCourse[$code][] = $s;
    }
    return $byCourse;
}

function insertStudents(string $courseId, array $students): void {
    $stmt = DB::get()->prepare(
        'INSERT INTO report_students (report_course_id, student_ext_id, first_name, last_name, email, student_data, risk_score, sentiment, teacher_review)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($students as $s) {
        $studentData = json_encode([
            'attendance' => $s['attendance'] ?? [],
            'assessments' => $s['assessments'] ?? [],
            'existingMetrics' => $s['existingMetrics'] ?? null,
        ]);

        $stmt->execute([
            $courseId,
            $s['id'] ?? 'UNK',
            $s['firstName'] ?? '',
            $s['lastName'] ?? '',
            $s['email'] ?? '',
            $studentData,
            isset($s['riskScore']) ? json_encode($s['riskScore']) : null,
            isset($s['sentimentAnalysis']) ? json_encode($s['sentimentAnalysis']) : null,
            isset($s['teacherReview']) ? json_encode($s['teacherReview']) : null,
        ]);
    }
}

function mergeStudents(int $courseId, array $newStudents, int $previousWeeksLoaded): void {
    foreach ($newStudents as $s) {
        $existing = DB::fetch(
            'SELECT id, student_data, teacher_review FROM report_students WHERE report_course_id = ? AND student_ext_id = ?',
            [$courseId, $s['id'] ?? 'UNK']
        );

        if ($existing) {
            // Merge attendance: keep old weeks, add new weeks that are beyond previousWeeksLoaded
            $oldData = json_decode($existing['student_data'], true);
            $oldAttendance = $oldData['attendance'] ?? [];
            $newAttendance = $s['attendance'] ?? [];

            // Build a map of existing weeks
            $weekMap = [];
            foreach ($oldAttendance as $w) {
                $weekMap[$w['week']] = $w;
            }

            // Add new weeks (overwrite if same week has new data)
            foreach ($newAttendance as $w) {
                if ($w['week'] > $previousWeeksLoaded || !isset($weekMap[$w['week']])) {
                    $weekMap[$w['week']] = $w;
                }
            }

            // Sort by week number
            ksort($weekMap);
            $mergedAttendance = array_values($weekMap);

            // Merge assessments: use the newer data (more complete)
            $mergedAssessments = !empty($s['assessments']) ? $s['assessments'] : ($oldData['assessments'] ?? []);

            $mergedData = json_encode([
                'attendance' => $mergedAttendance,
                'assessments' => $mergedAssessments,
                'existingMetrics' => $s['existingMetrics'] ?? ($oldData['existingMetrics'] ?? null),
            ]);

            // Preserve teacher review from DB
            DB::query(
                'UPDATE report_students SET first_name = ?, last_name = ?, email = ?,
                        student_data = ?, risk_score = ?, sentiment = ?, updated_at = NOW()
                 WHERE id = ?',
                [
                    $s['firstName'] ?? '',
                    $s['lastName'] ?? '',
                    $s['email'] ?? '',
                    $mergedData,
                    isset($s['riskScore']) ? json_encode($s['riskScore']) : null,
                    isset($s['sentimentAnalysis']) ? json_encode($s['sentimentAnalysis']) : null,
                    $existing['id'],
                ]
            );
        } else {
            // New student in existing course
            $studentData = json_encode([
                'attendance' => $s['attendance'] ?? [],
                'assessments' => $s['assessments'] ?? [],
                'existingMetrics' => $s['existingMetrics'] ?? null,
            ]);

            DB::insert(
                'INSERT INTO report_students (report_course_id, student_ext_id, first_name, last_name, email, student_data, risk_score, sentiment, teacher_review)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $courseId,
                    $s['id'] ?? 'UNK',
                    $s['firstName'] ?? '',
                    $s['lastName'] ?? '',
                    $s['email'] ?? '',
                    $studentData,
                    isset($s['riskScore']) ? json_encode($s['riskScore']) : null,
                    isset($s['sentimentAnalysis']) ? json_encode($s['sentimentAnalysis']) : null,
                    null,
                ]
            );
        }
    }
}
