<?php

namespace App\Http\Resources;

use App\Services\ListingImageStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
                ? app(ListingImageStorage::class)->url($primaryImage->path, $primaryImage->disk)
                : null,
            'created_at' => $this->created_at,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
        ];
    }
}
