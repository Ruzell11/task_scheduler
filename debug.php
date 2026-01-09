<?php
// File: debug.php (place in root directory)
session_start();

echo "<h2>Session Debug Info</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user exists in database
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        echo "<h2>User in Database:</h2>";
        if ($user) {
            echo "<pre>";
            print_r($user);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'><strong>ERROR: User ID {$_SESSION['user_id']} does NOT exist in database!</strong></p>";
            
            // Show all users
            $allUsers = $pdo->query("SELECT id, name, email FROM users")->fetchAll();
            echo "<h3>Available Users:</h3>";
            echo "<pre>";
            print_r($allUsers);
            echo "</pre>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>No user_id in session!</p>";
}
?>