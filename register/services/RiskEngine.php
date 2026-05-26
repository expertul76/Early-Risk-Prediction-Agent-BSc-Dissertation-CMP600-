<?php

/**
 * Risk Engine - PHP port of Marcel's TypeScript risk scoring algorithm.
 * Calculates attendance, assessment, engagement, and composite risk scores.
 * Stores results statically in risk_scores table.
 */
class RiskEngine {

    // Weights
    const WEIGHTS_WITH_AI = ['attendance' => 0.30, 'assessment' => 0.30, 'engagement' => 0.20, 'sentiment' => 0.20];
    const WEIGHTS_WITHOUT_AI = ['attendance' => 0.35, 'assessment' => 0.35, 'engagement' => 0.30, 'sentiment' => 0.0];

    // Classification thresholds
    const THRESHOLD_LOW = 65;
    const THRESHOLD_MEDIUM = 40;

    /**
     * Calculate risk scores for ALL students in a course.
     */
    public static function calculateForCourse(int $courseId): void {
        // Get all students enrolled in this course
        $students = Database::fetchAll(
            'SELECT DISTINCT s.id, s.first_name, s.last_name
             FROM students s
             JOIN student_group_members sgm ON s.id = sgm.student_id
             JOIN group_course_assignments gca ON sgm.group_id = gca.group_id
             WHERE gca.course_id = ?',
            [$courseId]
        );

        foreach ($students as $student) {
            self::calculateForStudent($student['id'], $courseId);
        }
    }

    /**
     * Calculate risk score for a single student in a course.
     */
    public static function calculateForStudent(int $studentId, int $courseId): array {
        $attendance = self::calculateAttendanceScore($studentId, $courseId);
        $assessment = self::calculateAssessmentScore($studentId, $courseId);
        $engagement = self::calculateEngagementScore($studentId, $courseId, $attendance);

        // Check for cached sentiment
        $sentimentScore = null;
        $sentiment = Database::fetch(
            'SELECT score FROM sentiment_cache WHERE student_id = ? AND course_id = ?',
            [$studentId, $courseId]
        );
        if ($sentiment) {
            $sentimentScore = (float)$sentiment['score'];
        }

        // Select weights
        $useSentiment = $sentimentScore !== null;
        $weights = $useSentiment ? self::WEIGHTS_WITH_AI : self::WEIGHTS_WITHOUT_AI;

        // Composite
        $composite = $attendance['score'] * $weights['attendance']
                   + $assessment['score'] * $weights['assessment']
                   + $engagement['score'] * $weights['engagement']
                   + ($sentimentScore ?? 0) * $weights['sentiment'];

        $composite = round($composite, 1);
        $classification = self::classify($composite);

        $explanations = array_merge(
            ["Attendance: {$attendance['score']}/100 (weight: " . round($weights['attendance'] * 100) . "%)"],
            array_map(fn($e) => "  - {$e}", $attendance['explanations']),
            ["Assessment: {$assessment['score']}/100 (weight: " . round($weights['assessment'] * 100) . "%)"],
            array_map(fn($e) => "  - {$e}", $assessment['explanations']),
            ["Engagement: {$engagement['score']}/100 (weight: " . round($weights['engagement'] * 100) . "%)"],
            array_map(fn($e) => "  - {$e}", $engagement['explanations']),
            ["", "Composite Score: {$composite}/100 → " . strtoupper($classification) . " RISK"]
        );

        // Upsert risk_scores
        $existing = Database::fetch(
            'SELECT id FROM risk_scores WHERE student_id = ? AND course_id = ?',
            [$studentId, $courseId]
        );

        $data = [
            'composite_score' => $composite,
            'classification' => $classification,
            'attendance_score' => $attendance['score'],
            'assessment_score' => $assessment['score'],
            'engagement_score' => $engagement['score'],
            'sentiment_score' => $sentimentScore,
            'explanations' => json_encode($explanations),
            'calculated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            Database::update('risk_scores', $data, 'id = ?', [$existing['id']]);
        } else {
            Database::insert('risk_scores', array_merge($data, [
                'student_id' => $studentId,
                'course_id' => $courseId,
            ]));
        }

        return $data;
    }

    /**
     * Attendance Score (0-100)
     */
    private static function calculateAttendanceScore(int $studentId, int $courseId): array {
        $records = Database::fetchAll(
            'SELECT ar.status FROM attendance_records ar
             JOIN scheduled_sessions ss ON ar.session_id = ss.id
             WHERE ar.student_id = ? AND ss.course_id = ? AND ss.is_completed = 1
             ORDER BY ss.session_date ASC',
            [$studentId, $courseId]
        );

        $explanations = [];

        if (empty($records)) {
            return ['score' => 50.0, 'explanations' => ['No attendance data available']];
        }

        // Map to numeric values
        $values = array_map(function ($r) {
            return match ($r['status']) {
                'attending' => 100,
                'late' => 50,
                default => 0, // not_attending, authorised_absence
            };
        }, $records);

        $count = count($values);
        $average = array_sum($values) / $count;

        $presentCount = count(array_filter($values, fn($v) => $v === 100));
        $lateCount = count(array_filter($values, fn($v) => $v === 50));
        $absentCount = $count - $presentCount - $lateCount;
        $attendancePct = round(($presentCount / $count) * 100);

        $explanations[] = "{$attendancePct}% full attendance ({$presentCount}/{$count} sessions present, {$lateCount} late, {$absentCount} absent)";

        // Trend penalty
        $trendPenalty = 0;
        if ($count >= 4) {
            $mid = intdiv($count, 2);
            $earlyAvg = array_sum(array_slice($values, 0, $mid)) / $mid;
            $recentAvg = array_sum(array_slice($values, $mid)) / ($count - $mid);

            if ($recentAvg < $earlyAvg - 10) {
                $trendPenalty = min(15, ($earlyAvg - $recentAvg) * 0.3);
                $explanations[] = "Declining attendance trend (-" . round($trendPenalty) . " penalty)";
            } elseif ($recentAvg > $earlyAvg + 10) {
                $explanations[] = "Improving attendance trend in recent sessions";
            }
        }

        // Consecutive absence streak (excluding authorised)
        $maxStreak = 0;
        $currentStreak = 0;
        foreach ($records as $r) {
            if ($r['status'] === 'not_attending') {
                $currentStreak++;
                $maxStreak = max($maxStreak, $currentStreak);
            } else {
                $currentStreak = 0;
            }
        }

        $streakPenalty = 0;
        if ($maxStreak >= 3) {
            $streakPenalty = ($maxStreak - 2) * 5;
            $explanations[] = "{$maxStreak} consecutive absences detected (-{$streakPenalty} penalty)";
        }

        $score = max(0, min(100, $average - $trendPenalty - $streakPenalty));
        return ['score' => round($score, 1), 'explanations' => $explanations];
    }

    /**
     * Assessment Score (0-100)
     */
    private static function calculateAssessmentScore(int $studentId, int $courseId): array {
        $results = Database::fetchAll(
            'SELECT ar.status, ar.grade, a.type, a.name
             FROM assessment_results ar
             JOIN assessments a ON ar.assessment_id = a.id
             WHERE ar.student_id = ? AND a.course_id = ? AND ar.status != ?',
            [$studentId, $courseId, 'not_given']
        );

        $explanations = [];

        if (empty($results)) {
            return ['score' => 50.0, 'explanations' => ['No assessment data available']];
        }

        $totalPoints = 0;
        $totalWeight = 0;
        $completedCount = 0;
        $lateCount = 0;
        $missingCount = 0;

        foreach ($results as $r) {
            $weight = $r['type'] === 'summative' ? 2 : 1;

            if ($r['status'] === 'not_completed') {
                $missingCount++;
                $points = 0;
            } elseif ($r['status'] === 'late') {
                $lateCount++;
                $grade = $r['grade'] !== null ? (float)$r['grade'] : 40;
                $points = $grade * 0.85;
            } else { // completed
                $completedCount++;
                $points = $r['grade'] !== null ? (float)$r['grade'] : 70;
            }

            $totalPoints += $points * $weight;
            $totalWeight += 100 * $weight;
        }

        $score = $totalWeight > 0 ? ($totalPoints / $totalWeight) * 100 : 50;

        $explanations[] = "{$completedCount}/" . count($results) . " assessments completed on time";
        if ($lateCount > 0) $explanations[] = "{$lateCount} late submission" . ($lateCount > 1 ? 's' : '') . " (15% penalty applied)";
        if ($missingCount > 0) $explanations[] = "{$missingCount} assessment" . ($missingCount > 1 ? 's' : '') . " not submitted";

        // Average grade
        $graded = array_filter($results, fn($r) => $r['grade'] !== null && $r['status'] !== 'not_completed');
        if (!empty($graded)) {
            $avgGrade = round(array_sum(array_map(fn($r) => (float)$r['grade'], $graded)) / count($graded));
            $explanations[] = "Average grade: {$avgGrade}%";
        }

        return ['score' => round(max(0, min(100, $score)), 1), 'explanations' => $explanations];
    }

    /**
     * Engagement Score (0-100)
     */
    private static function calculateEngagementScore(int $studentId, int $courseId, array $attendanceData): array {
        $score = 50;
        $explanations = [];

        // Factor 1: Attendance consistency
        $records = Database::fetchAll(
            'SELECT ar.status FROM attendance_records ar
             JOIN scheduled_sessions ss ON ar.session_id = ss.id
             WHERE ar.student_id = ? AND ss.course_id = ? AND ss.is_completed = 1',
            [$studentId, $courseId]
        );

        if (count($records) >= 3) {
            $values = array_map(fn($r) => match ($r['status']) {
                'attending' => 100, 'late' => 50, default => 0,
            }, $records);

            $mean = array_sum($values) / count($values);
            $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / count($values);

            if ($variance < 500) {
                $score += 15;
                $explanations[] = "Consistent attendance pattern (+15)";
            } elseif ($variance < 1500) {
                $score += 5;
                $explanations[] = "Moderately consistent attendance (+5)";
            } else {
                $score -= 10;
                $explanations[] = "Irregular attendance pattern (-10)";
            }
        }

        // Factor 2: Submission behaviour
        $assessmentResults = Database::fetchAll(
            'SELECT ar.status FROM assessment_results ar
             JOIN assessments a ON ar.assessment_id = a.id
             WHERE ar.student_id = ? AND a.course_id = ? AND ar.status != ?',
            [$studentId, $courseId, 'not_given']
        );

        $lateCount = count(array_filter($assessmentResults, fn($r) => $r['status'] === 'late'));
        $missingCount = count(array_filter($assessmentResults, fn($r) => $r['status'] === 'not_completed'));

        if ($lateCount > 0) {
            $penalty = $lateCount * 5;
            $score -= $penalty;
            $explanations[] = "{$lateCount} late submission" . ($lateCount > 1 ? 's' : '') . " (-{$penalty})";
        }
        if ($missingCount > 0) {
            $penalty = $missingCount * 15;
            $score -= $penalty;
            $explanations[] = "{$missingCount} missing assessment" . ($missingCount > 1 ? 's' : '') . " (-{$penalty})";
        }

        // Factor 3: Keyword analysis on session notes
        $notes = Database::fetchAll(
            'SELECT ar.notes FROM attendance_records ar
             JOIN scheduled_sessions ss ON ar.session_id = ss.id
             WHERE ar.student_id = ? AND ss.course_id = ? AND ar.notes IS NOT NULL AND ar.notes != ""',
            [$studentId, $courseId]
        );

        if (!empty($notes)) {
            $allNotes = strtolower(implode(' ', array_column($notes, 'notes')));

            $positiveKeywords = ['excellent', 'outstanding', 'actively', 'confident', 'insightful',
                'strong', 'engaged', 'improved', 'growth', 'encouraging', 'well-prepared', 'motivated',
                'enthusiastic', 'participating', 'collaborative'];
            $negativeKeywords = ['absent', 'struggling', 'disengaged', 'distracted', 'declined',
                'limited', 'cautious', 'difficulty', 'confused', 'hesitant', 'detached', 'withdrawn',
                'unresponsive', 'passive', 'concerning'];

            $posCount = count(array_filter($positiveKeywords, fn($k) => str_contains($allNotes, $k)));
            $negCount = count(array_filter($negativeKeywords, fn($k) => str_contains($allNotes, $k)));
            $netSentiment = ($posCount - $negCount) * 3;

            if ($netSentiment !== 0) {
                $score += $netSentiment;
                if ($netSentiment > 0) {
                    $explanations[] = "Positive engagement language detected (+{$netSentiment})";
                } else {
                    $explanations[] = "Concerning engagement language detected ({$netSentiment})";
                }
            }
        }

        return ['score' => round(max(0, min(100, $score)), 1), 'explanations' => $explanations];
    }

    /**
     * Classify risk level based on composite score.
     */
    private static function classify(float $composite): string {
        if ($composite >= self::THRESHOLD_LOW) return 'low';
        if ($composite >= self::THRESHOLD_MEDIUM) return 'medium';
        return 'high';
    }
}
