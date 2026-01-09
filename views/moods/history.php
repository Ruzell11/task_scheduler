<?php
// File: views/moods/history.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

include '../../includes/header.php';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Mood History</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../dashboard/index.php">Home</a></li>
                        <li class="breadcrumb-item active">Mood History</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <!-- Filter Controls -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Time Period</label>
                                    <select class="form-control" id="timePeriod">
                                        <option value="7">Last 7 Days</option>
                                        <option value="14">Last 14 Days</option>
                                        <option value="30" selected>Last 30 Days</option>
                                        <option value="90">Last 90 Days</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>Mood Type</label>
                                    <select class="form-control" id="moodFilter">
                                        <option value="">All Moods</option>
                                        <option value="energetic">Energetic</option>
                                        <option value="focused">Focused</option>
                                        <option value="creative">Creative</option>
                                        <option value="tired">Tired</option>
                                        <option value="stressed">Stressed</option>
                                        <option value="neutral">Neutral</option>
                                        <option value="happy">Happy</option>
                                        <option value="relaxed">Relaxed</option>
                                        <option value="anxious">Anxious</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-primary btn-block" onclick="loadMoodHistory()">
                                        <i class="fas fa-filter"></i> Apply Filter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3 id="totalEntries">0</h3>
                            <p>Total Entries</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-list"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3 id="avgEnergy">0</h3>
                            <p>Avg Energy Level</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3 id="mostCommonMood">--</h3>
                            <p>Most Common Mood</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-smile"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3 id="streakDays">0</h3>
                            <p>Day Streak</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-fire"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <!-- Energy Level Chart -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-1"></i>
                                Energy Level Trend
                            </h3>
                        </div>
                        <div class="card-body">
                            <canvas id="energyChart" style="height: 250px;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Mood Distribution Chart -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-1"></i>
                                Mood Distribution
                            </h3>
                        </div>
                        <div class="card-body">
                            <canvas id="moodChart" style="height: 250px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mood History Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-history mr-1"></i>
                                Mood Entries
                            </h3>
                        </div>
                        <div class="card-body">
                            <table id="moodTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Mood</th>
                                        <th>Energy Level</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="moodTableBody">
                                    <tr>
                                        <td colspan="4" class="text-center">Loading...</td>
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

<?php include '../../includes/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
const API_URL = '/api/moods';
let energyChart, moodChart;

// Load mood history on page load
$(document).ready(function() {
    loadMoodHistory();
    initializeCharts();
});

// Load mood history data
function loadMoodHistory() {
    const days = $('#timePeriod').val();
    const moodType = $('#moodFilter').val();

    $.ajax({
        url: `${API_URL}/history.php?days=${days}`,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                let moods = response.data;
                
                // Filter by mood type if selected
                if (moodType) {
                    moods = moods.filter(m => m.mood_type === moodType);
                }

                displayStats(moods);
                displayMoodTable(moods);
                updateCharts(moods);
            } else {
                console.error('Failed to load mood history:', response.message);
                alert('Failed to load mood history: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading mood history:', error);
            alert('Failed to load mood history. Please try again.');
        }
    });
}

// Display statistics
function displayStats(moods) {
    $('#totalEntries').text(moods.length);

    if (moods.length > 0) {
        // Average energy
        const avgEnergy = moods.reduce((sum, m) => sum + parseInt(m.energy_level), 0) / moods.length;
        $('#avgEnergy').text(avgEnergy.toFixed(1));

        // Most common mood
        const moodCounts = {};
        moods.forEach(m => {
            moodCounts[m.mood_type] = (moodCounts[m.mood_type] || 0) + 1;
        });
        const mostCommon = Object.keys(moodCounts).reduce((a, b) => 
            moodCounts[a] > moodCounts[b] ? a : b
        );
        $('#mostCommonMood').text(mostCommon.charAt(0).toUpperCase() + mostCommon.slice(1));

        // Calculate streak
        $('#streakDays').text(calculateStreak(moods));
    } else {
        $('#avgEnergy').text('0');
        $('#mostCommonMood').text('--');
        $('#streakDays').text('0');
    }
}

// Calculate logging streak
function calculateStreak(moods) {
    if (moods.length === 0) return 0;
    
    const dates = moods.map(m => new Date(m.recorded_at).toDateString());
    const uniqueDates = [...new Set(dates)];
    
    let streak = 1;
    const today = new Date().toDateString();
    
    if (uniqueDates[0] !== today) return 0;
    
    for (let i = 0; i < uniqueDates.length - 1; i++) {
        const current = new Date(uniqueDates[i]);
        const next = new Date(uniqueDates[i + 1]);
        const diffDays = Math.floor((current - next) / (1000 * 60 * 60 * 24));
        
        if (diffDays === 1) {
            streak++;
        } else {
            break;
        }
    }
    
    return streak;
}

// Display mood table
function displayMoodTable(moods) {
    const tbody = $('#moodTableBody');
    
    if (moods.length === 0) {
        tbody.html('<tr><td colspan="4" class="text-center">No mood entries found</td></tr>');
        return;
    }

    const rows = moods.map(mood => {
        const date = new Date(mood.recorded_at);
        const formattedDate = date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        const moodIcon = getMoodIcon(mood.mood_type);
        const energyBadge = getEnergyBadge(mood.energy_level);

        return `
            <tr>
                <td>${formattedDate}</td>
                <td>
                    ${moodIcon} 
                    <span class="badge badge-info">${mood.mood_type}</span>
                </td>
                <td>${energyBadge}</td>
                <td>${mood.notes || '-'}</td>
            </tr>
        `;
    }).join('');

    tbody.html(rows);

    // Initialize DataTable
    if ($.fn.DataTable.isDataTable('#moodTable')) {
        $('#moodTable').DataTable().destroy();
    }
    
    $('#moodTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 10
    });
}

// Get mood icon
function getMoodIcon(mood) {
    const icons = {
        energetic: '⚡',
        focused: '🎯',
        creative: '🎨',
        tired: '😴',
        stressed: '😰',
        neutral: '😐',
        happy: '😊',
        relaxed: '😌',
        anxious: '😟'
    };
    return icons[mood] || '😊';
}

// Get energy badge
function getEnergyBadge(level) {
    level = parseInt(level);
    let color = 'secondary';
    if (level >= 8) color = 'success';
    else if (level >= 5) color = 'warning';
    else color = 'danger';
    
    return `<span class="badge badge-${color}">${level}/10</span>`;
}

// Initialize charts
function initializeCharts() {
    // Energy Chart
    const energyCtx = document.getElementById('energyChart').getContext('2d');
    energyChart = new Chart(energyCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Energy Level',
                data: [],
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Mood Chart
    const moodCtx = document.getElementById('moodChart').getContext('2d');
    moodChart = new Chart(moodCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40',
                    '#FF6384',
                    '#C9CBCF',
                    '#4BC0C0'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
}

// Update charts with data
function updateCharts(moods) {
    if (moods.length === 0) {
        energyChart.data.labels = [];
        energyChart.data.datasets[0].data = [];
        energyChart.update();
        
        moodChart.data.labels = [];
        moodChart.data.datasets[0].data = [];
        moodChart.update();
        return;
    }

    // Update Energy Chart (last 14 entries)
    const energyData = moods.slice(0, 14).reverse();
    energyChart.data.labels = energyData.map(m => {
        const date = new Date(m.recorded_at);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    energyChart.data.datasets[0].data = energyData.map(m => parseInt(m.energy_level));
    energyChart.update();

    // Update Mood Chart
    const moodCounts = {};
    moods.forEach(m => {
        moodCounts[m.mood_type] = (moodCounts[m.mood_type] || 0) + 1;
    });

    moodChart.data.labels = Object.keys(moodCounts).map(m => 
        m.charAt(0).toUpperCase() + m.slice(1)
    );
    moodChart.data.datasets[0].data = Object.values(moodCounts);
    moodChart.update();
}
</script>

</body>
</html>