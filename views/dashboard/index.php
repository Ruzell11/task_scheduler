<?php
// File: views/dashboard/index.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

include '../../includes/header.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Stats Cards Row -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3 id="total-tasks">0</h3>
                            <p>Total Tasks</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3 id="completed-tasks">0</h3>
                            <p>Completed</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3 id="pending-tasks">0</h3>
                            <p>Pending</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3 id="current-mood">--</h3>
                            <p>Current Mood</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-smile"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="row">
                <!-- AI Recommendations -->
                <!-- <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-magic mr-1"></i>
                                AI Recommendations
                            </h3>
                        </div>
                        <div class="card-body" id="ai-recommendations">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Log your mood to get AI-powered task recommendations!
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- Today's Tasks -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-day mr-1"></i>
                                Today's Schedule
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-success" onclick="window.location.href='../schedule/ai-schedule.php'">
                                    <i class="fas fa-plus"></i> Generate AI Schedule
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="todays-tasks" style="max-height: 300px; overflow-y: auto;">
                                <div class="p-3 text-center text-muted">
                                    No tasks scheduled for today
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
const API_URL = '/api';

// Load dashboard data on page load
$(document).ready(function() {
    loadDashboardData();
});

// Load all dashboard data
function loadDashboardData() {
    loadTaskStats();
    loadCurrentMood();
    loadTodaysSchedule();
}

// Load task statistics
function loadTaskStats() {
    $.ajax({
        url: API_URL + '/tasks/list.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const tasks = response.data || [];
                const completed = tasks.filter(t => t.status === 'completed').length;
                const pending = tasks.filter(t => t.status === 'pending').length;

                $('#total-tasks').text(tasks.length);
                $('#completed-tasks').text(completed);
                $('#pending-tasks').text(pending);
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load task stats:', error);
        }
    });
}

// Load current mood
function loadCurrentMood() {
    $.ajax({
        url: API_URL + '/moods/log.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
                const mood = response.data.mood_type || '--';
                $('#current-mood').text(mood.charAt(0).toUpperCase() + mood.slice(1));
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load mood:', error);
        }
    });
}

// Load today's schedule
function loadTodaysSchedule() {
    $.ajax({
        url: API_URL + '/schedule/today.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.schedule && response.schedule.length > 0) {
                displayTodaysSchedule(response.schedule);
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load schedule:', error);
        }
    });
}

// Display today's schedule
function displayTodaysSchedule(tasks) {
    const container = $('#todays-tasks');
    
    if (tasks.length === 0) {
        container.html('<div class="p-3 text-center text-muted">No tasks scheduled for today</div>');
        return;
    }

    let html = '<ul class="todo-list" data-widget="todo-list">';
    
    tasks.forEach(function(task) {
        const time = new Date(task.scheduled_time).toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        const statusClass = task.status === 'completed' ? 'done' : '';
        const priorityBadge = getPriorityBadge(task.priority);
        
        html += `
            <li class="${statusClass}">
                <span class="text">${task.title}</span>
                <small class="badge badge-info">
                    <i class="fas fa-clock"></i> ${time}
                </small>
                <small>${priorityBadge}</small>
            </li>
        `;
    });
    
    html += '</ul>';
    container.html(html);
}

function getPriorityBadge(priority) {
    const badges = {
        urgent: '<span class="badge badge-danger">Urgent</span>',
        high: '<span class="badge badge-warning">High</span>',
        medium: '<span class="badge badge-info">Medium</span>',
        low: '<span class="badge badge-success">Low</span>'
    };
    return badges[priority] || '';
}
</script>

</body>
</html