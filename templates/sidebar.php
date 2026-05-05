<?php
$current_script = $_SERVER['PHP_SELF'];
$is_in_src = strpos($current_script, '/src/') !== false;

$public_path = $is_in_src ? '../../public/' : '';
$src_path = $is_in_src ? '' : '../src/Auth/';

$current_page = basename($current_script);
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="mainSidebar">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem;">
        <a href="<?= $public_path ?>index.php" class="sidebar-logo" style="margin-bottom: 0;">Tasker<span class="gradient-text">.</span></a>
        <button class="mobile-toggle" id="closeSidebar" style="display: none; color: var(--text-secondary);">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    
    <ul class="nav-links">
        <li>
            <a href="<?= $public_path ?>index.php" class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                Dashboard
            </a>
        </li>
        <li>
            <a href="<?= $public_path ?>calendar.php" class="nav-link <?= $current_page == 'calendar.php' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Calendar
            </a>
        </li>
        <li>
            <a href="<?= $public_path ?>projects.php" class="nav-link <?= $current_page == 'projects.php' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                Projects
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <a href="<?= $is_in_src ? '../Auth/logout.php' : '../src/Auth/logout.php' ?>" class="nav-link" style="color: #ef4444;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1-2 2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Logout
        </a>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('mainSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const closeBtn = document.getElementById('closeSidebar');
    
    // Function to toggle sidebar
    window.toggleSidebar = function() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    };

    if (overlay) overlay.addEventListener('click', toggleSidebar);
    if (closeBtn) closeBtn.addEventListener('click', toggleSidebar);

    // Show close button only on small screens (handled by CSS, but good to ensure)
    if (window.innerWidth <= 992) {
        closeBtn.style.display = 'block';
    }
    
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
            closeBtn.style.display = 'none';
        } else {
            closeBtn.style.display = 'block';
        }
    });
});
</script>
