<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/PrivatePackageRepository.php';

final class PrivatePackageService
{
    private const STATUSES = ['active', 'inactive', 'archived'];

    public function __construct(private readonly PrivatePackageRepository $packages = new PrivatePackageRepository()) {}

    public function availablePackages(): array
    {
        return $this->packages->active();
    }

    public function allPackages(?string $status = null, string $search = '', int $limit = 100): array
    {
        return $this->packages->all($status ? $this->status($status) : null, $search, $limit);
    }

    public function packageById(int $id): ?array
    {
        return $id > 0 ? $this->packages->findById($id) : null;
    }

    public function packagesForCoach(int $coachUserId, bool $activeOnly = false): array
    {
        return $coachUserId > 0 ? $this->packages->forCoachUser($coachUserId, $activeOnly) : [];
    }

    public function coachProfiles(): array
    {
        return $this->packages->coachProfiles();
    }

    public function create(array $input, int $adminId): int
    {
        $this->assertAdmin($adminId);
        return $this->packages->create($this->data($input));
    }

    public function update(int $id, array $input, int $adminId): bool
    {
        $this->assertAdmin($adminId);
        if ($id <= 0 || !$this->packages->findById($id)) {
            throw new RuntimeException('Private package was not found.');
        }

        return $this->packages->update($id, $this->data($input));
    }

    public function setStatus(int $id, string $status, int $adminId): bool
    {
        $this->assertAdmin($adminId);
        if ($id <= 0 || !$this->packages->findById($id)) {
            throw new RuntimeException('Private package was not found.');
        }

        return $this->packages->setStatus($id, $this->status($status));
    }

    private function data(array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $duration = trim((string) ($input['duration'] ?? ''));
        $price = (float) ($input['price'] ?? -1);
        $coachProfileId = (int) ($input['coach_profile_id'] ?? 0);

        if ($title === '' || $description === '' || $duration === '') {
            throw new RuntimeException('Title, description, and duration are required.');
        }
        if ($price < 0) {
            throw new RuntimeException('Package price must be zero or greater.');
        }
        if ($coachProfileId <= 0 || !$this->packages->coachProfileById($coachProfileId)) {
            throw new RuntimeException('A valid coach assignment is required.');
        }

        return [
            'title' => substr($title, 0, 160),
            'description' => substr($description, 0, 4000),
            'price' => $price,
            'duration' => substr($duration, 0, 80),
            'coach_profile_id' => $coachProfileId,
            'status' => $this->status((string) ($input['status'] ?? 'active')),
        ];
    }

    private function status(string $status): string
    {
        $status = strtolower(trim($status));
        return in_array($status, self::STATUSES, true) ? $status : 'active';
    }

    private function assertAdmin(int $adminId): void
    {
        if ($adminId <= 0) {
            throw new RuntimeException('Administrator authorization is required.');
        }
    }
}
