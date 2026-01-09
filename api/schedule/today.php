<?php
// File: api/schedule/today.php
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
    
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.scheduled_time,
            s.duration,
            s.status,
            s.ai_confidence_score,
            t.id as task_id,
            t.title,
            t.description,
            t.priority,
            t.energy_level_required
        FROM schedules s
        JOIN tasks t ON s.task_id = t.id
        WHERE s.user_id = :user_id
        AND DATE(s.scheduled_time) = CURDATE()
        ORDER BY s.scheduled_time ASC
    ");
    
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $schedule = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'schedule' => $schedule,
        'count' => count($schedule)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>