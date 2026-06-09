<?php
$pageTitle = 'Reports & Analytics';
$activePage = 'reports';
require_once __DIR__ . '/../includes/layouts/admin-header.php';
require_once __DIR__ . '/../app/services/AdminService.php';

$adminService = new AdminService();
$period = $_GET['period'] ?? 'day';

$stats = $adminService->getDashboardStats();
$bookingStats = $adminService->getBookingStats();
$revenueStats = $adminService->getRevenueStats($period);
?>

<?php require_once __DIR__ . '/../includes/layouts/admin-navbar.php'; ?>

<main class="admin-main">
    <div class="container">
        <div class="admin-header">
            <h1>Reports & Analytics</h1>
            <p class="admin-subtitle">View system analytics and reports</p>
        </div>
        
        <!-- Period Selector -->
        <div class="report-controls">
            <div class="btn-group">
                <a href="?period=day" class="btn <?php echo $period === 'day' ? 'btn-primary' : 'btn-secondary'; ?>">Daily</a>
                <a href="?period=week" class="btn <?php echo $period === 'week' ? 'btn-primary' : 'btn-secondary'; ?>">Weekly</a>
                <a href="?period=month" class="btn <?php echo $period === 'month' ? 'btn-primary' : 'btn-secondary'; ?>">Monthly</a>
            </div>
        </div>
        
        <!-- Overview Stats -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="stat-value"><?php echo $stats['total_users'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <div class="stat-value"><?php echo $stats['total_bookings'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="stat-value">₱<?php echo number_format($stats['total_revenue'] ?? 0, 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Payments</h3>
                <div class="stat-value"><?php echo $stats['pending_payments'] ?? 0; ?></div>
            </div>
        </div>
        
        <div class="grid-2">
            <!-- Booking Status Distribution -->
            <section>
                <h2>Booking Status Distribution</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totalBookings = $stats['total_bookings'] ?? 1;
                            foreach ($bookingStats as $stat):
                                $bookingStatusKey = pickled_booking_status_key($stat['status']);
                            ?>
                                <tr>
                                    <td><span class="badge badge-<?php echo htmlspecialchars($bookingStatusKey); ?>"><?php echo htmlspecialchars(pickled_booking_status_label($stat['status'])); ?></span></td>
                                    <td><?php echo $stat['count']; ?></td>
                                    <td><?php echo number_format(($stat['count'] / $totalBookings) * 100, 1); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Revenue by Period -->
            <section>
                <h2>Revenue by <?php echo ucfirst($period); ?></h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Revenue</th>
                                <th>Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenueStats as $stat): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['period']); ?></td>
                                    <td>₱<?php echo number_format($stat['revenue'] ?? 0, 2); ?></td>
                                    <td><?php echo $stat['bookings'] ?? 0; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/layouts/admin-footer.php'; ?>
