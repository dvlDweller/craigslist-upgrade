<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['listing_id', 'path', 'disk', 'sort_order', 'is_primary'])]
class ListingImage extends Model
{
    /** @use HasFactory<\Database\Factories\ListingImageFactory> */
    use HasFactory;

    protected $attributes = [
        'disk' => 'public',
        'sort_order' => 0,
        'is_primary' => false,
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
