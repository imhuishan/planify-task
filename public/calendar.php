<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../src/Auth/login.php");
    exit;
}
require_once __DIR__ . '/../config/db.php';

// Fetch users for assignment modal
$users_stmt = $pdo->query("SELECT id, username FROM users");
$users = $users_stmt->fetchAll();

$page_title = "Calendar - Tasker.";
$user_role = $_SESSION['role'] ?? 'member';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
</head>
<body>
    <?php include __DIR__ . '/../templates/sidebar.php'; ?>

    <main class="main-wrapper">
        <header style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 1rem; flex: 1; min-width: 0;">
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <div>
                    <h1 style="font-size: 2.25rem;">Calendar View</h1>
                    <p class="hide-mobile" style="color: var(--text-secondary); margin-top: 0.25rem;">Click a date to add a new task or view your timeline.</p>
                </div>
            </div>
            <div class="calendar-header-actions">
                <?php if ($user_role === 'admin' || $user_role === 'manager'): ?>
                <div class="calendar-viewing-selector">
                    <span style="font-size: 0.85rem; color: var(--text-secondary); font-weight: 500; white-space: nowrap;">Viewing:</span>
                    <select id="userSelector" class="form-control" style="width: 160px; padding: 0.4rem 0.75rem; font-size: 0.85rem;" onchange="changeViewedUser(this.value)">
                        <option value="<?= $_SESSION['user_id'] ?>">Me (<?= htmlspecialchars($_SESSION['username']) ?>)</option>
                        <?php foreach ($users as $u): ?>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <button onclick="openCreateModal()" class="btn btn-primary" style="padding: 0.6rem 1.2rem; white-space: nowrap;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    New Task
                </button>
            </div>
        </header>

        <section class="glass-card" style="padding: 2rem;">
            <div id='calendar'></div>
        </section>
    </main>

    <!-- Create Task Modal -->
    <div id="createModal" class="modal-overlay" style="display: none;">
        <div class="glass-card modal-content" style="max-width: 600px; width: 90%; padding: 2rem; text-align: left;">
            <h2 style="margin-bottom: 1.5rem; font-size: 1.6rem; text-align: center;">Create New Task</h2>
            <form id="createTaskForm">
                <input type="hidden" name="action" value="create_task">
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="margin-bottom: 0.5rem; font-size: 0.9rem;">Title</label>
                    <input type="text" name="title" class="form-control" placeholder="What needs to be done?" required style="padding: 0.75rem 1rem;">
                </div>
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label style="margin-bottom: 0.5rem; font-size: 0.9rem;">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Additional details..." style="padding: 0.75rem 1rem;"></textarea>
                </div>
                <div class="create-modal-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <div style="height: 1.5rem; display: flex; align-items: center; margin-bottom: 0.5rem;">
                            <label style="font-size: 0.9rem;">Task Date</label>
                        </div>
                        <input type="date" name="due_date" id="modal_task_date" class="form-control" style="padding: 0.75rem 1rem;" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <div style="height: 1.5rem; display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <label style="font-size: 0.9rem;">Due Date</label>
                            <label style="font-size: 0.8rem; display: flex; align-items: center; gap: 0.3rem; color: var(--text-secondary); cursor: pointer;">
                                <input type="checkbox" id="tbd_checkbox" onchange="toggleDeadline(this.checked)"> TBD
                            </label>
                        </div>
                        <input type="date" name="deadline" id="modal_deadline" class="form-control" style="padding: 0.75rem 1rem;">
                    </div>
                </div>
                <div class="create-modal-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="margin-bottom: 0.5rem; font-size: 0.9rem;">Priority</label>
                        <select name="priority" class="form-control" style="padding: 0.75rem 1rem;">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="margin-bottom: 0.5rem; font-size: 0.9rem;">Category</label>
                        <select name="category" class="form-control" style="padding: 0.75rem 1rem;">
                            <option value="General">General</option>
                            <option value="Work">Work</option>
                            <option value="Personal">Personal</option>
                            <option value="Bug">Bug</option>
                            <option value="Feature">Feature</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="margin-bottom: 0.5rem; font-size: 0.9rem;">Assign To</label>
                    <select name="assigned_to" class="form-control" style="padding: 0.75rem 1rem;">
                        <option value="">Assign to me</option>
                        <?php foreach ($users as $user): ?>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label style="margin-bottom: 0.5rem; font-size: 0.9rem;">Project (Optional)</label>
                    <select name="project_id" class="form-control project-select" style="padding: 0.75rem 1rem;">
                        <option value="">No Project</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 2; padding: 0.75rem;">Save Task</button>
                    <button type="button" onclick="closeCreateModal()" class="btn btn-outline" style="flex: 1; padding: 0.75rem;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

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
                                    <option value="General">General</option>
                                    <option value="Work">Work</option>
                                    <option value="Personal">Personal</option>
                                    <option value="Bug">Bug</option>
                                    <option value="Feature">Feature</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Project (Optional)</label>
                                <select id="editTaskProject" name="project_id" class="form-control project-select">
                                    <option value="">No Project</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group" style="flex: 1;">
                                <label>Task Date</label>
                                <input type="date" id="editTaskDate" name="due_date" class="form-control">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Due Date</label>
                                <input type="date" id="editTaskDeadline" name="deadline" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea id="editTaskDesc" name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="modal-actions" style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                            <button type="button" class="btn btn-primary" onclick="saveTaskEdits()">Save Changes</button>
                            <button type="button" class="btn btn-outline" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3);" onclick="deleteTask()">Delete Task</button>
                        </div>
                    </form>
                </div>

                <div class="comments-section" style="margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                    <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Comments</h3>
                    <div id="commentsList" style="max-height: 250px; overflow-y: auto; margin-bottom: 1rem; padding-right: 0.5rem;"></div>
                    <div class="add-comment" style="display: flex; gap: 0.5rem;">
                        <input type="text" id="newCommentText" class="form-control" placeholder="Add a comment..." onkeypress="if(event.key === 'Enter') addComment()">
                        <button class="btn btn-primary" onclick="addComment()">Post</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
      let calendar;
      let currentTaskId = null;

      function loadProjectsForModals() {
          fetch('api/projects.php?action=list_projects')
              .then(res => res.json())
              .then(data => {
                  if (data.success) {
                      const selects = document.querySelectorAll('.project-select');
                      selects.forEach(select => {
                          // Clear existing options except the first one
                          while (select.options.length > 1) {
                              select.remove(1);
                          }
                          data.projects.forEach(p => {
                              const opt = document.createElement('option');
                              opt.value = p.id;
                              opt.textContent = p.name;
                              select.appendChild(opt);
                          });
                      });
                  }
              });
      }

      function openCreateModal(dateStr = '') {
        document.getElementById('createModal').style.display = 'flex';
        document.getElementById('createTaskForm').reset();
        
        const tbd = document.getElementById('tbd_checkbox');
        tbd.checked = false;
        toggleDeadline(false);

        if (dateStr) document.getElementById('modal_task_date').value = dateStr;
      }

      function closeCreateModal() {
        document.getElementById('createModal').style.display = 'none';
      }

      function toggleDeadline(isTbd) {
          const dateInput = document.getElementById('modal_deadline');
          if (isTbd) {
              dateInput.value = '';
              dateInput.disabled = true;
              dateInput.style.opacity = '0.5';
          } else {
              dateInput.disabled = false;
              dateInput.style.opacity = '1';
          }
      }

      function openTaskModal(taskId) {
            currentTaskId = taskId;
            document.getElementById('taskModal').style.display = 'flex';
            
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
                        document.getElementById('editTaskDate').value = task.due_date || '';
                        document.getElementById('editTaskDeadline').value = task.deadline || '';
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
                        closeTaskModal();
                        calendar.refetchEvents();
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
            fetch('api/tasks.php', { method: 'POST', body: formData });
        }

        function deleteTask() {
            if (confirm("Are you sure you want to delete this task?")) {
                const formData = new FormData();
                formData.append('action', 'delete_task');
                formData.append('id', currentTaskId);

                fetch('api/tasks.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            closeTaskModal();
                            calendar.refetchEvents();
                        } else {
                            alert('Failed to delete task.');
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
                        loadComments();
                    }
                });
        }

      document.getElementById('createTaskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('api/tasks.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeCreateModal();
                calendar.refetchEvents();
            } else {
                alert('Error creating task: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            alert('Failed to connect to the server. Please check if your database is configured correctly.');
        });
      });


      document.addEventListener('DOMContentLoaded', function() {
        loadProjectsForModals();
        var calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          height: 850,
          expandRows: true,
          headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
          },
          editable: true,
          selectable: true,
          events: 'api/tasks.php',
          dateClick: function(info) {
            openCreateModal(info.dateStr);
          },
          eventClick: function(info) {
            openTaskModal(info.event.id);
          },
          eventContent: function(arg) {
            let status = arg.event.extendedProps.status;
            let baseColor = '#94a3b8';
            if (status === 'doing' || status === 'in-progress') baseColor = '#3b82f6';
            if (status === 'finish' || status === 'completed') baseColor = '#22c55e';
            
            return {
              html: `<div class="calendar-event" style="background: rgba(15, 23, 42, 0.7); border-left: 4px solid ${baseColor}; color: white; padding: 4px 8px;">
                       <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                         <div style="font-size: 0.75rem; font-weight: 600;">${arg.event.title}</div>
                       </div>
                     </div>`
            };
          },
          eventDrop: function(info) {
             const formData = new FormData();
             formData.append('action', 'update_date');
             formData.append('id', info.event.id);
             formData.append('due_date', info.event.startStr.split('T')[0]);

             fetch('api/tasks.php', {
                 method: 'POST',
                 body: formData
             })
             .then(response => response.json())
             .then(data => {
                 if (!data.success) {
                     alert('Failed to update task date.');
                     info.revert();
                 }
             });
          }
        });
        calendar.render();
      });
      function changeViewedUser(userId) {
          calendar.removeAllEventSources();
          calendar.addEventSource({
              url: 'api/tasks.php',
              extraParams: {
                  user_id: userId
              }
          });
      }
    </script>
</body>
</html>
