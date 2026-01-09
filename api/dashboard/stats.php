<?php

header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../models/Task.php';
require_once '../../models/MoodLog.php';
require_once '../../includes/session.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $database = new Database();
    $db = $database->getConnection();
    
    $taskModel = new Task($db);
    $moodModel = new MoodLog($db);
    
    $userId = getCurrentUserId();
    
    // Get all tasks
    $allTasks = $taskModel->getTasksByUser($userId);
    
    // Count by status
    $pending = 0;
    $inProgress = 0;
    $completed = 0;
    
    foreach ($allTasks as $task) {
        switch ($task['status']) {
            case 'pending':
                $pending++;
                break;
            case 'in-progress':
                $inProgress++;
                break;
            case 'completed':
                $completed++;
                break;
        }
    }
    
    // Get current mood
    $currentMood = $moodModel->getCurrentMood($userId);
    
    // Get recent moods for trend
    $recentMoods = $moodModel->getRecentMoods($userId, 7);
    
    $moodTrend = [];
    foreach ($recentMoods as $mood) {
        $moodTrend[] = [
            'mood' => $mood['mood'],
            'energy' => $mood['energy'],
            'date' => date('Y-m-d', strtotime($mood['timestamp']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_tasks' => count($allTasks),
            'pending_tasks' => $pending,
            'in_progress_tasks' => $inProgress,
            'completed_tasks' => $completed,
            'completion_rate' => count($allTasks) > 0 ? round(($completed / count($allTasks)) * 100) : 0
        ],
        'current_mood' => $currentMood,
        'mood_trend' => $moodTrend
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}