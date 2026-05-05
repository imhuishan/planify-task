<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../src/Auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'member';
$page_title = "Projects - Tasker.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../templates/sidebar.php'; ?>

    <main class="main-wrapper">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem; flex: 1; min-width: 0;">
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <div>
                    <h1 style="font-size: 2.25rem;">Projects</h1>
                    <p class="hide-mobile" style="color: var(--text-secondary); margin-top: 0.5rem;">Manage your team projects and collaborations.</p>
                </div>
            </div>
            <?php if ($user_role === 'admin'): ?>
                <button class="btn btn-primary" onclick="openCreateProjectModal()" style="white-space: nowrap;">+ New Project</button>
            <?php endif; ?>
        </header>

        <div id="projectsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">
            <!-- Projects will be loaded here -->
        </div>
    </main>

    <!-- Create Project Modal -->
    <div id="createProjectModal" class="modal-overlay" onclick="closeModal('createProjectModal')">
        <div class="glass-card modal-content" onclick="event.stopPropagation()" style="width: 500px;">
            <h2>Create New Project</h2>
            <form id="createProjectForm" onsubmit="handleCreateProject(event)">
                <input type="hidden" name="action" value="create_project">
                <div class="form-group" style="text-align: left; margin-top: 1.5rem;">
                    <label>Project Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Website Redesign">
                </div>
                <div class="form-group" style="text-align: left;">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="What is this project about?"></textarea>
                </div>
                <div class="form-group" style="text-align: left;">
                    <label>Project Leader</label>
                    <select name="project_leader_id" id="leaderSelect" class="form-control">
                        <option value="">Select a Leader</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn" style="flex: 1; background: rgba(255,255,255,0.05);" onclick="closeModal('createProjectModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Create Project</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div id="addMemberModal" class="modal-overlay" onclick="closeModal('addMemberModal')">
        <div class="glass-card modal-content" onclick="event.stopPropagation()" style="width: 400px;">
            <h2>Add Team Member</h2>
            <form id="addMemberForm" onsubmit="handleAddMember(event)">
                <input type="hidden" name="action" value="add_member">
                <input type="hidden" name="project_id" id="targetProjectId">
                <div class="form-group" style="text-align: left; margin-top: 1.5rem;">
                    <label>Select User</label>
                    <select name="user_id" id="memberSelect" class="form-control" required>
                        <option value="">Choose a member</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn" style="flex: 1; background: rgba(255,255,255,0.05);" onclick="closeModal('addMemberModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Add Member</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadProjects();
            loadUsers();
        });

        function loadProjects() {
            fetch('api/projects.php?action=list_projects')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const grid = document.getElementById('projectsGrid');
                        grid.innerHTML = '';
                        data.projects.forEach(project => {
                            const card = document.createElement('div');
                            card.className = 'glass-card';
                            card.style.padding = '1.5rem';
                            card.style.cursor = 'pointer';
                            card.onclick = (e) => {
                                if (e.target.tagName !== 'BUTTON') {
                                    window.location.href = `project_details.php?id=${project.id}`;
                                }
                            };
                            card.innerHTML = `
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <h3 style="font-size: 1.25rem;">${project.name}</h3>
                                    <span class="category-tag">${project.leader_name || 'No Leader'}</span>
                                </div>
                                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">${project.description || 'No description provided.'}</p>
                                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                                    <span style="font-size: 0.8rem; color: var(--text-secondary);">Created: ${new Date(project.created_at).toLocaleDateString()}</span>
                                    ${project.can_add_members ? `<button class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="openAddMemberModal(${project.id})">+ Member</button>` : ''}
                                </div>
                            `;
                            grid.appendChild(card);
                        });
                    }
                });
        }

        function loadUsers() {
            fetch('api/projects.php?action=get_users')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const leaderSelect = document.getElementById('leaderSelect');
                        const memberSelect = document.getElementById('memberSelect');
                        data.users.forEach(user => {
                            const opt = `<option value="${user.id}">${user.username} (${user.role})</option>`;
                            leaderSelect.innerHTML += opt;
                            memberSelect.innerHTML += opt;
                        });
                    }
                });
        }

        function openCreateProjectModal() {
            document.getElementById('createProjectModal').style.display = 'flex';
        }

        function openAddMemberModal(projectId) {
            document.getElementById('targetProjectId').value = projectId;
            document.getElementById('addMemberModal').style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        function handleCreateProject(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            fetch('api/projects.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeModal('createProjectModal');
                        loadProjects();
                        e.target.reset();
                    } else {
                        alert(data.error);
                    }
                });
        }

        function handleAddMember(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            fetch('api/projects.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeModal('addMemberModal');
                        alert('Member added successfully!');
                        e.target.reset();
                    } else {
                        alert(data.error);
                    }
                });
        }
    </script>
</body>
</html>
