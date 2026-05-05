<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../src/Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'member';
$project_id = $_GET['id'] ?? null;

if (!$project_id) {
    header("Location: projects.php");
    exit;
}

// Fetch project info
$stmt = $pdo->prepare("SELECT p.*, u.username as leader_name FROM projects p LEFT JOIN users u ON p.project_leader_id = u.id WHERE p.id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    header("Location: projects.php");
    exit;
}

// Permission check (Member, Leader, Manager, Admin)
if ($user_role !== 'admin' && $user_role !== 'manager') {
    $checkStmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? UNION SELECT 1 FROM projects WHERE id = ? AND project_leader_id = ?");
    $checkStmt->execute([$project_id, $user_id, $project_id, $user_id]);
    if (!$checkStmt->fetch()) {
        die("Permission denied for this project.");
    }
}

$categories = ['General', 'Work', 'Personal', 'Bug', 'Feature'];
$page_title = $project['name'] . " - Tasker.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../templates/sidebar.php'; ?>

    <main class="main-wrapper">
        <header style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <a href="projects.php" style="color: var(--text-secondary); text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Back to Projects
                </a>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2.25rem;"><?= htmlspecialchars($project['name']) ?></h1>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem;"><?= htmlspecialchars($project['description'] ?: 'No description.') ?></p>
                </div>
                <div class="glass-card" style="padding: 0.75rem 1.25rem; text-align: center; min-width: 120px;">
                    <span style="display: block; font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Leader</span>
                    <span style="font-weight: 700; color: var(--primary-color);"><?= htmlspecialchars($project['leader_name'] ?: 'None') ?></span>
                </div>
            </div>
        </header>

        <div id="projectStats" class="project-stats-grid" style="margin-bottom: 2.5rem;">
            <!-- Stats will be loaded here -->
        </div>

        <div class="glass-card" style="padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem;">Linked Tasks</h2>
                <!-- We could add a + Task button here specifically for this project -->
            </div>
            
            <div class="task-list" id="projectTasksList" style="display: flex; flex-direction: column; gap: 1rem;">
                <!-- Tasks will be loaded here -->
            </div>
        </div>
    </main>



    <script>
        const projectId = <?= json_encode($project_id) ?>;
        let currentTaskId = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadProjectTasks();
        });

        function loadProjectTasks() {
            fetch(`api/tasks.php?project_id=${projectId}`)
                .then(res => res.json())
                .then(tasks => {
                    const list = document.getElementById('projectTasksList');
                    const statsContainer = document.getElementById('projectStats');
                    list.innerHTML = '';
                    
                    if (tasks.error) {
                        list.innerHTML = `<p style="color: #ef4444; text-align: center;">${tasks.error}</p>`;
                        return;
                    }

                    if (tasks.length === 0) {
                        list.innerHTML = '<p style="color: var(--text-secondary); text-align: center; padding: 2rem;">No tasks linked to this project yet.</p>';
                        updateStats(tasks);
                        return;
                    }

                    tasks.forEach(task => {
                        const card = document.createElement('div');
                        card.className = 'glass-card task-card';
                        card.style.padding = '1rem';
                        
                        let priorityColor = '#94a3b8';
                        if (task.priority === 'urgent') priorityColor = '#ef4444';
                        else if (task.priority === 'high') priorityColor = '#f59e0b';
                        else if (task.priority === 'medium') priorityColor = '#3b82f6';

                        card.innerHTML = `
                            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                                <div style="width: 12px; height: 12px; border-radius: 50%; background: ${task.color};"></div>
                                <div style="flex: 1;">
                                    <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">${task.title}</h4>
                                    <div style="display: flex; gap: 1rem; font-size: 0.8rem; color: var(--text-secondary);">
                                        <span>Assignee: ${task.assigned_to_name || 'Unassigned'}</span>
                                    </div>
                                </div>
                                <span class="priority-badge ${task.priority}">${task.priority}</span>
                                <span class="status-badge ${task.status}">${task.status}</span>
                            </div>
                        `;
                        list.appendChild(card);
                    });

                    updateStats(tasks);
                });
        }

        function updateStats(tasks) {
            const stats = {
                total: tasks.length,
                waiting: tasks.filter(t => t.status === 'waiting').length,
                doing: tasks.filter(t => t.status === 'doing').length,
                finish: tasks.filter(t => t.status === 'finish').length
            };

            const statsContainer = document.getElementById('projectStats');
            statsContainer.innerHTML = `
                <div class="glass-card" style="padding: 1.5rem; text-align: center;">
                    <span style="font-size: 2rem; font-weight: 800;">${stats.total}</span>
                    <p style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.5rem;">Total Tasks</p>
                </div>
                <div class="glass-card" style="padding: 1.5rem; text-align: center;">
                    <span style="font-size: 2rem; font-weight: 800; color: #94a3b8;">${stats.waiting}</span>
                    <p style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.5rem;">Waiting</p>
                </div>
                <div class="glass-card" style="padding: 1.5rem; text-align: center;">
                    <span style="font-size: 2rem; font-weight: 800; color: #3b82f6;">${stats.doing}</span>
                    <p style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.5rem;">Doing</p>
                </div>
                <div class="glass-card" style="padding: 1.5rem; text-align: center;">
                    <span style="font-size: 2rem; font-weight: 800; color: #22c55e;">${stats.finish}</span>
                    <p style="color: var(--text-secondary); font-size: 0.8rem; margin-top: 0.5rem;">Finish</p>
                </div>
            `;
        }


    </script>
</body>
</html>
