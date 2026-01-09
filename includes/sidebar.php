<?php
// File: views/includes/sidebar.php
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h1>🤖 AI Daily Planner</h1>
    </div>
    
    <nav class="sidebar-nav">
        <a href="../dashboard/index.php" class="nav-item active">
            <span class="nav-icon">📊</span>
            <span>Dashboard</span>
        </a>
        
        <a href="../tasks/index.php" class="nav-item">
            <span class="nav-icon">📝</span>
            <span>My Tasks</span>
        </a>
        
        <a href="../mood/log.php" class="nav-item">
            <span class="nav-icon">😊</span>
            <span>Log Mood</span>
        </a>
        
        <a href="../mood/history.php" class="nav-item">
            <span class="nav-icon">📈</span>
            <span>Mood History</span>
        </a>
        
        <a href="../schedule/index.php" class="nav-item">
            <span class="nav-icon">🗓️</span>
            <span>AI Schedule</span>
        </a>
        
        <a href="../schedule/suggestions.php" class="nav-item">
            <span class="nav-icon">💡</span>
            <span>Suggestions</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <p style="padding: 20px; color: rgba(255,255,255,0.6); font-size: 0.85rem; text-align: center;">
            AI Daily Planner v1.0
        </p>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 250px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    z-index: 1000;
    overflow-y: auto;
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h1 {
    font-size: 1.3rem;
    font-weight: 600;
    margin: 0;
}

.sidebar-nav {
    padding: 20px 0;
}

.nav-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s;
    border-left: 4px solid transparent;
}

.nav-item:hover {
    background: rgba(255,255,255,0.1);
    color: white;
}

.nav-item.active {
    background: rgba(255,255,255,0.2);
    color: white;
    border-left: 4px solid white;
}

.nav-icon {
    margin-right: 10px;
    font-size: 1.2rem;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    width: 100%;
    border-top: 1px solid rgba(255,255,255,0.1);
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s;
    }

    .sidebar.active {
        transform: translateX(0);
    }
}
</style>