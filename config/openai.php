<?php
class OpenAI {
    private $api_key = "YOUR_OPENAI_API_KEY";
    private $api_url = "https://api.openai.com/v1/chat/completions";

    public function analyzeMood($moodData) {
        $prompt = "Based on the following mood data, provide analysis and task recommendations:
                   Mood: {$moodData['mood']}, Energy: {$moodData['energy']}/10.
                   Respond in JSON format with 'analysis' and 'recommendation' fields.";

        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a productivity assistant analyzing user mood for task scheduling.'
                ],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 150
        ];

        return $this->makeRequest($data);
    }

    public function optimizeSchedule($tasks, $moodHistory) {
        $taskList = json_encode($tasks);
        $moodList = json_encode($moodHistory);
        
        $prompt = "Given these tasks: {$taskList} and mood history: {$moodList}, 
                   create an optimized schedule. Return JSON with task IDs and suggested times.";

        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an AI scheduler that optimizes tasks based on user mood patterns.'
                ],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.8,
            'max_tokens' => 500
        ];

        return $this->makeRequest($data);
    }

    private function makeRequest($data) {
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            return $result['choices'][0]['message']['content'] ?? null;
        }
        return null;
    }
}