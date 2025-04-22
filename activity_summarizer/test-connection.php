<?php
require 'config.php';

try {
    // Test query: Fetch all activities
    $stmt = $db->query("SELECT * FROM activities");
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection is working!',
        'data' => $activities
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Query failed!',
        'error' => $e->getMessage()
    ]);
}