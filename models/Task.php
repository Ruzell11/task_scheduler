<?php

class Task {
    private $conn;
    private $table = 'tasks';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($userId, $data) {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, title, description, priority, estimated_duration, deadline, mood_tag) 
                  VALUES (:user_id, :title, :description, :priority, :estimated_duration, :deadline, :mood_tag)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':estimated_duration', $data['estimated_duration']);
        $stmt->bindParam(':deadline', $data['deadline']);
        $stmt->bindParam(':mood_tag', $data['mood_tag']);

        return $stmt->execute();
    }

    public function getTasksByUser($userId) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = :user_id ORDER BY scheduled_time ASC, priority DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTaskById($id, $userId) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND user_id = :user_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $userId, $data) {
        $query = "UPDATE " . $this->table . " SET 
                  title = :title,
                  description = :description,
                  priority = :priority,
                  estimated_duration = :estimated_duration,
                  deadline = :deadline,
                  status = :status,
                  scheduled_time = :scheduled_time,
                  mood_tag = :mood_tag
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':estimated_duration', $data['estimated_duration']);
        $stmt->bindParam(':deadline', $data['deadline']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':scheduled_time', $data['scheduled_time']);
        $stmt->bindParam(':mood_tag', $data['mood_tag']);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }

    public function delete($id, $userId) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }

    public function getPendingTasks($userId) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = :user_id AND status = 'pending' 
                  ORDER BY priority DESC, deadline ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}