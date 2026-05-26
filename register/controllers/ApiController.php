<?php

require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../services/RiskEngine.php';
require_once __DIR__ . '/../services/SentimentService.php';

class ApiController {

    public function calculateRisk(array $params): void {
        $user = Auth::requireAuth();
        $courseId = (int)$params['courseId'];
        $course = Course::findById($courseId, Auth::schoolId());
        if (!$course) {
            Response::json(['error' => 'Course not found'], 404);
        }

        RiskEngine::calculateForCourse($courseId);

        Response::json(['success' => true, 'message' => 'Risk scores recalculated']);
    }

    public function runSentiment(array $params): void {
        $user = Auth::requireAuth();
        $courseId = (int)$params['courseId'];
        $course = Course::findById($courseId, Auth::schoolId());
        if (!$course) {
            Response::json(['error' => 'Course not found'], 404);
        }

        $result = SentimentService::analyseForCourse($courseId);

        // Recalculate risk scores with sentiment data
        RiskEngine::calculateForCourse($courseId);

        Response::json([
            'success' => true,
            'analysed' => $result['analysed'],
            'skipped' => $result['skipped'],
            'message' => "Analysed {$result['analysed']} students, skipped {$result['skipped']} (unchanged).",
        ]);
    }
}
