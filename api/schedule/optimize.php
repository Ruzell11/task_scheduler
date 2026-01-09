<?php
// File: api/schedule/optimize.php
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
    
    // Step 1: Get current mood
    $moodStmt = $pdo->prepare("
        SELECT mood_type, energy_level, recorded_at
        FROM moods
        WHERE user_id = :user_id
        ORDER BY recorded_at DESC
        LIMIT 1
    ");
    $moodStmt->execute([':user_id' => $user_id]);
    $currentMood = $moodStmt->fetch();
    
    if (!$currentMood) {
        echo json_encode([
            'success' => false,
            'message' => 'Please log your mood first before generating a schedule.'
        ]);
        exit;
    }
    
    // Step 2: Get pending tasks
    $tasksStmt = $pdo->prepare("
        SELECT * FROM tasks
        WHERE user_id = :user_id
        AND status = 'pending'
        ORDER BY 
            CASE priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            deadline ASC
    ");
    $tasksStmt->execute([':user_id' => $user_id]);
    $tasks = $tasksStmt->fetchAll();
    
    if (empty($tasks)) {
        echo json_encode([
            'success' => false,
            'message' => 'No pending tasks found. Please add some tasks first.'
        ]);
        exit;
    }
    
    // Step 3: Generate optimized schedule
    $schedule = generateOptimizedSchedule($tasks, $currentMood, $pdo, $user_id);
    
    // Step 4: Save schedule to database
    $saved = saveSchedule($schedule, $pdo, $user_id);
    
    if ($saved) {
        echo json_encode([
            'success' => true,
            'message' => 'Schedule generated successfully',
            'schedule' => $schedule,
            'mood' => $currentMood
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save schedule'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

function generateOptimizedSchedule($tasks, $mood, $pdo, $user_id) {
    $schedule = [];
    $currentTime = new DateTime();
    $currentTime->setTime($currentTime->format('H'), 0, 0); // Round to hour
    
    // If it's past 6 PM, start schedule for tomorrow at 8 AM
    if ($currentTime->format('H') >= 18) {
        $currentTime->modify('+1 day');
        $currentTime->setTime(8, 0, 0);
    } else {
        // Start from next hour
        $currentTime->modify('+1 hour');
    }
    
    $energyLevel = intval($mood['energy_level']);
    $moodType = $mood['mood_type'];
    
    // Sort tasks based on mood and energy
    $sortedTasks = prioritizeTasksByMood($tasks, $moodType, $energyLevel);
    
    foreach ($sortedTasks as $task) {
        $confidence = calculateConfidence($task, $moodType, $energyLevel);
        
        $schedule[] = [
            'id' => $task['id'],
            'title' => $task['title'],
            'description' => $task['description'],
            'priority' => $task['priority'],
            'energy_level_required' => $task['energy_level_required'],
            'estimated_time' => $task['estimated_time'],
            'duration' => $task['estimated_time'],
            'scheduled_time' => $currentTime->format('Y-m-d H:i:s'),
            'ai_confidence_score' => $confidence,
            'status' => 'scheduled'
        ];
        
        // Add task duration plus 10 min break
        $currentTime->modify('+' . ($task['estimated_time'] + 10) . ' minutes');
        
        // Add lunch break if crossing 12 PM
        if ($currentTime->format('H') >= 12 && $currentTime->format('H') < 13) {
            $currentTime->setTime(13, 0, 0);
        }
        
        // Stop scheduling after 6 PM
        if ($currentTime->format('H') >= 18) {
            break;
        }
    }
    
    return $schedule;
}

function prioritizeTasksByMood($tasks, $moodType, $energyLevel) {
    // Define mood-task affinity
    $moodPreferences = [
        'energetic' => ['high', 'medium'],
        'focused' => ['high', 'medium'],
        'creative' => ['high', 'medium'],
        'tired' => ['low'],
        'stressed' => ['low', 'medium'],
        'neutral' => ['medium', 'low']
    ];
    
    $preferredEnergy = $moodPreferences[$moodType] ?? ['medium'];
    
    // Score each task
    $scored = array_map(function($task) use ($preferredEnergy, $energyLevel) {
        $score = 0;
        
        // Energy match
        if (in_array($task['energy_level_required'], $preferredEnergy)) {
            $score += 10;
        }
        
        // Priority boost
        $priorityScores = ['urgent' => 15, 'high' => 10, 'medium' => 5, 'low' => 0];
        $score += $priorityScores[$task['priority']] ?? 0;
        
        // Deadline proximity
        if ($task['deadline']) {
            $daysUntil = (strtotime($task['deadline']) - time()) / 86400;
            if ($daysUntil < 1) $score += 20;
            elseif ($daysUntil < 3) $score += 10;
            elseif ($daysUntil < 7) $score += 5;
        }
        
        $task['_score'] = $score;
        return $task;
    }, $tasks);
    
    // Sort by score descending
    usort($scored, function($a, $b) {
        return $b['_score'] - $a['_score'];
    });
    
    return $scored;
}

function calculateConfidence($task, $moodType, $energyLevel) {
    $confidence = 0.5; // Base confidence
    
    // Mood-energy alignment
    $energyRequired = $task['energy_level_required'];
    
    if ($energyLevel >= 7 && $energyRequired == 'high') {
        $confidence += 0.3;
    } elseif ($energyLevel >= 4 && $energyLevel <= 6 && $energyRequired == 'medium') {
        $confidence += 0.2;
    } elseif ($energyLevel <= 4 && $energyRequired == 'low') {
        $confidence += 0.25;
    }
    
    // Mood type bonus
    if (in_array($moodType, ['energetic', 'focused']) && $energyRequired == 'high') {
        $confidence += 0.15;
    }
    if ($moodType == 'tired' && $energyRequired == 'low') {
        $confidence += 0.2;
    }
    
    // Priority factor
    if ($task['priority'] == 'urgent') {
        $confidence += 0.1;
    }
    
    return min(1.0, max(0.1, $confidence));
}

function saveSchedule($schedule, $pdo, $user_id) {
    try {
        // Clear today's existing schedule
        $clearStmt = $pdo->prepare("
            DELETE FROM schedules
            WHERE user_id = :user_id
            AND DATE(scheduled_time) = CURDATE()
        ");
        $clearStmt->execute([':user_id' => $user_id]);
        
        // Insert new schedule
        $insertStmt = $pdo->prepare("
            INSERT INTO schedules 
            (user_id, task_id, scheduled_time, duration, status, ai_confidence_score)
            VALUES (:user_id, :task_id, :scheduled_time, :duration, :status, :confidence)
        ");
        
        foreach ($schedule as $item) {
            $insertStmt->execute([
                ':user_id' => $user_id,
                ':task_id' => $item['id'],
                ':scheduled_time' => $item['scheduled_time'],
                ':duration' => $item['duration'],
                ':status' => 'scheduled',
                ':confidence' => $item['ai_confidence_score']
            ]);
        }
        
        // Update task statuses to 'scheduled'
        $taskIds = array_column($schedule, 'id');
        $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
        $updateStmt = $pdo->prepare("
            UPDATE tasks 
            SET status = 'scheduled'
            WHERE id IN ($placeholders)
        ");
        $updateStmt->execute($taskIds);
        
        return true;
    } catch (Exception $e) {
        error_log("Error saving schedule: " . $e->getMessage());
        return false;
    }
}
?>