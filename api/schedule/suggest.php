<?php


header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../models/Task.php';
require_once '../../models/MoodLog.php';
require_once '../../controllers/AIController.php';
require_once '../../includes/session.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $aiController = new AIController();
    $result = $aiController->getSuggestions(getCurrentUserId());
    
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
