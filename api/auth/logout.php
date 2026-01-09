<?php


header('Content-Type: application/json');
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../controllers/AuthController.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController = new AuthController();
    $result = $authController->logout();
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
