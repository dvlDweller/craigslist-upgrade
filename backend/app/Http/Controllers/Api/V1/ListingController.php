<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListListingsRequest;
use App\Http\Resources\ListingDetailResource;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListingController extends Controller
{
    public function index(ListListingsRequest $request): AnonymousResourceCollection
    {
        $query = Listing::query()
            ->where('status', Listing::STATUS_ACTIVE)
            ->with([
                'category:id,name,slug',
                'user:id,name',
                'images' => fn ($query) => $query
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order'),
            ]);

        $category = $request->validated('category');
        $search = $request->validated('q');
        $municipalities = $request->validated('municipality');
        $barangay = $request->validated('barangay');
        $minPrice = $request->validated('min_price');
        $maxPrice = $request->validated('max_price');
        $priceType = $request->validated('price_type');

        if ($category !== null) {
            $query->whereHas('category', function ($query) use ($category): void {
                $query->where('slug', $category);
            });
        }

        if ($search !== null) {
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (is_array($municipalities) && $municipalities !== []) {
            $query->whereIn('municipality', $municipalities);
        }

        if ($barangay !== null) {
            $query->where('barangay', $barangay);
        }

        if ($minPrice !== null || $maxPrice !== null) {
            $query->where('price_type', Listing::PRICE_TYPE_FIXED);

            if ($minPrice !== null) {
                $query->where('price', '>=', $minPrice);
            }

            if ($maxPrice !== null) {
                $query->where('price', '<=', $maxPrice);
            }
        }

        if ($priceType !== null) {
            $query->where('price_type', $priceType);
        }

        $sort = $request->validated('sort') ?? 'newest';

        match ($sort) {
            'price_asc' => $query->orderByRaw('price is null, price asc')->orderBy('id'),
            'price_desc' => $query->orderByRaw('price is null, price desc')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderBy('id'),
        };

        $perPage = (int) ($request->validated('per_page') ?? 15);

        return ListingResource::collection($query->paginate($perPage));
    }

    public function show(Listing $listing): ListingDetailResource
    {
        abort_if($listing->status !== Listing::STATUS_ACTIVE, 404);

        $listing->load([
            'category:id,name,slug',
            'user:id,name',
            'images' => fn ($query) => $query
                ->orderByDesc('is_primary')
                ->orderBy('sort_order'),
        ]);

        return new ListingDetailResource($listing);
    }
}
