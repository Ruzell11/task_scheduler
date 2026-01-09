<?php
// File: api/tasks/update.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$input = file_get_contents('php://input');
if (empty($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No data provided']);
    exit;
}

$data = json_decode($input, true);

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID required']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        UPDATE tasks 
        SET title = :title,
            description = :description,
            priority = :priority,
            estimated_time = :estimated_time,
            energy_level_required = :energy,
            category = :category,
            deadline = :deadline
        WHERE id = :id AND user_id = :user_id
    ");
    
    $result = $stmt->execute([
        ':id' => $data['id'],
        ':user_id' => $_SESSION['user_id'],
        ':title' => $data['title'],
        ':description' => $data['description'] ?? '',
        ':priority' => $data['priority'],
        ':estimated_time' => $data['estimated_time'],
        ':energy' => $data['energy_level_required'],
        ':category' => $data['category'] ?? null,
        ':deadline' => $data['deadline'] ?? null
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update task']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>