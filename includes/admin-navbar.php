<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$activePage = $activePage ?? '';

require_once __DIR__ . '/admin-paths.php';
?>
<nav class="admin-navbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <h1>Pickled Admin</h1>
        </div>
        
        <ul class="navbar-menu">
            <li><a href="<?php echo pickled_admin_url('admin-dashboard.php'); ?>" class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
            
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">Manage</a>
                <ul class="dropdown-menu">
                    <li><a href="<?php echo pickled_admin_url('manage-bookings.php'); ?>">Bookings</a></li>
                    <li><a href="<?php echo pickled_admin_url('manage-users.php'); ?>">Users</a></li>
                    <li><a href="<?php echo pickled_admin_url('manage-events.php'); ?>">Events</a></li>
                </ul>
            </li>
            
            <li><a href="<?php echo pickled_admin_url('notifications.php'); ?>" class="<?php echo $activePage === 'notifications' ? 'active' : ''; ?>">Notifications</a></li>
            <li><a href="<?php echo pickled_admin_url('reports.php'); ?>" class="<?php echo $activePage === 'reports' ? 'active' : ''; ?>">Reports</a></li>
        </ul>
        
        <div class="navbar-user">
            <span>
                <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Admin'); ?>
            </span>
            <a href="<?php echo pickled_admin_url('admin-logout.php'); ?>" class="btn btn-secondary btn-sm">Logout</a>
        </div>
    </div>
</nav>
