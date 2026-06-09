<?php
$pageTitle = 'Manage Bookings';
$activePage = 'bookings';
require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../app/services/AdminService.php';

$adminService = new AdminService();
$filter = $_GET['filter'] ?? 'all';
$bookingId = $_GET['id'] ?? null;
$successMsg = '';
$errorMsg = '';

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = 'Invalid form submission';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['booking_id'] ?? 0);
        
        if ($action === 'approve_payment' && $id) {
            if ($adminService->approvePayment($id, $_SESSION['user']['id'])) {
                $successMsg = 'Payment approved successfully';
            } else {
                $errorMsg = 'Failed to approve payment';
            }
        } elseif ($action === 'reject_payment' && $id) {
            $reason = $_POST['reason'] ?? 'Payment rejected by admin';
            if ($adminService->rejectPayment($id, $reason, $_SESSION['user']['id'])) {
                $successMsg = 'Payment rejected and notification sent';
            } else {
                $errorMsg = 'Failed to reject payment';
            }
        } elseif ($action === 'update_status' && $id) {
            $status = $_POST['status'] ?? '';
            if ($adminService->updateBookingStatus($id, $status, $_SESSION['user']['id'])) {
                $successMsg = 'Booking status updated';
                $bookingId = null;
            } else {
                $errorMsg = 'Failed to update status';
            }
        }
    }
}

// Get bookings based on filter
$bookings = [];
if ($filter === 'all') {
    $bookings = $adminService->getAllBookings(50, 0);
} elseif ($filter === 'pending') {
    $bookings = $adminService->getBookingsByPaymentStatus('Pending');
} elseif ($filter === 'completed') {
    $bookings = $adminService->getBookingsByPaymentStatus('Completed');
} elseif ($filter === 'rejected') {
    $bookings = $adminService->getBookingsByPaymentStatus('Rejected');
}

$currentBooking = null;
if ($bookingId) {
    $currentBooking = $adminService->getBookingDetail((int) $bookingId);
}
?>

<?php require_once __DIR__ . '/../includes/admin-navbar.php'; ?>

<main class="admin-main">
    <div class="container">
        <div class="admin-header">
            <h1>Manage Bookings</h1>
            <p class="admin-subtitle">View and manage all bookings and payments</p>
        </div>
        
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($successMsg); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="filter-group">
            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
            <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
            <a href="?filter=completed" class="btn <?php echo $filter === 'completed' ? 'btn-primary' : 'btn-secondary'; ?>">Completed</a>
            <a href="?filter=rejected" class="btn <?php echo $filter === 'rejected' ? 'btn-primary' : 'btn-secondary'; ?>">Rejected</a>
        </div>
        
        <div class="grid-2">
            <!-- Bookings List -->
            <section>
                <h2>Bookings List</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                                <?php $bookingStatusKey = pickled_booking_status_key($booking['status']); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['reference']); ?></td>
                                    <td>₱<?php echo number_format($booking['total'], 2); ?></td>
                                    <td><span class="badge badge-<?php echo htmlspecialchars($bookingStatusKey); ?>"><?php echo htmlspecialchars(pickled_booking_status_label($booking['status'])); ?></span></td>
                                    <td><span class="badge badge-payment-<?php echo strtolower(str_replace(' ', '-', $booking['payment_status'])); ?>"><?php echo htmlspecialchars($booking['payment_status']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                                    <td>
                                        <a href="?id=<?php echo $booking['id']; ?>&filter=<?php echo urlencode($filter); ?>" class="btn btn-primary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Booking Detail -->
            <?php if ($currentBooking): ?>
                <section>
                    <h2>Booking Details</h2>
                    <div class="detail-box">
                        <p><strong>Reference:</strong> <?php echo htmlspecialchars($currentBooking['reference']); ?></p>
                        <p><strong>User ID:</strong> <?php echo $currentBooking['user_id']; ?></p>
                        <p><strong>Total:</strong> ₱<?php echo number_format($currentBooking['total'], 2); ?></p>
                        <p><strong>Status:</strong> <span class="badge badge-<?php echo htmlspecialchars(pickled_booking_status_key($currentBooking['status'])); ?>"><?php echo htmlspecialchars(pickled_booking_status_label($currentBooking['status'])); ?></span></p>
                        <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($currentBooking['payment_status']); ?></p>
                        <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($currentBooking['created_at'])); ?></p>
                        
                        <h3>Booking Items</h3>
                        <ul>
                            <?php foreach ($currentBooking['items'] as $item): ?>
                                <li><?php echo htmlspecialchars($item['name']); ?> - ₱<?php echo number_format($item['unit_price'], 2); ?> x <?php echo $item['quantity']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php if ($currentBooking['payment_status'] === 'Pending'): ?>
                            <form method="POST" class="form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
                                <input type="hidden" name="booking_id" value="<?php echo $currentBooking['id']; ?>">
                                
                                <div class="form-group">
                                    <label>Approve Payment</label>
                                    <button type="submit" name="action" value="approve_payment" class="btn btn-success">Approve</button>
                                </div>
                            </form>
                            
                            <form method="POST" class="form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(pickled_csrf_token()); ?>">
                                <input type="hidden" name="booking_id" value="<?php echo $currentBooking['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="reason">Rejection Reason</label>
                                    <textarea id="reason" name="reason" rows="3"></textarea>
                                </div>
                                <button type="submit" name="action" value="reject_payment" class="btn btn-danger">Reject</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
