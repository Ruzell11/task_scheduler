<?php


class TaskController {
    private $db;
    private $task;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->task = new Task($this->db);
    }

    public function createTask($userId, $data) {
        $required = ['title', 'priority'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => ucfirst($field) . ' is required'];
            }
        }

        if ($this->task->create($userId, $data)) {
            return ['success' => true, 'message' => 'Task created successfully'];
        }
        return ['success' => false, 'message' => 'Failed to create task'];
    }

    public function updateTask($id, $userId, $data) {
        if ($this->task->update($id, $userId, $data)) {
            return ['success' => true, 'message' => 'Task updated successfully'];
        }
        return ['success' => false, 'message' => 'Failed to update task'];
    }

    public function deleteTask($id, $userId) {
        if ($this->task->delete($id, $userId)) {
            return ['success' => true, 'message' => 'Task deleted successfully'];
        }
        return ['success' => false, 'message' => 'Failed to delete task'];
    }

    public function getAllTasks($userId) {
        return $this->task->getTasksByUser($userId);
    }
}