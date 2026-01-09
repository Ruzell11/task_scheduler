<?php
// File: api/schedule/update.php
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

if (!isset($data['schedule_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: schedule_id and status']);
    exit;
}

$validStatuses = ['scheduled', 'in_progress', 'completed', 'skipped'];
if (!in_array($data['status'], $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Update schedule status
    $stmt = $pdo->prepare("
        UPDATE schedules
        SET status = :status
        WHERE id = :schedule_id
        AND user_id = :user_id
    ");
    
    $stmt->execute([
        ':status' => $data['status'],
        ':schedule_id' => $data['schedule_id'],
        ':user_id' => $_SESSION['user_id']
    ]);
    
    // If completed, update the task status too
    if ($data['status'] === 'completed') {
        $taskStmt = $pdo->prepare("
            UPDATE tasks t
            JOIN schedules s ON t.id = s.task_id
            SET t.status = 'completed'
            WHERE s.id = :schedule_id
            AND s.user_id = :user_id
        ");
        $taskStmt->execute([
            ':schedule_id' => $data['schedule_id'],
            ':user_id' => $_SESSION['user_id']
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Schedule updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>