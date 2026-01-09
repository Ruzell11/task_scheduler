<?php
// File: api/tasks/get.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID required']);
    exit;
}

require_once '../../config/database.php';

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("
        SELECT * FROM tasks 
        WHERE id = :id AND user_id = :user_id
    ");
    
    $stmt->execute([
        ':id' => $_GET['id'],
        ':user_id' => $_SESSION['user_id']
    ]);
    
    $task = $stmt->fetch();
    
    if ($task) {
        echo json_encode(['success' => true, 'data' => $task]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Task not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>