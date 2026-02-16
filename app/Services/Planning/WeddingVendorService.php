<?php

namespace App\Services\Planning;

use App\Models\Vendor;
use App\Models\Wedding;
use App\Models\WeddingVendor;
use Illuminate\Support\Arr;

class WeddingVendorService
{
    public function createOrUpdateForWedding(Wedding $wedding, array $data): WeddingVendor
    {
        $document = $this->normalizeDocument($data['document'] ?? null);
        $categoryIds = Arr::get($data, 'category_ids', []);

        $globalVendor = Vendor::withTrashed()->where('document', $document)->first();

        if (!$globalVendor) {
            $globalVendor = Vendor::create([
                'name' => $data['name'],
                'document' => $document,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'website' => $data['website'] ?? null,
            ]);

            if (!empty($categoryIds)) {
                $globalVendor->categories()->sync($categoryIds);
            }
        } elseif ($globalVendor->trashed()) {
            $globalVendor->restore();
        }

        $weddingVendor = WeddingVendor::withTrashed()
            ->where('wedding_id', $wedding->id)
            ->where('vendor_id', $globalVendor->id)
            ->first();

        if (!$weddingVendor) {
            $weddingVendor = new WeddingVendor([
                'wedding_id' => $wedding->id,
                'vendor_id' => $globalVendor->id,
            ]);
        } elseif ($weddingVendor->trashed()) {
            $weddingVendor->restore();
        }

        $weddingVendor->fill([
            'name' => $data['name'] ?? $globalVendor->name,
            'document' => $document,
            'phone' => $data['phone'] ?? $globalVendor->phone,
            'email' => $data['email'] ?? $globalVendor->email,
            'website' => $data['website'] ?? $globalVendor->website,
        ]);

        $weddingVendor->save();

        if (!empty($categoryIds)) {
            $weddingVendor->categories()->sync($categoryIds);
        }

        return $weddingVendor;
    }

    public function normalizeDocument(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $value);

        return $normalized ?: null;
    }
}
