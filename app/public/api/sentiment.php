<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$prompt = $input['prompt'] ?? '';

if (!$prompt) {
    http_response_code(400);
    echo json_encode(['error' => 'Prompt is required']);
    exit;
}

// Gemini API credentials — read from environment (never commit secrets)
define('GEMINI_API_KEY_SENTIMENT', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL_SENTIMENT', getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash');

if (!GEMINI_API_KEY_SENTIMENT) {
    http_response_code(503);
    echo json_encode(['error' => 'Gemini API key not configured. Set GEMINI_API_KEY in the environment.']);
    exit;
}

$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL_SENTIMENT . ':generateContent?key=' . GEMINI_API_KEY_SENTIMENT;

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

if ($httpCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'Gemini API error', 'status' => $httpCode]);
    exit;
}

$data = json_decode($response, true);
$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

echo json_encode(['result' => $text]);
