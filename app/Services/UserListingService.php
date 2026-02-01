<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wedding;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserListingService
{
    /**
     * List users for a wedding with filters and sorting.
     *
     * @param User $viewer
     * @param Wedding $wedding
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function listUsers(
        User $viewer,
        Wedding $wedding,
        array $filters = []
    ): LengthAwarePaginator {
        $query = $wedding->users();

        // Organizer can only see guests
        if ($viewer->isOrganizerIn($wedding)) {
            $query->wherePivot('role', 'guest');
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $query->wherePivot('role', $filters['type']);
        }

        // Search by name or email
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        if ($sortField === 'type') {
            $query->orderByPivot('role', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get all users for a wedding without pagination.
     *
     * @param User $viewer
     * @param Wedding $wedding
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllUsers(
        User $viewer,
        Wedding $wedding,
        array $filters = []
    ) {
        $query = $wedding->users();

        // Organizer can only see guests
        if ($viewer->isOrganizerIn($wedding)) {
            $query->wherePivot('role', 'guest');
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $query->wherePivot('role', $filters['type']);
        }

        // Search by name or email
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        if ($sortField === 'type') {
            $query->orderByPivot('role', $sortDirection);
        } else {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->get();
    }
}
