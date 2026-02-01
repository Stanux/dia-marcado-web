<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AlbumType Model
 * 
 * Represents a type/category for albums (Pre-Wedding, Post-Wedding, Site Usage).
 */
class AlbumType extends Model
{
    /**
     * Album type constants
     */
    const PRE_WEDDING = 'pre_casamento';
    const POST_WEDDING = 'pos_casamento';
    const SITE_USAGE = 'uso_site';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'description',
    ];

    /**
     * Get the albums of this type.
     */
    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    /**
     * Get all available album type slugs.
     *
     * @return array<string>
     */
    public static function getSlugs(): array
    {
        return [
            self::PRE_WEDDING,
            self::POST_WEDDING,
            self::SITE_USAGE,
        ];
    }
}
