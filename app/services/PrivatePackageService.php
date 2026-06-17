<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/PrivatePackageRepository.php';
require_once __DIR__ . '/../../includes/validation.php';

final class PrivatePackageService
{
    private const STATUSES = ['active', 'inactive', 'archived'];
    private const CATEGORIES = [
        'Birthday Event',
        'Corporate Event',
        'Team Building',
        'Family Package',
        'Private Coaching',
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
        $title = validateText($input['title'] ?? '', true, 80);
        $category = trim((string) ($input['category'] ?? 'Private Coaching'));
        $description = validateText($input['description'] ?? '', true, 1000);
        $duration = validateText($input['duration'] ?? '', true, 80);
        $price = (float) validateMoney($input['price'] ?? -1);
        $capacity = (int) ($input['capacity'] ?? 0);
        $coachProfileId = (int) ($input['coach_profile_id'] ?? 0);
        $requiredCoach = in_array((string) ($input['required_coach'] ?? '1'), ['1', 'yes', 'required'], true);
        $slug = trim((string) ($input['slug'] ?? ''));
        $sortOrder = (int) ($input['sort_order'] ?? 0);

        if (!in_array($category, self::CATEGORIES, true)) {
            throw new RuntimeException('Choose a valid package category.');
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
            'title' => $title,
            'category' => $category,
            'description' => $description,
            'price' => $price,
            'duration' => $duration,
            'capacity' => $capacity,
            'coach_profile_id' => $coachProfileId > 0 ? $coachProfileId : null,
            'required_coach' => $requiredCoach,
            'slug' => $this->slug($slug !== '' ? $slug : $title),
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
        if ($slug === '' || strlen($slug) > 150 || !preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new RuntimeException('Slug must use lowercase letters, numbers, and hyphens only.');
        }
        return $slug;
    }
}
