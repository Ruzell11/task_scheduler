<?php
// File: api/moods/log.php
header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Get JSON input
    $input = file_get_contents('php://input');
    
    // Check if input is empty
    if (empty($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No data provided']);
        exit;
    }
    
    $data = json_decode($input, true);
    
    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    // Validate required fields
    if (!isset($data['mood_type']) || !isset($data['energy_level'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: mood_type and energy_level'
        ]);
        exit;
    }

    // Validate mood_type
    $valid_moods = ['energetic', 'focused', 'creative', 'tired', 'stressed', 'neutral'];
    if (!in_array($data['mood_type'], $valid_moods)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid mood type. Must be one of: ' . implode(', ', $valid_moods)
        ]);
        exit;
    }

    // Validate energy_level
    $energy_level = intval($data['energy_level']);
    if ($energy_level < 1 || $energy_level > 10) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Energy level must be between 1 and 10'
        ]);
        exit;
    }

    try {
        $pdo = getDBConnection();
        
        // Insert mood log
        $stmt = $pdo->prepare("
            INSERT INTO moods (user_id, mood_type, energy_level, notes, recorded_at) 
            VALUES (:user_id, :mood_type, :energy_level, :notes, NOW())
        ");
        
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':mood_type' => $data['mood_type'],
            ':energy_level' => $energy_level,
            ':notes' => $data['notes'] ?? ''
        ]);

        // Get AI analysis and recommendations
        $analysis = generateMoodAnalysis($data['mood_type'], $energy_level);
        $recommendations = generateRecommendations($data['mood_type'], $energy_level);

        echo json_encode([
            'success' => true,
            'message' => 'Mood logged successfully',
            'mood_id' => $pdo->lastInsertId(),
            'analysis' => $analysis,
            'recommendations' => $recommendations
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }

} elseif ($method === 'GET') {
    // Get current mood
    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            SELECT * FROM moods 
            WHERE user_id = :user_id 
            ORDER BY recorded_at DESC 
            LIMIT 1
        ");
        
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $mood = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $mood ?: null
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

// Helper functions
function generateMoodAnalysis($mood, $energy) {
    $analyses = [
        'energetic' => "You're feeling energetic with high energy levels! This is a great time for challenging tasks and creative work.",
        'focused' => "You're in a focused state. Perfect for deep work, problem-solving, and tasks requiring concentration.",
        'creative' => "Your creative energy is flowing! Great time for brainstorming, design work, and innovative thinking.",
        'tired' => "You're feeling tired. Consider taking a break, or tackle simple, routine tasks that don't require much energy.",
        'stressed' => "You're feeling stressed. Try breaking tasks into smaller chunks and taking regular breaks. Consider some relaxation techniques.",
        'neutral' => "You're in a neutral state. Good for a balanced mix of tasks - both challenging and routine work."
    ];

    $energy_note = '';
    if ($energy >= 8) {
        $energy_note = " Your high energy level ($energy/10) means you can tackle demanding tasks effectively.";
    } elseif ($energy >= 5) {
        $energy_note = " Your moderate energy level ($energy/10) is good for a mix of tasks.";
    } else {
        $energy_note = " Your low energy level ($energy/10) suggests focusing on lighter tasks.";
    }

    return ($analyses[$mood] ?? 'Mood logged successfully.') . $energy_note;
}

function generateRecommendations($mood, $energy) {
    $recommendations = [];

    // Based on mood
    switch ($mood) {
        case 'energetic':
            $recommendations[] = "Tackle your most challenging or creative tasks first";
            $recommendations[] = "Schedule complex problem-solving work";
            $recommendations[] = "Great time for meetings and collaboration";
            break;
        case 'focused':
            $recommendations[] = "Deep work sessions - minimize distractions";
            $recommendations[] = "Complex analytical tasks";
            $recommendations[] = "Learning new skills or concepts";
            break;
        case 'creative':
            $recommendations[] = "Brainstorming and ideation sessions";
            $recommendations[] = "Design and creative projects";
            $recommendations[] = "Strategic planning";
            break;
        case 'tired':
            $recommendations[] = "Administrative tasks and email";
            $recommendations[] = "Routine work that doesn't require much thought";
            $recommendations[] = "Consider taking a short break";
            break;
        case 'stressed':
            $recommendations[] = "Break large tasks into smaller chunks";
            $recommendations[] = "Focus on one thing at a time";
            $recommendations[] = "Take short breaks between tasks";
            break;
        case 'neutral':
            $recommendations[] = "Mix of challenging and routine tasks";
            $recommendations[] = "Good time for planning and organization";
            $recommendations[] = "Balanced approach to your task list";
            break;
    }

    // Based on energy
    if ($energy >= 8) {
        $recommendations[] = "Use this high energy wisely on priority tasks";
    } elseif ($energy < 4) {
        $recommendations[] = "Consider a 10-15 minute break to recharge";
    }

    return $recommendations;
}
?>