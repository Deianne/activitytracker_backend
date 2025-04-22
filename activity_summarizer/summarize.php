<?php
require 'config.php';
header('Content-Type: application/json');

// 1. Get and validate input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing activity ID']);
    exit;
}

// 2. Fetch activity
$stmt = $db->prepare("SELECT * FROM activities WHERE id = ?");
$stmt->execute([$data['id']]);
$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activity) {
    http_response_code(404);
    echo json_encode([
        'error' => 'Activity not found',
        'debug' => [
            'received_id' => $data['id'],
            'all_ids' => $db->query("SELECT id FROM activities")->fetchAll(PDO::FETCH_COLUMN)
        ]
    ]);
    exit;
}

// 3. Call Ollama
$prompt = "Summarize this activity: " . json_encode($activity);
$ollamaUrl = 'http://localhost:11434/api/generate';

$response = file_get_contents($ollamaUrl, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode([
            'model' => 'llama3',
            'prompt' => $prompt,
            'stream' => false
        ])
    ]
]));

echo $response;
?>