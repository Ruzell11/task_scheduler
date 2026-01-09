<?php
// File: api/tasks/list.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Get optional status filter
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $sql = "SELECT * FROM tasks WHERE user_id = :user_id";
    
    if ($status && in_array($status, ['pending', 'scheduled', 'completed', 'cancelled'])) {
        $sql .= " AND status = :status";
    }
    
    $sql .= " ORDER BY 
        CASE priority
            WHEN 'urgent' THEN 1
            WHEN 'high' THEN 2
            WHEN 'medium' THEN 3
            WHEN 'low' THEN 4
        END,
        deadline ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    
    if ($status) {
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $tasks = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $tasks,
        'count' => count($tasks)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>