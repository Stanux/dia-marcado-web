<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Models\Wedding;

class AdminAuditService
{
    /**
     * Log an admin action on a wedding.
     * Only logs actions on weddings that the admin doesn't own.
     *
     * @param User $admin
     * @param Wedding $wedding
     * @param string $action
     * @param array $details
     * @return AdminAuditLog|null
     */
    public function logAction(
        User $admin,
        Wedding $wedding,
        string $action,
        array $details = []
    ): ?AdminAuditLog {
        // Only log for admins
        if (!$admin->isAdmin()) {
            return null;
        }

        // Don't log if admin is couple in this wedding (their own wedding)
        if ($admin->isCoupleIn($wedding)) {
            return null;
        }

        return AdminAuditLog::create([
            'admin_id' => $admin->id,
            'wedding_id' => $wedding->id,
            'action' => $action,
            'details' => $details,
            'performed_at' => now(),
        ]);
    }

    /**
     * Get audit logs for a specific wedding.
     *
     * @param Wedding $wedding
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLogsForWedding(Wedding $wedding, int $limit = 50)
    {
        return AdminAuditLog::where('wedding_id', $wedding->id)
            ->with('admin')
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs for a specific admin.
     *
     * @param User $admin
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLogsForAdmin(User $admin, int $limit = 50)
    {
        return AdminAuditLog::where('admin_id', $admin->id)
            ->with('wedding')
            ->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
