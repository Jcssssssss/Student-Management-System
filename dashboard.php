<?php
// ============================================================
// Dashboard Page
// ============================================================
require_once 'config.php';
requireLogin();

$db = getDB();
$sid = $_SESSION['student_id'];
$name = $_SESSION['full_name'];

// Fetch task counts
$counts = $db->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(status = 'Pending')   as pending,
        SUM(status = 'Ongoing')   as ongoing,
        SUM(status = 'Completed') as completed
    FROM tasks WHERE student_id = ?
");
$counts->execute([$sid]);
$stats = $counts->fetch();

// Fetch upcoming deadlines (next 7 days, not completed)
$deadlines = $db->prepare("
    SELECT id, title, deadline, status,
           DATEDIFF(deadline, CURDATE()) as days_left
    FROM tasks 
    WHERE student_id = ? AND status != 'Completed' AND deadline >= CURDATE()
    ORDER BY deadline ASC
    LIMIT 5
");
$deadlines->execute([$sid]);
$upcomingDeadlines = $deadlines->fetchAll();

// Get initials for avatar
$initials = '';
$parts = explode(' ', $name);
foreach ($parts as $p) { $initials .= strtoupper($p[0] ?? ''); }
$initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Student Task Manager</title>
    <meta name="description" content="Manage your tasks and assignments from the dashboard.">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="bg-shapes">
        <span></span><span></span><span></span>
    </div>

    <!-- Mobile toggle -->
    <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle sidebar">☰</button>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="app-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">📋</div>
                <h2>Task Manager <span>SIA101 Project</span></h2>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active" id="navDashboard">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="#" onclick="document.getElementById('taskSection').scrollIntoView({behavior:'smooth'}); return false;" id="navTasks">
                    <span class="nav-icon">✅</span> My Tasks
                </a>
                <a href="logout.php" id="navLogout">
                    <span class="nav-icon">🚪</span> Logout
                </a>
            </nav>

            <div class="sidebar-user">
                <div class="avatar"><?= $initials ?></div>
                <div class="user-info">
                    <div class="name"><?= htmlspecialchars($name) ?></div>
                    <div class="sid"><?= htmlspecialchars($sid) ?></div>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1>👋 Welcome, <?= htmlspecialchars(explode(' ', $name)[0]) ?>!</h1>
                    <p>Here's your task overview for today.</p>
                </div>
                <button class="btn btn-primary" onclick="openAddModal()" id="addTaskBtn">
                    ➕ Add Task
                </button>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">📚</div>
                    <div class="stat-value" id="statTotal"><?= $stats['total'] ?></div>
                    <div class="stat-label">Total Tasks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pending">⏳</div>
                    <div class="stat-value" id="statPending"><?= $stats['pending'] ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon ongoing">🔄</div>
                    <div class="stat-value" id="statOngoing"><?= $stats['ongoing'] ?></div>
                    <div class="stat-label">Ongoing</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon completed">✅</div>
                    <div class="stat-value" id="statCompleted"><?= $stats['completed'] ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>

            <!-- Upcoming Deadlines -->
            <?php if (!empty($upcomingDeadlines)): ?>
            <div class="section-card">
                <div class="section-header">
                    <h2>📅 Upcoming Deadlines</h2>
                </div>
                <div class="deadline-list">
                    <?php foreach ($upcomingDeadlines as $dl): 
                        $d = new DateTime($dl['deadline']);
                        $urgencyClass = $dl['days_left'] <= 1 ? 'urgent' : ($dl['days_left'] <= 3 ? 'soon' : 'ok');
                        $remaining = $dl['days_left'] == 0 ? 'Due today!' : ($dl['days_left'] == 1 ? 'Due tomorrow' : "In {$dl['days_left']} days");
                    ?>
                    <div class="deadline-item">
                        <div class="dl-date">
                            <span class="dl-day"><?= $d->format('d') ?></span>
                            <span class="dl-month"><?= $d->format('M') ?></span>
                        </div>
                        <div class="dl-info">
                            <div class="dl-title"><?= htmlspecialchars($dl['title']) ?></div>
                            <div class="dl-remaining <?= $urgencyClass ?>"><?= $remaining ?></div>
                        </div>
                        <span class="status-badge <?= $dl['status'] ?>"><?= $dl['status'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Task List Section -->
            <div class="section-card" id="taskSection">
                <div class="section-header">
                    <h2>📝 My Tasks</h2>
                    <div class="filter-tabs" id="filterTabs">
                        <button class="active" data-filter="All" id="filterAll">All</button>
                        <button data-filter="Pending" id="filterPending">Pending</button>
                        <button data-filter="Ongoing" id="filterOngoing">Ongoing</button>
                        <button data-filter="Completed" id="filterCompleted">Completed</button>
                    </div>
                </div>
                <div class="task-list" id="taskList">
                    <!-- Tasks loaded via JS -->
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal-overlay" id="taskModal">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Task</h2>
                <button class="modal-close" onclick="closeModal()" id="modalCloseBtn">✕</button>
            </div>
            <form id="taskForm" onsubmit="handleTaskSubmit(event)">
                <input type="hidden" id="taskId" name="id">

                <div class="form-group">
                    <label for="taskTitleInput">Task Title</label>
                    <input type="text" id="taskTitleInput" name="title" 
                           placeholder="e.g. SIA101 Final Project" required>
                </div>

                <div class="form-group">
                    <label for="taskDescInput">Description</label>
                    <textarea id="taskDescInput" name="description" 
                              placeholder="Describe the task details..."></textarea>
                </div>

                <div class="form-group">
                    <label for="taskDeadlineInput">Deadline</label>
                    <input type="date" id="taskDeadlineInput" name="deadline" required>
                </div>

                <div class="form-group">
                    <label for="taskStatusInput">Status</label>
                    <select id="taskStatusInput" name="status">
                        <option value="Pending">Pending</option>
                        <option value="Ongoing">Ongoing</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn">💾 Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirm Delete Dialog -->
    <div class="confirm-overlay" id="confirmOverlay">
        <div class="confirm-box">
            <div class="confirm-icon">🗑️</div>
            <h3>Delete Task?</h3>
            <p>This action cannot be undone. The task will be permanently removed.</p>
            <div class="confirm-actions">
                <button class="btn btn-secondary" onclick="closeConfirm()" id="confirmCancelBtn">Cancel</button>
                <button class="btn btn-danger" onclick="confirmDelete()" id="confirmDeleteBtn">🗑️ Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="js/app.js"></script>
</body>
</html>
