<?php


class AIController {
    private $db;
    private $task;
    private $mood;
    private $openai;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->task = new Task($this->db);
        $this->mood = new MoodLog($this->db);
        $this->openai = new OpenAI();
    }

    public function optimizeSchedule($userId) {
        $tasks = $this->task->getPendingTasks($userId);
        $moodHistory = $this->mood->getMoodHistory($userId, 7);

        if (empty($tasks)) {
            return ['success' => false, 'message' => 'No pending tasks to schedule'];
        }

        // Use OpenAI to optimize schedule
        $aiResponse = $this->openai->optimizeSchedule($tasks, $moodHistory);
        
        if ($aiResponse) {
            // Parse AI response and update task schedules
            // This is a simplified version
            $optimized = json_decode($aiResponse, true);
            return ['success' => true, 'schedule' => $optimized, 'tasks' => $tasks];
        }

        return ['success' => false, 'message' => 'AI scheduling failed'];
    }

    public function getSuggestions($userId) {
        $currentMood = $this->mood->getCurrentMood($userId);
        $tasks = $this->task->getPendingTasks($userId);

        if (!$currentMood) {
            return ['success' => false, 'message' => 'Please log your current mood first'];
        }

        // Simple suggestion algorithm based on mood and energy
        $suggestions = [];
        $energy = $currentMood['energy'];

        foreach ($tasks as $task) {
            $score = 0;
            
            // Match high energy with high priority tasks
            if ($energy >= 7 && $task['priority'] === 'high') {
                $score += 3;
            }
            
            // Match low energy with low priority tasks
            if ($energy <= 4 && $task['priority'] === 'low') {
                $score += 2;
            }

            $task['suggestion_score'] = $score;
            $suggestions[] = $task;
        }

        usort($suggestions, function($a, $b) {
            return $b['suggestion_score'] - $a['suggestion_score'];
        });

        return ['success' => true, 'suggestions' => array_slice($suggestions, 0, 5)];
    }
}