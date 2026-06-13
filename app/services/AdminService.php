<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/BookingRepository.php';
require_once __DIR__ . '/../repositories/NotificationRepository.php';
require_once __DIR__ . '/PaymentService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/AdminLogService.php';
require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/../repositories/AdminRepository.php';

class AdminService {
    private $userRepo;
    private $bookingRepo;
    private $notificationRepo;
    private $notificationService;
    private $adminRepo;
    private $paymentService;
    private $adminLogs;

    public function __construct() {
        $connection = Database::connection();

        $this->adminLogs = new AdminLogService();
        $this->userRepo = new UserRepository();
        $this->bookingRepo = new BookingRepository();
        $this->paymentService = new PaymentService();
        $this->notificationRepo = new NotificationRepository($connection);
        $this->notificationService = new NotificationService($this->notificationRepo);
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
        if ($result) {
            $this->adminRepo->logAction($adminId, 'role_changed', 'user', $userId, ['new_role' => $role]);
        }
        return $result;
    }

    public function updateUser(int $userId, string $name, string $email, int $adminId) {
        $result = $this->userRepo->update($userId, $name, $email);
        if ($result) {
            $this->adminRepo->logAction($adminId, 'user_updated', 'user', $userId, ['name' => $name, 'email' => $email]);
        }
        return $result;
    }

    public function deleteUser(int $userId, int $adminId) {
        $result = $this->userRepo->delete($userId);
        if ($result) {
            $this->adminRepo->logAction($adminId, 'user_deleted', 'user', $userId);
        }
        return $result;
    }

    public function getTotalUsers(): int {
        return $this->userRepo->getTotalCount();
    }

    // Booking Management
    public function getDashboardStats(): array {
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
        return $result;
    }

    public function rejectPayment(int $bookingId, string $reason, int $adminId, ?int $paymentId = null) {
        $result = $paymentId
            ? $this->paymentService->reject($paymentId, $adminId, $reason)
            : $this->paymentService->rejectLatestForBooking($bookingId, $adminId, $reason);
        return $result;
    }

    public function updateBookingStatus(int $bookingId, string $status, int $adminId) {
        $previousBooking = $this->bookingRepo->findById($bookingId);
        $wasCancelled = $previousBooking && str_contains(strtolower((string) ($previousBooking['status'] ?? '')), 'cancel');
        $isCancelled = str_contains(strtolower($status), 'cancel');
        $result = $this->bookingRepo->updateStatus($bookingId, $status);
        $booking = $result ? ($this->bookingRepo->findById($bookingId) ?? $previousBooking) : null;
        if ($result && $booking && in_array((string) ($booking['status'] ?? ''), ['confirmed', 'completed'], true)) {
            $this->adminLogs->recordBookingConfirmed($booking, $adminId);
        }
        if ($result && $isCancelled && !$wasCancelled) {
            if ($booking) {
                $this->adminLogs->recordBookingCancelled($booking, $adminId);
                $this->notificationService->notifyBookingCancelled($booking);
            }
        }
        return $result;
    }

    public function getTotalBookings(): int {
        return $this->bookingRepo->getTotalCount();
    }

    // Event Management
    public function createEvent(string $title, string $description, string $eventDate, string $eventTime, 
                              string $location, int $maxParticipants, int $adminId) {
        $this->logLegacyEventAccess('createEvent');
        return 0;
    }

    public function updateEvent(int $eventId, string $title, string $description, string $eventDate, 
                               string $eventTime, string $location, int $maxParticipants, string $status, int $adminId) {
        $this->logLegacyEventAccess('updateEvent');
        return false;
    }

    public function deleteEvent(int $eventId, int $adminId) {
        $this->logLegacyEventAccess('deleteEvent');
        return false;
    }

    public function getAllEvents($limit = 50, $offset = 0) {
        $this->logLegacyEventAccess('getAllEvents');
        return [];
    }

    public function getEventsByStatus(string $status) {
        $this->logLegacyEventAccess('getEventsByStatus');
        return [];
    }

    public function getEventDetail(int $eventId) {
        $this->logLegacyEventAccess('getEventDetail');
        return null;
    }

    // Notification Management
    public function sendNotification(int $userId, string $title, string $message, int $adminId, string $type = 'info', ?string $link = null) {
        $notificationId = $this->notificationService->createForUser($userId, $title, $message, $type, $link);
        if ($notificationId) {
            $description = 'Admin sent notification "' . trim($title) . '" to user #' . $userId . '.';
            $this->adminLogs->recordAdminNotificationSent($adminId, (int) $notificationId, $description);
        }
        return $notificationId;
    }

    public function sendBroadcastNotification(string $title, string $message, int $adminId, string $type = 'info', ?string $link = null) {
        $users = $this->userRepo->findAll();
        $count = 0;
        foreach ($users as $user) {
            if ($this->notificationService->createForUser((int) $user['id'], $title, $message, $type, $link)) {
                $count++;
            }
        }
        if ($count > 0) {
            $this->adminLogs->recordAdminNotificationSent(
                $adminId,
                null,
                'Admin broadcast notification "' . trim($title) . '" to ' . $count . ' recipient' . ($count === 1 ? '' : 's') . '.'
            );
        }
        return $count;
    }

    public function getAllNotifications(int $limit = 100): array {
        return $this->notificationService->allNotifications($limit);
    }

    public function getUserNotifications(int $userId) {
        return $this->notificationService->notificationsForUser($userId);
    }

    public function getUnreadNotifications(int $userId) {
        return $this->notificationRepo->findUnreadByUserId($userId);
    }

    public function markNotificationAsRead(int $notificationId) {
        return $this->notificationRepo->markAsRead($notificationId);
    }

    public function deleteNotification(int $notificationId) {
        return $this->notificationService->delete($notificationId);
    }

    // Analytics & Reports
    public function getRevenueStats(string $period = 'day') {
        return $this->adminRepo->getRevenueStats($period);
    }

    public function getBookingStats() {
        return $this->adminRepo->getBookingStats();
    }

    public function getAdminLogs($limit = 100, array $filters = [], string $sort = 'desc') {
        return $this->adminLogs->logs($filters, (int) $limit, $sort);
    }

    public function getAdminActivityByAdmin(int $adminId, $limit = 50, string $sort = 'desc') {
        return $this->adminLogs->logsForAdmin($adminId, (int) $limit, $sort);
    }

    private function logLegacyEventAccess(string $method): void {
        error_log("AdminService::$method is disabled. Events are represented by booking_variants, sessions, and private_packages in the approved ERD.");
    }
}
