<?php


header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../models/MoodLog.php';
require_once '../../includes/session.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $database = new Database();
    $db = $database->getConnection();
    $moodLog = new MoodLog($db);
    
    $currentMood = $moodLog->getCurrentMood(getCurrentUserId());
    $recentMoods = $moodLog->getRecentMoods(getCurrentUserId(), 10);
    
    if (!$currentMood) {
        echo json_encode([
            'success' => false,
            'message' => 'No mood data available. Please log your mood first.'
        ]);
        exit();
    }
    
    // Calculate average energy
    $totalEnergy = 0;
    foreach ($recentMoods as $mood) {
        $totalEnergy += $mood['energy'];
    }
    $avgEnergy = count($recentMoods) > 0 ? round($totalEnergy / count($recentMoods), 1) : 5;
    
    echo json_encode([
        'success' => true,
        'current_mood' => $currentMood['mood'],
        'current_energy' => $currentMood['energy'],
        'average_energy' => $avgEnergy,
        'analysis' => $currentMood['ai_analysis'],
        'recommendations' => $currentMood['recommendations'],
        'mood_history' => $recentMoods
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
