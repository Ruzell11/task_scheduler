<?php include '../../includes/header.php'; ?>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Log Your Mood</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="../dashboard/index.php">Home</a></li>
                            <li class="breadcrumb-item active">Log Mood</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h3 class="card-title">How are you feeling?</h3>
                            </div>
                            <div class="card-body">
                                <form id="moodForm">
                                    <div class="form-group">
                                        <label>Mood Type</label>
                                        <select class="form-control" id="mood_type" required>
                                            <option value="">Select your mood...</option>
                                            <option value="energetic">⚡ Energetic</option>
                                            <option value="focused">🎯 Focused</option>
                                            <option value="creative">🎨 Creative</option>
                                            <option value="tired">😴 Tired</option>
                                            <option value="stressed">😰 Stressed</option>
                                            <option value="neutral">😐 Neutral</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Energy Level: <span id="energyValue" class="badge badge-info">5</span>/10</label>
                                        <input type="range" class="custom-range" id="energy_level" min="1" max="10" value="5">
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">Low</small>
                                            <small class="text-muted">Medium</small>
                                            <small class="text-muted">High</small>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Notes (Optional)</label>
                                        <textarea class="form-control" id="notes" rows="3" placeholder="What's on your mind? Any context about your current state?"></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                                        <i class="fas fa-save"></i> Log Mood
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success">
                                <h3 class="card-title">
                                    <i class="fas fa-brain"></i> AI Analysis
                                </h3>
                            </div>
                            <div class="card-body" id="aiAnalysis">
                                <div class="text-center text-muted py-5">
                                    <i class="fas fa-lightbulb fa-3x mb-3"></i>
                                    <p>Log your mood to get AI-powered insights and personalized recommendations!</p>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Mood -->
                        <div class="card" id="recentMoodCard" style="display: none;">
                            <div class="card-header bg-info">
                                <h3 class="card-title">
                                    <i class="fas fa-history"></i> Your Last Mood
                                </h3>
                            </div>
                            <div class="card-body" id="recentMood">
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
    // Load recent mood
    loadRecentMood();

    // Update energy value display
    $('#energy_level').on('input', function() {
        const value = $(this).val();
        $('#energyValue').text(value);
        
        // Change badge color based on energy
        const badge = $('#energyValue');
        badge.removeClass('badge-danger badge-warning badge-info badge-success');
        if (value <= 3) {
            badge.addClass('badge-danger');
        } else if (value <= 5) {
            badge.addClass('badge-warning');
        } else if (value <= 7) {
            badge.addClass('badge-info');
        } else {
            badge.addClass('badge-success');
        }
    });
    
    // Handle form submission
    $('#moodForm').on('submit', function(e) {
        e.preventDefault();
        
        const mood_type = $('#mood_type').val();
        const energy_level = $('#energy_level').val();
        const notes = $('#notes').val();

        if (!mood_type) {
            alert('Please select a mood type');
            return;
        }
        
        const moodData = {
            mood_type: mood_type,
            energy_level: parseInt(energy_level),
            notes: notes
        };
        
        // Disable submit button
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Logging...');
        
        $.ajax({
            url: '/api/moods/log.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(moodData),
            success: function(response) {
                if (response.success) {
                    // Display AI analysis
                    let recommendationsHtml = '';
                    if (Array.isArray(response.recommendations)) {
                        recommendationsHtml = '<ul class="list-unstyled">';
                        response.recommendations.forEach(function(rec) {
                            recommendationsHtml += `<li><i class="fas fa-check text-success"></i> ${rec}</li>`;
                        });
                        recommendationsHtml += '</ul>';
                    } else {
                        recommendationsHtml = `<p>${response.recommendations}</p>`;
                    }

                    $('#aiAnalysis').html(`
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <h5><i class="icon fas fa-check"></i> Mood Logged Successfully!</h5>
                        </div>
                        
                        <div class="mb-3">
                            <h5><i class="fas fa-chart-line text-primary"></i> AI Analysis:</h5>
                            <div class="alert alert-light">
                                ${response.analysis}
                            </div>
                        </div>
                        
                        <div>
                            <h5><i class="fas fa-lightbulb text-warning"></i> Recommendations:</h5>
                            ${recommendationsHtml}
                        </div>
                    `);
                    
                    // Reset form
                    $('#moodForm')[0].reset();
                    $('#energyValue').text('5').removeClass().addClass('badge badge-info');
                    
                    // Show success toast
                    $(document).Toasts('create', {
                        class: 'bg-success',
                        title: 'Success',
                        body: 'Mood logged successfully!'
                    });

                    // Reload recent mood
                    loadRecentMood();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('Failed to log mood. Please try again.');
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Log Mood');
            }
        });
    });

    // Load recent mood
    function loadRecentMood() {
        $.ajax({
            url: '/api/moods/log.php',
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    const mood = response.data;
                    const date = new Date(mood.recorded_at);
                    const timeAgo = getTimeAgo(date);
                    
                    const moodEmojis = {
                        'energetic': '⚡',
                        'focused': '🎯',
                        'creative': '🎨',
                        'tired': '😴',
                        'stressed': '😰',
                        'neutral': '😐'
                    };

                    $('#recentMood').html(`
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="h4 mb-0">${moodEmojis[mood.mood_type] || '😊'} ${mood.mood_type}</span>
                            <span class="badge badge-info">${mood.energy_level}/10</span>
                        </div>
                        <small class="text-muted">
                            <i class="far fa-clock"></i> ${timeAgo}
                        </small>
                        ${mood.notes ? `<p class="mt-2 mb-0"><small>${mood.notes}</small></p>` : ''}
                    `);
                    
                    $('#recentMoodCard').show();
                }
            }
        });
    }

    // Helper function to calculate time ago
    function getTimeAgo(date) {
        const seconds = Math.floor((new Date() - date) / 1000);
        
        let interval = seconds / 31536000;
        if (interval > 1) return Math.floor(interval) + " years ago";
        
        interval = seconds / 2592000;
        if (interval > 1) return Math.floor(interval) + " months ago";
        
        interval = seconds / 86400;
        if (interval > 1) return Math.floor(interval) + " days ago";
        
        interval = seconds / 3600;
        if (interval > 1) return Math.floor(interval) + " hours ago";
        
        interval = seconds / 60;
        if (interval > 1) return Math.floor(interval) + " minutes ago";
        
        return "just now";
    }
});
</script>