<?php
// File: api/tasks/create.php
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

// Validate required fields
$required = ['title', 'priority', 'energy_level_required', 'estimated_time'];
$missing = array_filter($required, fn($field) => empty($data[$field]));

if (!empty($missing)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        INSERT INTO tasks (user_id, title, description, priority, estimated_time, 
                          energy_level_required, category, deadline, status)
        VALUES (:user_id, :title, :description, :priority, :estimated_time,
                :energy, :category, :deadline, 'pending')
    ");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':title' => $data['title'],
        ':description' => $data['description'] ?? '',
        ':priority' => $data['priority'],
        ':estimated_time' => $data['estimated_time'],
        ':energy' => $data['energy_level_required'],
        ':category' => $data['category'] ?? null,
        ':deadline' => $data['deadline'] ?? null
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Task created successfully',
        'task_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>