<?php
// File: api/moods/history.php
header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Get number of days from query parameter (default 30)
    $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
    
    // Validate days parameter
    if ($days < 1 || $days > 365) {
        $days = 30;
    }
    
    // Get mood history
    $stmt = $pdo->prepare("
        SELECT 
            id,
            user_id,
            mood_type,
            energy_level,
            notes,
            recorded_at
        FROM moods 
        WHERE user_id = :user_id 
        AND recorded_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        ORDER BY recorded_at DESC
    ");
    
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':days', $days, PDO::PARAM_INT);
    $stmt->execute();
    
    $moods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $stats = calculateStats($moods);
    
    echo json_encode([
        'success' => true,
        'data' => $moods,
        'stats' => $stats,
        'count' => count($moods),
        'period_days' => $days
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

// Calculate statistics
function calculateStats($moods) {
    if (empty($moods)) {
        return [
            'total_entries' => 0,
            'avg_energy' => 0,
            'most_common_mood' => null,
            'streak_days' => 0,
            'mood_distribution' => []
        ];
    }
    
    // Total entries
    $total = count($moods);
    
    // Average energy
    $totalEnergy = array_reduce($moods, function($sum, $mood) {
        return $sum + intval($mood['energy_level']);
    }, 0);
    $avgEnergy = $totalEnergy / $total;
    
    // Mood distribution
    $moodCounts = [];
    foreach ($moods as $mood) {
        $type = $mood['mood_type'];
        $moodCounts[$type] = ($moodCounts[$type] ?? 0) + 1;
    }
    
    // Most common mood
    $mostCommonMood = array_keys($moodCounts, max($moodCounts))[0];
    
    // Calculate streak
    $streakDays = calculateStreak($moods);
    
    return [
        'total_entries' => $total,
        'avg_energy' => round($avgEnergy, 1),
        'most_common_mood' => $mostCommonMood,
        'streak_days' => $streakDays,
        'mood_distribution' => $moodCounts
    ];
}

// Calculate consecutive days streak
function calculateStreak($moods) {
    if (empty($moods)) return 0;
    
    // Get unique dates
    $dates = [];
    foreach ($moods as $mood) {
        $date = date('Y-m-d', strtotime($mood['recorded_at']));
        $dates[$date] = true;
    }
    
    $uniqueDates = array_keys($dates);
    sort($uniqueDates);
    $uniqueDates = array_reverse($uniqueDates);
    
    // Check if today is included
    $today = date('Y-m-d');
    if ($uniqueDates[0] !== $today) {
        return 0;
    }
    
    // Count consecutive days
    $streak = 1;
    for ($i = 0; $i < count($uniqueDates) - 1; $i++) {
        $current = strtotime($uniqueDates[$i]);
        $next = strtotime($uniqueDates[$i + 1]);
        $diffDays = ($current - $next) / (60 * 60 * 24);
        
        if ($diffDays == 1) {
            $streak++;
        } else {
            break;
        }
    }
    
    return $streak;
}
?>