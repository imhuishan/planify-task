<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../src/Auth/login.php");
    exit;
}
require_once __DIR__ . '/../config/db.php';

$user_id = $_SESSION['user_id'];

function getStatusLabel($status) {
    $map = [
        'pending' => 'waiting',
        'in-progress' => 'doing',
        'completed' => 'finish',
        'waiting' => 'waiting',
        'doing' => 'doing',
        'finish' => 'finish'
    ];
    return $map[$status] ?? $status;
}

// Fetch stats for the chart
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks WHERE assigned_to = ? OR created_by = ? GROUP BY status");
$stmt->execute([$user_id, $user_id]);
$stats_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stats = [
    'waiting' => ($stats_raw['waiting'] ?? 0) + ($stats_raw['pending'] ?? 0),
    'doing' => ($stats_raw['doing'] ?? 0) + ($stats_raw['in-progress'] ?? 0),
    'finish' => ($stats_raw['finish'] ?? 0) + ($stats_raw['completed'] ?? 0)
];

// Fetch priority stats for chart
$stmt = $pdo->prepare("SELECT priority, COUNT(*) as count FROM tasks WHERE assigned_to = ? OR created_by = ? GROUP BY priority");
$stmt->execute([$user_id, $user_id]);
$priority_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Filters
$filter_status = $_GET['filter_status'] ?? 'all';
$filter_priority = $_GET['filter_priority'] ?? 'all';
$sort = $_GET['sort'] ?? 'created_desc';

// Base Query for tasks
$params = ['uid' => $user_id, 'uid2' => $user_id];
$where_clause = "(t.assigned_to = :uid OR t.created_by = :uid2)";

if ($filter_status !== 'all') {
    $map = ['waiting' => 'pending', 'doing' => 'in-progress', 'finish' => 'completed'];
    $db_status = $map[$filter_status] ?? $filter_status;
    $where_clause .= " AND t.status = :status";
    $params['status'] = $db_status;
}

if ($filter_priority !== 'all') {
    $where_clause .= " AND t.priority = :priority";
    $params['priority'] = $filter_priority;
}

$order_clause = "ORDER BY t.created_at DESC";
if ($sort === 'due_asc') {
    $order_clause = "ORDER BY t.due_date ASC";
} elseif ($sort === 'due_desc') {
    $order_clause = "ORDER BY t.due_date DESC";
}

// Fetch "Assigned to Me" tasks
$stmt_assigned = $pdo->prepare("SELECT t.*, u.username as creator_name FROM tasks t JOIN users u ON t.created_by = u.id WHERE $where_clause AND t.assigned_to = :uid $order_clause LIMIT 20");
$stmt_assigned->execute($params);
$assigned_tasks = $stmt_assigned->fetchAll();

// Fetch "My Reported Tasks"
$stmt_reported = $pdo->prepare("SELECT t.*, u.username as assignee_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE $where_clause AND t.created_by = :uid2 $order_clause LIMIT 20");
$stmt_reported->execute($params);
$reported_tasks = $stmt_reported->fetchAll();

$categories = ['General', 'Work', 'Personal', 'Bug', 'Feature'];

// Fetch all users for possible reassignment (if we want to allow it later, or just to have it)
$stmt = $pdo->query("SELECT id, username FROM users");
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Dashboard - Tasker.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include __DIR__ . '/../templates/sidebar.php'; ?>

    <main class="main-wrapper">
        <header style="margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>
            <div>
                <h1 style="font-size: 2.25rem;">Welcome back, <span class="gradient-text"><?= htmlspecialchars($_SESSION['username']) ?></span></h1>
                <p class="hide-mobile" style="color: var(--text-secondary); margin-top: 0.5rem;">Here's what's happening with your tasks today.</p>
            </div>
        </header>

        <!-- Real-time Activity Bar -->
        <div class="glass-card activity-bar" style="margin-bottom: 2.5rem; padding: 0.8rem 1.5rem; display: flex; align-items: center; gap: 1.5rem; border: 1px solid rgba(255,255,255,0.05); overflow: hidden;">
            <div class="activity-bar-label">
                <span class="live-pulse"></span>
                System Activity
            </div>
            <div id="activityTicker" style="flex: 1; font-size: 0.9rem; color: var(--text-secondary); white-space: nowrap; overflow: hidden; position: relative; height: 20px; display: flex; align-items: center;">
                <div id="tickerContent" style="display: flex; gap: 1rem; align-items: center; transition: opacity 0.5s ease;">
                    <span>Loading latest activity...</span>
                </div>
            </div>
        </div>

        <!-- Analytics & Stats Grid -->
        <div class="analytics-row">
            <div class="glass-card" style="padding: 1.5rem; display: flex; align-items: center; justify-content: space-around;">
                <div style="text-align: center;">
                    <span class="stat-value" style="color: #94a3b8;"><?= $stats['waiting'] ?></span>
                    <span class="stat-label">Waiting</span>
                </div>
                <div style="text-align: center;">
                    <span class="stat-value" style="color: #3b82f6;"><?= $stats['doing'] ?></span>
                    <span class="stat-label">Doing</span>
                </div>
                <div style="text-align: center;">
                    <span class="stat-value" style="color: #22c55e;"><?= $stats['finish'] ?></span>
                    <span class="stat-label">Finish</span>
                </div>
            </div>
            <div class="glass-card" style="padding: 1rem; position: relative; height: 160px;">
                <canvas id="tasksChart"></canvas>
            </div>
        </div>

        <!-- Filter & Sort Bar -->
        <div class="glass-card filter-bar" style="margin-bottom: 2rem; padding: 1rem 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <span style="color: var(--text-secondary); font-weight: 600; font-size: 0.9rem; white-space: nowrap;">Filters:</span>
            
            <form id="filterForm" method="GET" class="filter-form">
                <select name="filter_status" class="form-control" style="width: auto; padding: 0.5rem 2.5rem 0.5rem 1rem;" onchange="document.getElementById('filterForm').submit()">
                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="waiting" <?= $filter_status === 'waiting' ? 'selected' : '' ?>>Waiting</option>
                    <option value="doing" <?= $filter_status === 'doing' ? 'selected' : '' ?>>Doing</option>
                    <option value="finish" <?= $filter_status === 'finish' ? 'selected' : '' ?>>Finish</option>
                </select>

                <select name="filter_priority" class="form-control" style="width: auto; padding: 0.5rem 2.5rem 0.5rem 1rem;" onchange="document.getElementById('filterForm').submit()">
                    <option value="all" <?= $filter_priority === 'all' ? 'selected' : '' ?>>All Priorities</option>
                    <option value="urgent" <?= $filter_priority === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                    <option value="high" <?= $filter_priority === 'high' ? 'selected' : '' ?>>High</option>
                    <option value="medium" <?= $filter_priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="low" <?= $filter_priority === 'low' ? 'selected' : '' ?>>Low</option>
                </select>

                <select name="sort" class="form-control" style="width: auto; padding: 0.5rem 2.5rem 0.5rem 1rem; margin-left: auto;" onchange="document.getElementById('filterForm').submit()">
                    <option value="created_desc" <?= $sort === 'created_desc' ? 'selected' : '' ?>>Newest First</option>
                    <option value="due_asc" <?= $sort === 'due_asc' ? 'selected' : '' ?>>Due Date (Earliest)</option>
                    <option value="due_desc" <?= $sort === 'due_desc' ? 'selected' : '' ?>>Due Date (Latest)</option>
                </select>
            </form>
        </div>

        <div class="dashboard-sections">
            <!-- Assigned to Me -->
            <section class="glass-card" style="padding: 2rem;">
                <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Assigned to Me</h3>
                <ul class="task-list">
                    <?php if (empty($assigned_tasks)): ?>
                        <li style="color: var(--text-secondary); padding: 2rem; text-align: center;">No tasks assigned to you.</li>
                    <?php else: ?>
                        <?php foreach ($assigned_tasks as $task): ?>
                            <?php $status = getStatusLabel($task['status']); ?>
                            <li class="task-item" onclick="openTaskModal(<?= $task['id'] ?>)">
                                <div class="task-item-left">
                                    <span class="priority-badge priority-<?= $task['priority'] ?? 'medium' ?>"><?= $task['priority'] ?? 'medium' ?></span>
                                    <div>
                                        <div class="task-item-title"><?= htmlspecialchars($task['title']) ?></div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                            <?php if(isset($task['category'])): ?>
                                                <span class="category-tag"><?= htmlspecialchars($task['category']) ?></span> • 
                                            <?php endif; ?>
                                            Created by <?= htmlspecialchars($task['creator_name']) ?>
                                        </div>
                                    </div>
                                </div>
                                <span class="status-badge status-<?= $status ?>"><?= $status ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </section>

            <!-- Created by Me -->
            <section class="glass-card" style="padding: 2rem;">
                <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Reported by Me</h3>
                <ul class="task-list">
                    <?php if (empty($reported_tasks)): ?>
                        <li style="color: var(--text-secondary); padding: 2rem; text-align: center;">You haven't reported any tasks.</li>
                    <?php else: ?>
                        <?php foreach ($reported_tasks as $task): ?>
                            <?php $status = getStatusLabel($task['status']); ?>
                            <li class="task-item" onclick="openTaskModal(<?= $task['id'] ?>)">
                                <div class="task-item-left">
                                    <span class="priority-badge priority-<?= $task['priority'] ?? 'medium' ?>"><?= $task['priority'] ?? 'medium' ?></span>
                                    <div>
                                        <div class="task-item-title"><?= htmlspecialchars($task['title']) ?></div>
                                        <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                            <?php if(isset($task['category'])): ?>
                                                <span class="category-tag"><?= htmlspecialchars($task['category']) ?></span> • 
                                            <?php endif; ?>
                                            Assigned to <?= htmlspecialchars($task['assignee_name'] ?? 'Unassigned') ?>
                                        </div>
                                    </div>
                                </div>
                                <span class="status-badge status-<?= $status ?>"><?= $status ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </section>
        </div>
    </main>

    <!-- Comprehensive Task Detail Modal -->
    <div id="taskModal" class="modal-overlay" onclick="closeTaskModal(event)">
        <div class="glass-card modal-content task-detail-modal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2 id="modalTitle">Task Details</h2>
                <button class="btn-close" onclick="closeTaskModal()">×</button>
            </div>

            <div class="modal-body">
                <div class="task-info-section">
                    <form id="editTaskForm">
                        <input type="hidden" id="editTaskId" name="id">
                        <input type="hidden" name="action" value="edit_task">
                        
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" id="editTaskTitle" name="title" class="form-control" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group" style="flex: 1;">
                                <label>Status</label>
                                <select id="editTaskStatus" class="form-control" onchange="updateTaskStatus(this.value)">
                                    <option value="waiting">Waiting</option>
                                    <option value="doing">Doing</option>
                                    <option value="finish">Finish</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Priority</label>
                                <select id="editTaskPriority" name="priority" class="form-control">
                                    <option value="urgent">Urgent</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group" style="flex: 1;">
                                <label>Category</label>
                                <select id="editTaskCategory" name="category" class="form-control">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat ?>"><?= $cat ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Due Date</label>
                                <input type="date" id="editTaskDueDate" name="due_date" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea id="editTaskDesc" name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Project (Optional)</label>
                            <select id="editTaskProject" name="project_id" class="form-control">
                                <option value="">No Project</option>
                            </select>
                        </div>

                        <div class="modal-actions" style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                            <button type="button" class="btn btn-primary" onclick="saveTaskEdits()">Save Changes</button>
                            <button type="button" class="btn btn-outline" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3);" onclick="deleteTask()">Delete Task</button>
                        </div>
                    </form>
                </div>

                <div class="comments-section" style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                    <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Comments</h3>
                    <div id="commentsList" style="max-height: 250px; overflow-y: auto; margin-bottom: 1rem; padding-right: 0.5rem;">
                        <!-- Comments will be injected here -->
                    </div>
                    <div class="add-comment" style="display: flex; gap: 0.5rem;">
                        <input type="text" id="newCommentText" class="form-control" placeholder="Add a comment..." onkeypress="if(event.key === 'Enter') addComment()">
                        <button class="btn btn-primary" onclick="addComment()">Post</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart.js Setup
        const ctx = document.getElementById('tasksChart').getContext('2d');
        const tasksChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Waiting', 'Doing', 'Finish'],
                datasets: [{
                    data: [<?= $stats['waiting'] ?>, <?= $stats['doing'] ?>, <?= $stats['finish'] ?>],
                    backgroundColor: ['#94a3b8', '#3b82f6', '#22c55e'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { color: '#f8fafc' } }
                }
            }
        });

        // Modal Logic
        let currentTaskId = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadProjectsForModal();
        });

        function loadProjectsForModal() {
            fetch('api/projects.php?action=list_projects')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const projectSelect = document.getElementById('editTaskProject');
                        data.projects.forEach(p => {
                            const opt = document.createElement('option');
                            opt.value = p.id;
                            opt.textContent = p.name;
                            projectSelect.appendChild(opt);
                        });
                    }
                });
        }

        function openTaskModal(taskId) {
            currentTaskId = taskId;
            document.getElementById('taskModal').style.display = 'flex';
            
            // Fetch Task Details
            const formData = new FormData();
            formData.append('action', 'get_task');
            formData.append('id', taskId);

            fetch('api/tasks.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const task = data.task;
                        document.getElementById('editTaskId').value = task.id;
                        document.getElementById('editTaskTitle').value = task.title;
                        document.getElementById('editTaskDesc').value = task.description || '';
                        document.getElementById('editTaskStatus').value = task.status_label;
                        document.getElementById('editTaskPriority').value = task.priority || 'medium';
                        document.getElementById('editTaskCategory').value = task.category || 'General';
                        document.getElementById('editTaskDueDate').value = task.due_date || '';
                        document.getElementById('editTaskProject').value = task.project_id || '';
                        
                        loadComments();
                    } else {
                        alert('Error loading task details');
                    }
                });
        }

        function closeTaskModal(event) {
            if (!event || event.target === document.getElementById('taskModal') || event.target.className === 'btn-close') {
                document.getElementById('taskModal').style.display = 'none';
            }
        }

        function saveTaskEdits() {
            const form = document.getElementById('editTaskForm');
            const formData = new FormData(form);

            fetch('api/tasks.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to save changes');
                    }
                });
        }

        function updateTaskStatus(newStatus) {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('id', currentTaskId);
            formData.append('status', newStatus);

            fetch('api/tasks.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Just update the visual state, we'll reload on close anyway or full save
                    }
                });
        }

        function deleteTask() {
            if (confirm("Are you sure you want to delete this task? This cannot be undone.")) {
                const formData = new FormData();
                formData.append('action', 'delete_task');
                formData.append('id', currentTaskId);

                fetch('api/tasks.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Failed to delete task or you do not have permission.');
                        }
                    });
            }
        }

        function loadComments() {
            const formData = new FormData();
            formData.append('action', 'get_comments');
            formData.append('task_id', currentTaskId);

            fetch('api/comments.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const list = document.getElementById('commentsList');
                        list.innerHTML = '';
                        if (data.comments.length === 0) {
                            list.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.9rem; text-align: center;">No comments yet.</p>';
                        } else {
                            data.comments.forEach(c => {
                                const date = new Date(c.created_at).toLocaleString();
                                list.innerHTML += `
                                    <div class="comment-bubble" style="background: rgba(255,255,255,0.05); padding: 0.8rem; border-radius: 12px; margin-bottom: 0.8rem;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                                            <span style="font-weight: 600; font-size: 0.85rem; color: var(--primary-color);">${c.username}</span>
                                            <span style="font-size: 0.75rem; color: var(--text-secondary);">${date}</span>
                                        </div>
                                        <div style="font-size: 0.9rem;">${c.comment}</div>
                                    </div>
                                `;
                            });
                        }
                    }
                });
        }

        function addComment() {
            const textInput = document.getElementById('newCommentText');
            const text = textInput.value.trim();
            if (!text) return;

            const formData = new FormData();
            formData.append('action', 'add_comment');
            formData.append('task_id', currentTaskId);
            formData.append('comment', text);

            fetch('api/comments.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        textInput.value = '';
                        loadComments(); // reload comments
                    } else {
                        alert(data.error || 'Failed to add comment');
                    }
                });
        }
    </script>
    <script>
        function updateActivities() {
            fetch('api/activities.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.activities.length > 0) {
                        const ticker = document.getElementById('tickerContent');
                        const act = data.activities[0]; // Only show the latest one
                        
                        const time = new Date(act.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        let emoji = '🔔';
                        if (act.activity_type === 'login') emoji = '🔐';
                        if (act.activity_type === 'task_create') emoji = '➕';
                        if (act.activity_type === 'status_update') emoji = '✅';
                        
                        ticker.style.opacity = '0';
                        setTimeout(() => {
                            ticker.innerHTML = `
                                <strong style="color: var(--primary-color); font-size: 0.8rem; background: rgba(99, 102, 241, 0.1); padding: 2px 8px; border-radius: 4px;">${time}</strong> 
                                <span>${emoji} <span style="font-weight: 600; color: #fff;">${act.username}</span> ${act.activity_detail}</span>
                            `;
                            ticker.style.opacity = '1';
                        }, 500);
                    }
                });
        }

        // Initial load
        updateActivities();
        // Poll every 15 seconds
        setInterval(updateActivities, 15000);
    </script>
</body>
</html>
