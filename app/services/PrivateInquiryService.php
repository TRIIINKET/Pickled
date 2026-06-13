<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/PrivateInquiryRepository.php';
require_once __DIR__ . '/PrivatePackageService.php';

final class PrivateInquiryService
{
    private const STATUSES = ['new', 'in_review', 'responded', 'closed', 'cancelled'];

    public function __construct(
        private readonly PrivateInquiryRepository $inquiries = new PrivateInquiryRepository(),
        private readonly PrivatePackageService $packages = new PrivatePackageService()
    ) {}

    public function submit(int $userId, int $packageId, string $message): int
    {
        $user = $this->inquiries->userById($userId);
        if (!$user || ($user['role'] ?? '') !== 'player') {
            throw new RuntimeException('A player account is required to submit private package inquiries.');
        }

        $package = $this->packages->packageById($packageId);
        if (!$package || ($package['status'] ?? '') !== 'active') {
            throw new RuntimeException('Selected private package is unavailable.');
        }

        $message = $this->message($message);

        return $this->inquiries->create([
            'user_id' => $userId,
            'private_package_id' => $packageId,
            'message' => $message,
            'status' => 'new',
        ]);
    }

    public function inquiriesForUser(int $userId, int $limit = 50): array
    {
        return $userId > 0 ? $this->inquiries->forUser($userId, $limit) : [];
    }

    public function inquiriesForCoach(int $coachUserId, int $limit = 50): array
    {
        return $coachUserId > 0 ? $this->inquiries->forCoachUser($coachUserId, $limit) : [];
    }

    public function allInquiries(?string $status = null, string $search = '', int $limit = 100): array
    {
        return $this->inquiries->all($status ? $this->status($status) : null, $search, $limit);
    }

    public function respond(int $inquiryId, string $response, string $status, int $adminId): bool
    {
        $this->assertAdmin($adminId);
        if ($inquiryId <= 0 || !$this->inquiries->findById($inquiryId)) {
            throw new RuntimeException('Private inquiry was not found.');
        }

        $response = trim($response);
        if ($response === '') {
            throw new RuntimeException('Admin response is required.');
        }

        return $this->inquiries->respond($inquiryId, $this->status($status), substr($response, 0, 4000));
    }

    public function setStatus(int $inquiryId, string $status, int $adminId): bool
    {
        $this->assertAdmin($adminId);
        if ($inquiryId <= 0 || !$this->inquiries->findById($inquiryId)) {
            throw new RuntimeException('Private inquiry was not found.');
        }

        return $this->inquiries->setStatus($inquiryId, $this->status($status));
    }

    private function message(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            throw new RuntimeException('Please include a message for the team.');
        }

        return substr($message, 0, 4000);
    }

    private function status(string $status): string
    {
        $status = strtolower(trim($status));
        return in_array($status, self::STATUSES, true) ? $status : 'new';
    }

    private function assertAdmin(int $adminId): void
    {
        if ($adminId <= 0) {
            throw new RuntimeException('Administrator authorization is required.');
        }
    }
}
