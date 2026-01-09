<?php
// File: views/tasks/index.php
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
                    <h1 class="m-0">My Tasks</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../dashboard/index.php">Home</a></li>
                        <li class="breadcrumb-item active">My Tasks</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Add Task Button -->
            <div class="row mb-3">
                <div class="col-12">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#taskModal" onclick="resetForm()">
                        <i class="fas fa-plus"></i> Add New Task
                    </button>
                </div>
            </div>

            <!-- Filter -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <select class="form-control" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <!-- Tasks Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Task List</h3>
                        </div>
                        <div class="card-body">
                            <table id="tasksTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Priority</th>
                                        <th>Energy</th>
                                        <th>Time (min)</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tasksTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<!-- Add/Edit Task Modal -->
<div class="modal fade" id="taskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Add New Task</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="taskForm">
                <div class="modal-body">
                    <input type="hidden" id="task_id">
                    
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" class="form-control" id="title" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" id="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Priority *</label>
                                <select class="form-control" id="priority" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Energy Required *</label>
                                <select class="form-control" id="energy_level_required" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Estimated Time (minutes) *</label>
                                <input type="number" class="form-control" id="estimated_time" value="30" min="5" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category</label>
                                <input type="text" class="form-control" id="category" placeholder="e.g., Work, Personal">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Deadline</label>
                        <input type="datetime-local" class="form-control" id="deadline">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
const API_URL = '/api/tasks';
let tasksTable;

$(document).ready(function() {
    loadTasks();
    
    $('#statusFilter').on('change', function() {
        loadTasks();
    });

    $('#taskForm').on('submit', function(e) {
        e.preventDefault();
        saveTask();
    });
});

function loadTasks() {
    const status = $('#statusFilter').val();
    const url = status ? `${API_URL}/list.php?status=${status}` : `${API_URL}/list.php`;
    
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayTasks(response.data);
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load tasks:', error);
        }
    });
}

function displayTasks(tasks) {
    const tbody = $('#tasksTableBody');
    
    if (tasks.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center">No tasks found</td></tr>');
        return;
    }

    const rows = tasks.map(task => {
        const priorityBadge = getPriorityBadge(task.priority);
        const statusBadge = getStatusBadge(task.status);
        const deadlineStr = task.deadline ? new Date(task.deadline).toLocaleDateString() : '-';

        return `
            <tr>
                <td>${task.title}</td>
                <td>${priorityBadge}</td>
                <td><span class="badge badge-info">${task.energy_level_required}</span></td>
                <td>${task.estimated_time}</td>
                <td>${deadlineStr}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="editTask(${task.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteTask(${task.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    tbody.html(rows);

    // Initialize DataTable
    if ($.fn.DataTable.isDataTable('#tasksTable')) {
        $('#tasksTable').DataTable().destroy();
    }
    $('#tasksTable').DataTable({
        order: [[1, 'desc']]
    });
}

function getPriorityBadge(priority) {
    const badges = {
        urgent: '<span class="badge badge-danger">Urgent</span>',
        high: '<span class="badge badge-warning">High</span>',
        medium: '<span class="badge badge-info">Medium</span>',
        low: '<span class="badge badge-success">Low</span>'
    };
    return badges[priority] || priority;
}

function getStatusBadge(status) {
    const badges = {
        pending: '<span class="badge badge-secondary">Pending</span>',
        scheduled: '<span class="badge badge-primary">Scheduled</span>',
        completed: '<span class="badge badge-success">Completed</span>',
        cancelled: '<span class="badge badge-danger">Cancelled</span>'
    };
    return badges[status] || status;
}

function resetForm() {
    $('#taskForm')[0].reset();
    $('#task_id').val('');
    $('#modalTitle').text('Add New Task');
    $('#submitBtn').text('Save Task');
}

function editTask(taskId) {
    $.ajax({
        url: `${API_URL}/get.php?id=${taskId}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const task = response.data;
                $('#task_id').val(task.id);
                $('#title').val(task.title);
                $('#description').val(task.description);
                $('#priority').val(task.priority);
                $('#energy_level_required').val(task.energy_level_required);
                $('#estimated_time').val(task.estimated_time);
                $('#category').val(task.category);
                
                if (task.deadline) {
                    const deadline = new Date(task.deadline);
                    const formatted = deadline.toISOString().slice(0, 16);
                    $('#deadline').val(formatted);
                }
                
                $('#modalTitle').text('Edit Task');
                $('#submitBtn').text('Update Task');
                $('#taskModal').modal('show');
            }
        }
    });
}

function saveTask() {
    const taskId = $('#task_id').val();
    const isEdit = taskId !== '';
    
    const taskData = {
        title: $('#title').val(),
        description: $('#description').val(),
        priority: $('#priority').val(),
        energy_level_required: $('#energy_level_required').val(),
        estimated_time: parseInt($('#estimated_time').val()),
        category: $('#category').val(),
        deadline: $('#deadline').val() || null
    };

    if (isEdit) {
        taskData.id = parseInt(taskId);
    }

    const url = isEdit ? `${API_URL}/update.php` : `${API_URL}/create.php`;
    const method = isEdit ? 'PUT' : 'POST';

    $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

    $.ajax({
        url: url,
        type: method,
        contentType: 'application/json',
        data: JSON.stringify(taskData),
        success: function(response) {
            if (response.success) {
                $('#taskModal').modal('hide');
                loadTasks();
                $(document).Toasts('create', {
                    class: 'bg-success',
                    title: 'Success',
                    body: isEdit ? 'Task updated!' : 'Task created!'
                });
            } else {
                alert(response.message || 'Failed to save task');
            }
        },
        error: function() {
            alert('Failed to save task');
        },
        complete: function() {
            $('#submitBtn').prop('disabled', false).text(isEdit ? 'Update Task' : 'Save Task');
        }
    });
}

function deleteTask(taskId) {
    if (!confirm('Are you sure you want to delete this task?')) {
        return;
    }

    $.ajax({
        url: `${API_URL}/delete.php`,
        type: 'DELETE',
        contentType: 'application/json',
        data: JSON.stringify({ id: taskId }),
        success: function(response) {
            if (response.success) {
                loadTasks();
                $(document).Toasts('create', {
                    class: 'bg-success',
                    title: 'Success',
                    body: 'Task deleted!'
                });
            } else {
                alert(response.message || 'Failed to delete task');
            }
        },
        error: function() {
            alert('Failed to delete task');
        }
    });
}
</script>

</body>
</html>