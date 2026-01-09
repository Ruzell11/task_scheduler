<?php

header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../controller/AuthController.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    $authController = new AuthController();
    $result = $authController->register($data);
    
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}