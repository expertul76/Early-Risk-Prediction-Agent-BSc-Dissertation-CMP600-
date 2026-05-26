<?php

/**
 * Gemini AI Sentiment Analysis Service.
 * Analyses teacher notes about students using Google Gemini 2.5 Flash (free tier).
 * Caches results with a hash of notes to avoid redundant API calls.
 */
class SentimentService {

    const BATCH_SIZE = 10;

    /**
     * Run sentiment analysis for all students in a course.
     */
    public static function analyseForCourse(int $courseId): array {
        // Get students with notes
        $studentsWithNotes = Database::fetchAll(
            'SELECT DISTINCT s.id, s.first_name, s.last_name
             FROM students s
             JOIN student_group_members sgm ON s.id = sgm.student_id
             JOIN group_course_assignments gca ON sgm.group_id = gca.group_id
             JOIN attendance_records ar ON ar.student_id = s.id
             JOIN scheduled_sessions ss ON ar.session_id = ss.id AND ss.course_id = gca.course_id
             WHERE gca.course_id = ? AND ar.notes IS NOT NULL AND ar.notes != ""',
            [$courseId]
        );

        if (empty($studentsWithNotes)) {
            return ['analysed' => 0, 'skipped' => 0];
        }

        $analysed = 0;
        $skipped = 0;

        // Process in batches
        $batches = array_chunk($studentsWithNotes, self::BATCH_SIZE);

        foreach ($batches as $batch) {
            $studentData = [];

            foreach ($batch as $student) {
                // Get all notes for this student in this course
                $notes = Database::fetchAll(
                    'SELECT ss.week_number, ss.day_of_week, ar.notes
                     FROM attendance_records ar
                     JOIN scheduled_sessions ss ON ar.session_id = ss.id
                     WHERE ar.student_id = ? AND ss.course_id = ? AND ar.notes IS NOT NULL AND ar.notes != ""
                     ORDER BY ss.session_date',
                    [$student['id'], $courseId]
                );

                if (empty($notes)) continue;

                $noteText = implode("\n", array_map(
                    fn($n) => "Week {$n['week_number']} {$n['day_of_week']}: {$n['notes']}",
                    $notes
                ));

                $notesHash = md5($noteText);

                // Check cache
                $cached = Database::fetch(
                    'SELECT score, summary, notes_hash FROM sentiment_cache WHERE student_id = ? AND course_id = ?',
                    [$student['id'], $courseId]
                );

                if ($cached && $cached['notes_hash'] === $notesHash) {
                    $skipped++;
                    continue;
                }

                $studentData[] = [
                    'id' => $student['id'],
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'notes' => $noteText,
                    'hash' => $notesHash,
                ];
            }

            if (empty($studentData)) continue;

            // Call Gemini API
            $results = self::callGemini($studentData);

            foreach ($results as $result) {
                $studentInfo = null;
                foreach ($studentData as $sd) {
                    if ($sd['id'] == $result['studentId']) {
                        $studentInfo = $sd;
                        break;
                    }
                }
                if (!$studentInfo) continue;

                // Upsert sentiment cache
                $existing = Database::fetch(
                    'SELECT id FROM sentiment_cache WHERE student_id = ? AND course_id = ?',
                    [$result['studentId'], $courseId]
                );

                $data = [
                    'score' => max(0, min(100, (int)$result['score'])),
                    'summary' => $result['summary'] ?? '',
                    'notes_hash' => $studentInfo['hash'],
                ];

                if ($existing) {
                    Database::update('sentiment_cache', $data, 'id = ?', [$existing['id']]);
                } else {
                    Database::insert('sentiment_cache', array_merge($data, [
                        'student_id' => $result['studentId'],
                        'course_id' => $courseId,
                    ]));
                }

                $analysed++;
            }

            // Rate limit: small delay between batches
            if (count($batches) > 1) {
                usleep(500000); // 500ms
            }
        }

        return ['analysed' => $analysed, 'skipped' => $skipped];
    }

    /**
     * Call Gemini 2.5 Flash API with a batch of student notes.
     */
    private static function callGemini(array $studentData): array {
        $prompt = "You are an educational data analyst. Analyze the following student engagement notes. " .
            "For each student, assess the overall sentiment on a scale of 0-100 " .
            "(0 = extremely negative/disengaged, 100 = extremely positive/engaged). " .
            "Provide a one-sentence summary.\n\n";

        foreach ($studentData as $s) {
            $prompt .= "Student \"{$s['id']}\" ({$s['name']}):\n{$s['notes']}\n\n";
        }

        $prompt .= "Return ONLY a JSON array with this exact structure:\n" .
            '[{"studentId": "...", "score": 0-100, "summary": "..."}]';

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL .
            ":generateContent?key=" . GEMINI_API_KEY;

        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 2000,
                'responseMimeType' => 'application/json',
            ],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            // Return neutral scores on failure
            return array_map(fn($s) => [
                'studentId' => $s['id'],
                'score' => 50,
                'summary' => 'AI analysis unavailable',
            ], $studentData);
        }

        $data = json_decode($response, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Strip markdown code blocks
        $text = preg_replace('/```json?\n?/', '', $text);
        $text = preg_replace('/```/', '', $text);
        $text = trim($text);

        $parsed = json_decode($text, true);

        if (!is_array($parsed)) {
            return array_map(fn($s) => [
                'studentId' => $s['id'],
                'score' => 50,
                'summary' => 'AI response parsing failed',
            ], $studentData);
        }

        return $parsed;
    }
}
