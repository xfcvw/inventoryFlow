<?php

namespace App\Services;

use App\Models\Workspace;

class PlanService
{
    public function limits(Workspace $workspace): array
    {
        return config('plans.' . $workspace->plan, config('plans.free'));
    }

    public function productLimit(Workspace $workspace): ?int { return $this->limits($workspace)['products'] ?? null; }
    public function memberLimit(Workspace $workspace): ?int { return $this->limits($workspace)['members'] ?? null; }
    public function warehouseLimit(Workspace $workspace): ?int { return $this->limits($workspace)['warehouses'] ?? null; }
    public function reportAccess(Workspace $workspace): bool { return (bool) ($this->limits($workspace)['reports'] ?? false); }

    public function canCreateProduct(Workspace $workspace): bool
    {
        $limit = $this->productLimit($workspace);
        return $limit === null || $workspace->products()->count() < $limit;
    }

    public function canAddMember(Workspace $workspace): bool
    {
        $limit = $this->memberLimit($workspace);
        return $limit === null || $workspace->users()->count() < $limit;
    }

    public function canCreateWarehouse(Workspace $workspace): bool
    {
        $limit = $this->warehouseLimit($workspace);
        return $limit === null || $workspace->warehouses()->count() < $limit;
    }
}
