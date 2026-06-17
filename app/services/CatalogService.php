<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/CatalogRepository.php';
require_once __DIR__ . '/AdminLogService.php';
require_once __DIR__ . '/../../includes/validation.php';

final class CatalogService
{
    public function __construct(
        private readonly CatalogRepository $catalog = new CatalogRepository(),
        private readonly AdminLogService $adminLogs = new AdminLogService()
    ) {}

    public function courts(bool $includeInactive = false): array
    {
        return $this->catalog->courts($includeInactive);
    }

    public function courtBySlug(string $slug, bool $includeInactive = false): ?array
    {
        return $this->catalog->findCourtBySlug($slug, $includeInactive);
    }

    public function createCourt(array $input, ?int $adminId = null): int
    {
        $data = $this->courtData($input);
        $courtId = $this->catalog->createCourt($data);
        $this->adminLogs->recordCourtCreated($this->adminId($input, $adminId), $courtId, (string) $data['name']);
        return $courtId;
    }

    public function updateCourt(int $id, array $input, ?int $adminId = null): bool
    {
        if ($id <= 0) {
            throw new RuntimeException('Court is required.');
        }

        $data = $this->courtData($input);
        $updated = $this->catalog->updateCourt($id, $data);
        if ($updated) {
            $this->adminLogs->recordCourtUpdated($this->adminId($input, $adminId), $id, (string) $data['name']);
        }
        return $updated;
    }

    public function setCourtStatus(int $id, string $status, ?int $adminId = null): bool
    {
        if ($id <= 0) {
            throw new RuntimeException('Court is required.');
        }

        $status = $this->status($status);
        $updated = $this->catalog->setCourtStatus($id, $status);
        if ($updated) {
            if ($status === 'active') {
                $this->adminLogs->recordCourtUpdated((int) ($adminId ?? 0), $id, 'Court #' . $id);
            } else {
                $this->adminLogs->recordCourtDisabled((int) ($adminId ?? 0), $id, $status);
            }
        }
        return $updated;
    }

    public function variantsForCourtSlug(string $courtSlug, bool $includeInactive = false): array
    {
        return $this->catalog->variantsForCourtSlug($courtSlug, $includeInactive);
    }

    public function socialVariants(bool $includeInactive = false): array
    {
        return $this->catalog->socialVariants($includeInactive);
    }

    public function variants(bool $includeInactive = false): array
    {
        return $this->catalog->variants($includeInactive);
    }

    public function createVariant(array $input, ?int $adminId = null): int
    {
        $data = $this->variantData($input);
        $variantId = $this->catalog->createVariant($data);
        $this->adminLogs->recordVariantCreated($this->adminId($input, $adminId), $variantId, (string) $data['name']);
        return $variantId;
    }

    public function updateVariant(int $id, array $input, ?int $adminId = null): bool
    {
        if ($id <= 0) {
            throw new RuntimeException('Booking variant is required.');
        }

        $data = $this->variantData($input);
        $updated = $this->catalog->updateVariant($id, $data);
        if ($updated) {
            $this->adminLogs->recordVariantUpdated($this->adminId($input, $adminId), $id, (string) $data['name']);
        }
        return $updated;
    }

    public function setVariantActive(int $id, bool $active, ?int $adminId = null): bool
    {
        if ($id <= 0) {
            throw new RuntimeException('Booking variant is required.');
        }

        $updated = $this->catalog->setVariantActive($id, $active);
        if ($updated) {
            $this->adminLogs->recordVariantUpdated((int) ($adminId ?? 0), $id, 'Variant #' . $id);
        }
        return $updated;
    }

    private function courtData(array $input): array
    {
        $name = validateText($input['name'] ?? '', true, 80);
        $slug = $this->slug((string) ($input['slug'] ?? $name));
        if ($name === '' || $slug === '') {
            throw new RuntimeException('Court name and slug are required.');
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'status' => $this->status((string) ($input['status'] ?? 'active')),
            'description' => validateText($input['description'] ?? '', false, 1000) ?: null,
            'base_price' => (float) validateMoney($input['base_price'] ?? 0),
            'capacity' => validatePositiveInt($input['capacity'] ?? 1, null, 'Please enter a valid number of players.'),
            'operating_hours' => validateText($input['operating_hours'] ?? '', false, 80) ?: null,
            'court_type' => validateText($input['court_type'] ?? '', false, 80) ?: null,
        ];
    }

    private function variantData(array $input): array
    {
        $name = validateText($input['name'] ?? '', true, 80);
        $slug = $this->slug((string) ($input['slug'] ?? $name));
        $category = $this->category((string) ($input['category'] ?? ''));
        $duration = validateText($input['duration_label'] ?? '', true, 80);
        $price = (float) validateMoney($input['price'] ?? -1);
        $participants = validatePositiveInt($input['participants_limit'] ?? 0, null, 'Please enter a valid number of players.');
        $capacity = validatePositiveInt($input['capacity'] ?? 0, null, 'Please enter a valid number of players.');
        $courtId = (int) ($input['court_id'] ?? 0);
        $pricingType = $this->pricingType((string) ($input['pricing_type'] ?? ''));

        if ($courtId <= 0 || $name === '' || $slug === '' || $category === '' || $duration === '') {
            throw new RuntimeException('Court, service name, slug, category, and duration are required.');
        }
        if ($price <= 0 || $participants <= 0 || $capacity <= 0) {
            throw new RuntimeException('Price, participants, and capacity must be greater than zero.');
        }

        return [
            'court_id' => $courtId,
            'slug' => $slug,
            'name' => $name,
            'description' => validateText($input['description'] ?? '', false, 1000) ?: null,
            'category' => $category,
            'duration_label' => $duration,
            'price' => $price,
            'pricing_type' => $pricingType,
            'participants_limit' => $participants,
            'coach_required' => $this->coachRequired((string) ($input['coach_required'] ?? 'no')),
            'capacity' => $capacity,
            'image' => validateText($input['image'] ?? '', false, 255) ?: null,
            'active' => !empty($input['active']),
            'sort_order' => max(0, (int) ($input['sort_order'] ?? 0)),
        ];
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');
        if ($value === '' || strlen($value) > 150 || !preg_match('/^[a-z0-9-]+$/', $value)) {
            throw new RuntimeException('Slug must use lowercase letters, numbers, and hyphens only.');
        }
        return $value;
    }

    private function category(string $category): string
    {
        $key = strtolower(trim(str_replace(['-', ' '], '_', $category)));
        $map = [
            'court_rental' => 'court_rental',
            'court_rentals' => 'court_rental',
            'court_reservation' => 'court_rental',
            'private_coaching' => 'private_coaching',
            'coaching' => 'private_coaching',
            'lessons' => 'private_coaching',
            'lesson' => 'private_coaching',
            'social_play' => 'social_play',
            'match_play' => 'social_play',
            'open_match_play' => 'social_play',
            'community_event' => 'social_play',
            'tournament' => 'tournament',
            'training' => 'training_session',
            'training_session' => 'training_session',
            'class' => 'training_session',
            'kids_class' => 'training_session',
            'youth_class' => 'training_session',
        ];

        if (!isset($map[$key])) {
            throw new RuntimeException('Choose a valid service category.');
        }

        return $map[$key];
    }

    private function status(string $status): string
    {
        $status = strtolower(trim($status));
        return in_array($status, ['active', 'inactive', 'maintenance'], true) ? $status : 'active';
    }

    private function pricingType(string $pricingType): string
    {
        $pricingType = strtolower(trim($pricingType));
        if ($pricingType === 'per_court_session') {
            return 'per_court';
        }
        return in_array($pricingType, ['per_court', 'per_participant', 'per_session', 'per_team'], true) ? $pricingType : 'per_session';
    }

    private function coachRequired(string $coachRequired): string
    {
        $coachRequired = strtolower(trim($coachRequired));
        return in_array($coachRequired, ['no', 'yes', 'optional'], true) ? $coachRequired : 'no';
    }

    private function adminId(array $input, ?int $adminId): int
    {
        return max(0, (int) ($adminId ?? $input['admin_id'] ?? 0));
    }
}
