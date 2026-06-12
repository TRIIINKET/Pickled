<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/BookingRepository.php';
require_once __DIR__ . '/../repositories/EventRepository.php';
require_once __DIR__ . '/../repositories/NotificationRepository.php';
require_once __DIR__ . '/PaymentService.php';
require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/../repositories/AdminRepository.php';
require_once __DIR__ . '/../support/DatabaseRedesign.php';

class AdminService {
    private $userRepo;
    private $bookingRepo;
    private $eventRepo;
    private $notificationRepo;
    private $adminRepo;
    private $paymentService;

    public function __construct() {
        if (DatabaseRedesign::active()) {
            $this->userRepo = new UserRepository();
            $this->bookingRepo = new BookingRepository();
            $this->paymentService = new PaymentService();
            $this->eventRepo = null;
            $this->notificationRepo = null;
            $this->adminRepo = null;
            return;
        }

        $connection = Database::connection();

        $this->userRepo = new UserRepository();
        $this->bookingRepo = new BookingRepository();
        $this->paymentService = new PaymentService();
        $this->eventRepo = new EventRepository($connection);
        $this->notificationRepo = new NotificationRepository($connection);
        $this->adminRepo = new AdminRepository($connection);
    }

    // User Management
    public function isAdmin(array $user): bool {
        return $user['role'] === 'admin';
    }

    public function getAllUsers() {
        return $this->userRepo->findAll();
    }

    public function getUsersByRole(string $role) {
        return $this->userRepo->findByRole($role);
    }

    public function updateUserRole(int $userId, string $role, int $adminId) {
        $result = $this->userRepo->updateRole($userId, $role);
        if ($result && $this->adminRepo) {
            $this->adminRepo->logAction($adminId, 'role_changed', 'user', $userId, ['new_role' => $role]);
        }
        return $result;
    }

    public function updateUser(int $userId, string $name, string $email, int $adminId) {
        $result = $this->userRepo->update($userId, $name, $email);
        if ($result && $this->adminRepo) {
            $this->adminRepo->logAction($adminId, 'user_updated', 'user', $userId, ['name' => $name, 'email' => $email]);
        }
        return $result;
    }

    public function deleteUser(int $userId, int $adminId) {
        $result = $this->userRepo->delete($userId);
        if ($result && $this->adminRepo) {
            $this->adminRepo->logAction($adminId, 'user_deleted', 'user', $userId);
        }
        return $result;
    }

    public function getTotalUsers(): int {
        return $this->userRepo->getTotalCount();
    }

    // Booking Management
    public function getDashboardStats(): array {
        if (DatabaseRedesign::active()) {
            return DatabaseRedesign::dashboardStats();
        }

        return $this->adminRepo->getDashboardStats();
    }

    public function getAllBookings($limit = 50, $offset = 0) {
        return $this->bookingRepo->findAll($limit, $offset);
    }

    public function getBookingsByStatus(string $status) {
        return $this->bookingRepo->findByStatus($status);
    }

    public function getBookingsByPaymentStatus(string $status) {
        return $this->bookingRepo->findByPaymentStatus($status);
    }

    public function getBookingDetail(int $bookingId) {
        $booking = $this->bookingRepo->findById($bookingId);
        if ($booking) {
            $booking['items'] = $this->bookingRepo->getBookingItems($bookingId);
            $booking['user'] = $this->userRepo->findById($booking['user_id']);
            $booking['payments'] = $this->paymentService->paymentsForBooking($bookingId);
            $booking['latest_payment'] = $this->paymentService->latestForBooking($bookingId);
        }
        return $booking;
    }

    public function approvePayment(int $bookingId, int $adminId, ?int $paymentId = null, string $remarks = '') {
        $result = $paymentId
            ? $this->paymentService->approve($paymentId, $adminId, $remarks)
            : $this->paymentService->approveLatestForBooking($bookingId, $adminId, $remarks);
        if ($result && $this->adminRepo && $this->notificationRepo) {
            $this->adminRepo->logAction($adminId, 'payment_approved', 'booking', $bookingId);
            $booking = $this->bookingRepo->findById($bookingId);
            $this->notificationRepo->create(
                $booking['user_id'],
                'Payment Approved',
                'Your payment has been approved. Your booking is confirmed!',
                'success'
            );
        }
        return $result;
    }

    public function rejectPayment(int $bookingId, string $reason, int $adminId, ?int $paymentId = null) {
        $result = $paymentId
            ? $this->paymentService->reject($paymentId, $adminId, $reason)
            : $this->paymentService->rejectLatestForBooking($bookingId, $adminId, $reason);
        if ($result && $this->adminRepo && $this->notificationRepo) {
            $this->adminRepo->logAction($adminId, 'payment_rejected', 'booking', $bookingId, ['reason' => $reason]);
            $booking = $this->bookingRepo->findById($bookingId);
            $this->notificationRepo->create(
                $booking['user_id'],
                'Payment Rejected',
                'Your payment has been rejected. ' . $reason,
                'error'
            );
        }
        return $result;
    }

    public function updateBookingStatus(int $bookingId, string $status, int $adminId) {
        $result = $this->bookingRepo->updateStatus($bookingId, $status);
        if ($result && $this->adminRepo) {
            $this->adminRepo->logAction($adminId, 'booking_status_changed', 'booking', $bookingId, ['new_status' => $status]);
        }
        return $result;
    }

    public function getTotalBookings(): int {
        return $this->bookingRepo->getTotalCount();
    }

    // Event Management
    public function createEvent(string $title, string $description, string $eventDate, string $eventTime, 
                              string $location, int $maxParticipants, int $adminId) {
        if (DatabaseRedesign::active()) {
            return 0;
        }

        $eventId = $this->eventRepo->create($title, $description, $eventDate, $eventTime, $location, $maxParticipants, $adminId);
        if ($eventId) {
            $this->adminRepo->logAction($adminId, 'event_created', 'event', $eventId, ['title' => $title]);
        }
        return $eventId;
    }

    public function updateEvent(int $eventId, string $title, string $description, string $eventDate, 
                               string $eventTime, string $location, int $maxParticipants, string $status, int $adminId) {
        if (DatabaseRedesign::active()) {
            return true;
        }

        $result = $this->eventRepo->update($eventId, $title, $description, $eventDate, $eventTime, $location, $maxParticipants, $status);
        if ($result) {
            $this->adminRepo->logAction($adminId, 'event_updated', 'event', $eventId, ['title' => $title]);
        }
        return $result;
    }

    public function deleteEvent(int $eventId, int $adminId) {
        if (DatabaseRedesign::active()) {
            return true;
        }

        $result = $this->eventRepo->delete($eventId);
        if ($result) {
            $this->adminRepo->logAction($adminId, 'event_deleted', 'event', $eventId);
        }
        return $result;
    }

    public function getAllEvents($limit = 50, $offset = 0) {
        if (DatabaseRedesign::active()) {
            return [];
        }

        return $this->eventRepo->findAll($limit, $offset);
    }

    public function getEventsByStatus(string $status) {
        if (DatabaseRedesign::active()) {
            return [];
        }

        return $this->eventRepo->findByStatus($status);
    }

    public function getEventDetail(int $eventId) {
        if (DatabaseRedesign::active()) {
            return null;
        }

        return $this->eventRepo->findById($eventId);
    }

    // Notification Management
    public function sendNotification(int $userId, string $title, string $message, int $adminId, string $type = 'info', ?string $link = null) {
        if (DatabaseRedesign::active()) {
            return 1;
        }

        $notificationId = $this->notificationRepo->create($userId, $title, $message, $type, $link);
        if ($notificationId) {
            $this->adminRepo->logAction($adminId, 'notification_sent', 'notification', $notificationId, ['user_id' => $userId]);
        }
        return $notificationId;
    }

    public function sendBroadcastNotification(string $title, string $message, int $adminId, string $type = 'info') {
        if (DatabaseRedesign::active()) {
            return count(DatabaseRedesign::users());
        }

        $users = $this->userRepo->findAll();
        $count = 0;
        foreach ($users as $user) {
            if ($this->notificationRepo->create($user['id'], $title, $message, $type)) {
                $count++;
            }
        }
        if ($count > 0) {
            $this->adminRepo->logAction($adminId, 'broadcast_sent', 'notification', null, ['recipient_count' => $count]);
        }
        return $count;
    }

    public function getUserNotifications(int $userId) {
        if (DatabaseRedesign::active()) {
            return [];
        }

        return $this->notificationRepo->findByUserId($userId);
    }

    public function getUnreadNotifications(int $userId) {
        if (DatabaseRedesign::active()) {
            return [];
        }

        return $this->notificationRepo->findUnreadByUserId($userId);
    }

    public function markNotificationAsRead(int $notificationId) {
        if (DatabaseRedesign::active()) {
            return true;
        }

        return $this->notificationRepo->markAsRead($notificationId);
    }

    public function deleteNotification(int $notificationId) {
        if (DatabaseRedesign::active()) {
            return true;
        }

        return $this->notificationRepo->delete($notificationId);
    }

    // Analytics & Reports
    public function getRevenueStats(string $period = 'day') {
        if (DatabaseRedesign::active()) {
            return [];
        }

        return $this->adminRepo->getRevenueStats($period);
    }

    public function getBookingStats() {
        if (DatabaseRedesign::active()) {
            return [];
        }

        return $this->adminRepo->getBookingStats();
    }

    public function getAdminLogs($limit = 100) {
        if (DatabaseRedesign::active()) {
            return DatabaseRedesign::adminLogs((int) $limit);
        }

        return $this->adminRepo->getLogs($limit);
    }

    public function getAdminActivityByAdmin(int $adminId, $limit = 50) {
        if (DatabaseRedesign::active()) {
            return DatabaseRedesign::adminLogs((int) $limit);
        }

        return $this->adminRepo->getLogsByAdmin($adminId, $limit);
    }
}
