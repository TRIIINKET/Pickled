<?php
declare(strict_types=1);

require_once __DIR__ . '/../repositories/CatalogRepository.php';
require_once __DIR__ . '/AdminLogService.php';

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
        $name = trim((string) ($input['name'] ?? ''));
        $slug = $this->slug((string) ($input['slug'] ?? $name));
        if ($name === '' || $slug === '') {
            throw new RuntimeException('Court name and slug are required.');
        }

        return [
            'name' => $name,
            'slug' => $slug,
            'status' => $this->status((string) ($input['status'] ?? 'active')),
            'description' => trim((string) ($input['description'] ?? '')) ?: null,
            'base_price' => max(0, (float) ($input['base_price'] ?? 0)),
            'capacity' => max(1, (int) ($input['capacity'] ?? 1)),
            'operating_hours' => trim((string) ($input['operating_hours'] ?? '')) ?: null,
            'court_type' => trim((string) ($input['court_type'] ?? '')) ?: null,
        ];
    }

    private function variantData(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $slug = $this->slug((string) ($input['slug'] ?? $name));
        $category = trim((string) ($input['category'] ?? ''));
        $duration = trim((string) ($input['duration_label'] ?? ''));
        $price = (float) ($input['price'] ?? -1);
        $participants = (int) ($input['participants_limit'] ?? 0);
        $capacity = (int) ($input['capacity'] ?? 0);
        $courtId = (int) ($input['court_id'] ?? 0);

        if ($courtId <= 0 || $name === '' || $slug === '' || $category === '' || $duration === '') {
            throw new RuntimeException('Court, service name, slug, category, and duration are required.');
        }
        if ($price < 0 || $participants <= 0 || $capacity <= 0) {
            throw new RuntimeException('Price must be zero or greater. Participants and capacity must be greater than zero.');
        }

        return [
            'court_id' => $courtId,
            'slug' => $slug,
            'name' => $name,
            'category' => $category,
            'duration_label' => $duration,
            'price' => $price,
            'participants_limit' => $participants,
            'capacity' => $capacity,
            'image' => trim((string) ($input['image'] ?? '')) ?: null,
            'active' => !empty($input['active']),
            'sort_order' => max(0, (int) ($input['sort_order'] ?? 0)),
        ];
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    private function status(string $status): string
    {
        $status = strtolower(trim($status));
        return in_array($status, ['active', 'inactive', 'maintenance'], true) ? $status : 'active';
    }

    private function adminId(array $input, ?int $adminId): int
    {
        return max(0, (int) ($adminId ?? $input['admin_id'] ?? 0));
    }
}
