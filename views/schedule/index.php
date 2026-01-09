<?php
// File: views/schedule/ai-schedule.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

include '../../includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">AI-Optimized Schedule</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../dashboard/index.php">Home</a></li>
                        <li class="breadcrumb-item active">AI Schedule</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Instructions Card -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle"></i> How It Works</h3>
                        </div>
                        <div class="card-body">
                            <ol class="mb-0">
                                <li>Make sure you've logged your current mood and energy level</li>
                                <li>Add tasks with priorities and energy requirements</li>
                                <li>Click "Generate AI Schedule" below</li>
                                <li>The AI will create an optimized schedule based on your current state</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generate Button -->
            <div class="row mb-3">
                <div class="col-md-12 text-center">
                    <button class="btn btn-primary btn-lg" id="optimizeBtn">
                        <i class="fas fa-magic"></i> Generate AI Schedule
                    </button>
                </div>
            </div>

            <!-- Schedule Display -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Your Optimized Schedule</h3>
                        </div>
                        <div class="card-body" id="scheduleContent">
                            <p class="text-center text-muted py-5">
                                <i class="fas fa-robot fa-3x mb-3"></i><br>
                                Click "Generate AI Schedule" to get AI-powered task scheduling<br>
                                based on your mood and energy patterns.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Load existing schedule if available
    loadExistingSchedule();

    $('#optimizeBtn').on('click', function() {
        generateSchedule();
    });
});

function generateSchedule() {
    const btn = $('#optimizeBtn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating Schedule...');
    
    $.ajax({
        url: '/api/schedule/optimize.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displaySchedule(response.schedule);
                $(document).Toasts('create', {
                    class: 'bg-success',
                    title: 'Success',
                    body: 'AI schedule generated successfully!'
                });
            } else {
                $('#scheduleContent').html(`
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> ${response.message}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            $('#scheduleContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> Failed to generate schedule. Please try again.
                </div>
            `);
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-magic"></i> Generate AI Schedule');
        }
    });
}

function loadExistingSchedule() {
    $.ajax({
        url: '/api/schedule/today.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.schedule.length > 0) {
                displaySchedule(response.schedule);
            }
        }
    });
}

function displaySchedule(schedule) {
    if (!schedule || schedule.length === 0) {
        $('#scheduleContent').html(`
            <p class="text-center text-muted">No scheduled tasks yet.</p>
        `);
        return;
    }

    let html = '<div class="timeline">';
    
    schedule.forEach(task => {
        const time = task.scheduled_time ? new Date(task.scheduled_time).toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit' 
        }) : 'Not scheduled';
        
        const priorityBadge = getPriorityBadge(task.priority);
        const statusBadge = getStatusBadge(task.status);
        const confidenceColor = getConfidenceColor(task.ai_confidence_score);
        
        html += `
            <div class="time-label">
                <span class="bg-primary">${time}</span>
            </div>
            <div>
                <i class="fas fa-tasks bg-${confidenceColor}"></i>
                <div class="timeline-item">
                    <span class="time"><i class="fas fa-clock"></i> ${task.duration || 30} minutes</span>
                    <h3 class="timeline-header">
                        ${task.title}
                        ${statusBadge}
                    </h3>
                    <div class="timeline-body">
                        <p>${task.description || 'No description provided'}</p>
                        <div class="mb-2">
                            ${priorityBadge}
                            <span class="badge badge-info">
                                <i class="fas fa-bolt"></i> ${task.energy_level_required} energy
                            </span>
                            ${task.ai_confidence_score ? `
                                <span class="badge badge-${confidenceColor}">
                                    <i class="fas fa-brain"></i> ${Math.round(task.ai_confidence_score * 100)}% AI confidence
                                </span>
                            ` : ''}
                        </div>
                    </div>
                    <div class="timeline-footer">
                        ${getActionButtons(task)}
                    </div>
                </div>
            </div>
        `;
    });
    
    html += `
        <div>
            <i class="fas fa-clock bg-gray"></i>
        </div>
    </div>`;
    
    $('#scheduleContent').html(html);
}

function getPriorityBadge(priority) {
    const badges = {
        urgent: '<span class="badge badge-danger"><i class="fas fa-exclamation-circle"></i> Urgent</span>',
        high: '<span class="badge badge-warning"><i class="fas fa-arrow-up"></i> High</span>',
        medium: '<span class="badge badge-info"><i class="fas fa-minus"></i> Medium</span>',
        low: '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Low</span>'
    };
    return badges[priority] || badges.medium;
}

function getStatusBadge(status) {
    const badges = {
        scheduled: '<span class="badge badge-secondary">Scheduled</span>',
        in_progress: '<span class="badge badge-primary">In Progress</span>',
        completed: '<span class="badge badge-success">Completed</span>',
        skipped: '<span class="badge badge-warning">Skipped</span>'
    };
    return badges[status] || '';
}

function getConfidenceColor(score) {
    if (!score) return 'secondary';
    if (score >= 0.8) return 'success';
    if (score >= 0.6) return 'info';
    if (score >= 0.4) return 'warning';
    return 'danger';
}

function getActionButtons(task) {
    if (task.status === 'completed') {
        return '<span class="text-success"><i class="fas fa-check-circle"></i> Completed</span>';
    }
    
    if (task.status === 'skipped') {
        return '<span class="text-warning"><i class="fas fa-forward"></i> Skipped</span>';
    }
    
    return `
        <button class="btn btn-sm btn-primary" onclick="updateTaskStatus(${task.id}, 'in_progress')">
            <i class="fas fa-play"></i> Start
        </button>
        <button class="btn btn-sm btn-success" onclick="updateTaskStatus(${task.id}, 'completed')">
            <i class="fas fa-check"></i> Complete
        </button>
        <button class="btn btn-sm btn-warning" onclick="updateTaskStatus(${task.id}, 'skipped')">
            <i class="fas fa-forward"></i> Skip
        </button>
    `;
}

function updateTaskStatus(scheduleId, status) {
    $.ajax({
        url: '/api/schedule/update.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            schedule_id: scheduleId,
            status: status
        }),
        success: function(response) {
            if (response.success) {
                loadExistingSchedule();
                $(document).Toasts('create', {
                    class: 'bg-success',
                    title: 'Updated',
                    body: `Task ${status.replace('_', ' ')}!`
                });
            } else {
                alert('Failed to update task status');
            }
        },
        error: function() {
            alert('Error updating task status');
        }
    });
}
</script>

</body>
</html>