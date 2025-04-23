<?php
header("Access-Control-Allow-Origin: *"); // Allow all origins
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit();
}
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Allowed activity types for dropdown
$allowed_types = ['study', 'work', 'exercise', 'hobby'];

switch ($method) {
    // GET: Fetch all activities
    case 'GET':
        $stmt = $db->query("SELECT * FROM activities ORDER BY created_at DESC");
        echo json_encode([
            'status' => 'success',
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'allowed_types' => $allowed_types // Frontend can use this for dropdown
        ]);
        break;

    // POST: Add new activity
    case 'POST':
        // Validate activity type
        if (!isset($input['activity_type']) || !in_array($input['activity_type'], $allowed_types)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid activity type. Allowed: ' . implode(', ', $allowed_types)
            ]);
            exit;
        }

        // Validate duration (must be positive number)
        if (!isset($input['duration_minutes']) || $input['duration_minutes'] <= 0) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Duration must be a positive number'
            ]);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO activities (activity_type, duration_minutes, description) 
                             VALUES (?, ?, ?)");
        $stmt->execute([
            $input['activity_type'], 
            $input['duration_minutes'], 
            $input['description'] ?? ''
        ]);
        echo json_encode([
            'status' => 'success',
            'id' => $db->lastInsertId()
        ]);
        break;

    // PUT: Update activity
    case 'PUT':
        // Validate activity type if provided
        if (isset($input['activity_type']) && !in_array($input['activity_type'], $allowed_types)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid activity type'
            ]);
            exit;
        }

        // Build dynamic update query
        $updates = [];
        $params = [];
        
        if (isset($input['activity_type'])) {
            $updates[] = "activity_type = ?";
            $params[] = $input['activity_type'];
        }
        
        if (isset($input['duration_minutes'])) {
            if ($input['duration_minutes'] <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid duration']);
                exit;
            }
            $updates[] = "duration_minutes = ?";
            $params[] = $input['duration_minutes'];
        }
        
        if (isset($input['description'])) {
            $updates[] = "description = ?";
            $params[] = $input['description'];
        }
        
        $params[] = $input['id']; // WHERE condition
        
        $query = "UPDATE activities SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        echo json_encode([
            'status' => 'success',
            'updated' => $stmt->rowCount()
        ]);
        break;

    // DELETE: Remove activity
    case 'DELETE':
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing activity ID']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM activities WHERE id=?");
        $stmt->execute([$input['id']]);
        echo json_encode([
            'status' => 'success',
            'deleted' => $stmt->rowCount()
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed'
        ]);
}