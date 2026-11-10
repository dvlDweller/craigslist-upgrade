<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ListingDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price_type' => $this->price_type,
            'price' => $this->price,
            'currency' => $this->currency,
            'municipality' => $this->municipality,
            'barangay' => $this->barangay,
            'province' => $this->province,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'images' => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => Storage::disk($image->disk)->url($image->path),
                'sort_order' => $image->sort_order,
                'is_primary' => $image->is_primary,
            ])->values(),
        ];
    }
}
