<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/PrivatePackageRepository.php';

final class PrivatePackageService
{
    private const STATUSES = ['active', 'inactive', 'archived'];
    private const CATEGORIES = [
        'Private Coaching',
        'Birthday Event',
        'Corporate Event',
        'Family Package',
        'Team Building',
        'School Activity',
        'Custom Package',
    ];

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
        $category = trim((string) ($input['category'] ?? 'Private Coaching'));
        $description = trim((string) ($input['description'] ?? ''));
        $duration = trim((string) ($input['duration'] ?? ''));
        $price = (float) ($input['price'] ?? -1);
        $capacity = (int) ($input['capacity'] ?? 0);
        $coachProfileId = (int) ($input['coach_profile_id'] ?? 0);
        $requiredCoach = in_array((string) ($input['required_coach'] ?? '1'), ['1', 'yes', 'required'], true);
        $slug = trim((string) ($input['slug'] ?? ''));
        $sortOrder = (int) ($input['sort_order'] ?? 0);

        if ($title === '' || $description === '' || $duration === '') {
            throw new RuntimeException('Title, description, and duration are required.');
        }
        if (!in_array($category, self::CATEGORIES, true)) {
            throw new RuntimeException('Choose a valid package category.');
        }
        if ($price < 0) {
            throw new RuntimeException('Package price must be zero or greater.');
        }
        if ($capacity < 0) {
            throw new RuntimeException('Capacity must be zero or greater.');
        }
        if ($coachProfileId > 0 && !$this->packages->coachProfileById($coachProfileId)) {
            throw new RuntimeException('Choose a valid assigned coach.');
        }
        if ($requiredCoach && $coachProfileId <= 0) {
            throw new RuntimeException('A coach assignment is required for coach-required packages.');
        }

        return [
            'title' => substr($title, 0, 160),
            'category' => $category,
            'description' => substr($description, 0, 4000),
            'price' => $price,
            'duration' => substr($duration, 0, 80),
            'capacity' => $capacity,
            'coach_profile_id' => $coachProfileId > 0 ? $coachProfileId : null,
            'required_coach' => $requiredCoach,
            'slug' => substr($slug !== '' ? $slug : $this->slug($title), 0, 190),
            'sort_order' => $sortOrder,
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

    private function slug(string $value): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value), '-'));
        return $slug !== '' ? $slug : 'private-package';
    }
}
