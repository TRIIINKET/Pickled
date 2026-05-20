<?php
$pageTitle = 'Admin Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/_header.php';
require_once __DIR__ . '/../backend/services/AdminService.php';

$adminService = new AdminService();
$stats = $adminService->getDashboardStats();
$recentBookings = $adminService->getAllBookings(10, 0);
?>

<?php require_once __DIR__ . '/includes/_navbar.php'; ?>

<main class="admin-main">
    <div class="container">
        <div class="admin-header">
            <h1>Dashboard</h1>
            <p class="admin-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></p>
        </div>
        
        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="stat-value"><?php echo $stats['total_users'] ?? 0; ?></div>
                <p class="stat-label">Active users</p>
            </div>
            
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <div class="stat-value"><?php echo $stats['total_bookings'] ?? 0; ?></div>
                <p class="stat-label">All bookings</p>
            </div>
            
            <div class="stat-card">
                <h3>Revenue</h3>
                <div class="stat-value">₱<?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></div>
                <p class="stat-label">Total revenue</p>
            </div>
            
            <div class="stat-card">
                <h3>Pending Payments</h3>
                <div class="stat-value"><?php echo $stats['pending_payments'] ?? 0; ?></div>
                <p class="stat-label">Awaiting approval</p>
            </div>
            
            <div class="stat-card">
                <h3>Total Events</h3>
                <div class="stat-value"><?php echo $stats['total_events'] ?? 0; ?></div>
                <p class="stat-label">Upcoming events</p>
            </div>
            
            <div class="stat-card">
                <h3>Total Courts</h3>
                <div class="stat-value"><?php echo $stats['total_courts'] ?? 0; ?></div>
                <p class="stat-label">Available courts</p>
            </div>
        </div>
        
        <!-- Recent Bookings -->
        <section class="admin-section">
            <h2>Recent Bookings</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>User</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['reference']); ?></td>
                                <td><?php echo htmlspecialchars($booking['user_id']); ?></td>
                                <td>₱<?php echo number_format($booking['total'], 2); ?></td>
                                <td><span class="badge badge-<?php echo strtolower($booking['status']); ?>"><?php echo htmlspecialchars($booking['status']); ?></span></td>
                                <td><span class="badge badge-payment-<?php echo strtolower(str_replace(' ', '-', $booking['payment_status'])); ?>"><?php echo htmlspecialchars($booking['payment_status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                                <td>
                                    <a href="manage-bookings.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/includes/_footer.php'; ?>
