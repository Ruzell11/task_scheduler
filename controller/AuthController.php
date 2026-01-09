<?php
// File: controller/AuthController.php

class AuthController {
    private $db;
    
    public function __construct() {
        // Include required files
        require_once __DIR__ . '/../config/database.php';
        
        // Get database connection
        $this->db = getDBConnection();
    }
    
    public function register($data) {
        try {
            // Validate required fields
            if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
                return [
                    'success' => false,
                    'message' => 'All fields are required'
                ];
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Invalid email format'
                ];
            }
            
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Email already registered'
                ];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            
            // Insert user
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([
                $data['name'],
                $data['email'],
                $hashedPassword
            ])) {
                $userId = $this->db->lastInsertId();
                
                // Start session and set user data
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $data['name'];
                $_SESSION['user_email'] = $data['email'];
                
                return [
                    'success' => true,
                    'message' => 'Registration successful',
                    'user' => [
                        'id' => $userId,
                        'name' => $data['name'],
                        'email' => $data['email']
                    ]
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Registration failed'
            ];
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
    
    // FIXED: Accept $data array parameter
    public function login($data) {
        try {
            // Validate required fields
            if (empty($data['email']) || empty($data['password'])) {
                return [
                    'success' => false,
                    'message' => 'Email and password are required'
                ];
            }
            
            // Get user by email
            $stmt = $this->db->prepare("
                SELECT id, name, email, password 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify user exists and password is correct
            if ($user && password_verify($data['password'], $user['password'])) {
                // Start session and set user data
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email']
                    ]
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
            
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error occurred'
            ];
        }
    }
    
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Destroy all session data
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Logout successful'
        ];
    }
    
    public function checkAuth() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            return [
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'name' => $_SESSION['user_name'],
                    'email' => $_SESSION['user_email']
                ]
            ];
        }
        
        return [
            'authenticated' => false
        ];
    }
}
?>