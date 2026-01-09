<?php
// File: includes/header.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

// Get user info with defaults
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Daily Planner - Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.2.0/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    
    <!-- jQuery - Load in header for logout to work -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <style>
        .mood-badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; }
        .priority-high { background: #dc3545; color: white; }
        .priority-medium { background: #ffc107; color: black; }
        .priority-low { background: #28a745; color: white; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <span class="nav-link">Welcome, <?php echo htmlspecialchars($user_name); ?>!</span>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" id="logoutBtn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="/views/dashboard/index.php" class="brand-link">
            <i class="fas fa-brain brand-image"></i>
            <span class="brand-text font-weight-light">AI Daily Planner</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="/views/dashboard/index.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/views/tasks/index.php" class="nav-link">
                            <i class="nav-icon fas fa-tasks"></i>
                            <p>My Tasks</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/views/moods/log.php" class="nav-link">
                            <i class="nav-icon fas fa-smile"></i>
                            <p>Log Mood</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/views/moods/history.php" class="nav-link">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>Mood History</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/views/schedule/ai-schedule.php" class="nav-link">
                            <i class="nav-icon fas fa-magic"></i>
                            <p>AI Schedule</p>
                        </a>
                    </li>
                    <!-- <li class="nav-item">
                        <a href="/views/schedule/suggestions.php" class="nav-link">
                            <i class="nav-icon fas fa-lightbulb"></i>
                            <p>Suggestions</p>
                        </a>
                    </li> -->
                </ul>
            </nav>
        </div>
    </aside>

    <script>
    // Logout script - placed in header so it works immediately
    $(document).ready(function() {
        $('#logoutBtn').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to logout?')) {
                return;
            }
            
            $.ajax({
                url: '/api/auth/logout.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    window.location.href = '/views/auth/login.php';
                },
                error: function(xhr, status, error) {
                    console.error('Logout error:', error);
                    // Redirect anyway
                    window.location.href = '/views/auth/login.php';
                }
            });
        });
    });
    </script>