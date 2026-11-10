<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $primaryImage = $this->images->first(fn ($image) => $image->is_primary)
            ?? $this->images->sortBy('sort_order')->first();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'price_type' => $this->price_type,
            'price' => $this->price,
            'currency' => $this->currency,
            'municipality' => $this->municipality,
            'province' => $this->province,
            'status' => $this->status,
            'primary_image_url' => $primaryImage
                ? Storage::disk($primaryImage->disk)->url($primaryImage->path)
                : null,
            'created_at' => $this->created_at,
        ];
    }
}
