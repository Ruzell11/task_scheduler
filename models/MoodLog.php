<?php


class MoodLog {
    private $conn;
    private $table = 'mood_logs';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($userId, $data) {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, mood, energy, context, ai_analysis, recommendations) 
                  VALUES (:user_id, :mood, :energy, :context, :ai_analysis, :recommendations)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':mood', $data['mood']);
        $stmt->bindParam(':energy', $data['energy']);
        $stmt->bindParam(':context', $data['context']);
        $stmt->bindParam(':ai_analysis', $data['ai_analysis']);
        $stmt->bindParam(':recommendations', $data['recommendations']);

        return $stmt->execute();
    }

    public function getRecentMoods($userId, $limit = 10) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  ORDER BY timestamp DESC 
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCurrentMood($userId) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  ORDER BY timestamp DESC 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getMoodHistory($userId, $days = 7) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id 
                  AND timestamp >= DATE_SUB(NOW(), INTERVAL :days DAY)
                  ORDER BY timestamp DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}