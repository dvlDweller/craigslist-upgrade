<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Listing;
use App\Rules\SelectableCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', new SelectableCategory()],
            'title' => ['sometimes', 'string', 'max:120'],
            'description' => ['sometimes', 'string', 'max:5000'],
            'price_type' => ['sometimes', 'string', Rule::in([Listing::PRICE_TYPE_FIXED, Listing::PRICE_TYPE_FREE, Listing::PRICE_TYPE_CONTACT])],
            'price' => [
                'nullable',
                'numeric',
                'gt:0',
                Rule::requiredIf(fn () => $this->input('price_type') === Listing::PRICE_TYPE_FIXED),
                Rule::prohibitedIf(fn () => in_array($this->input('price_type'), [Listing::PRICE_TYPE_FREE, Listing::PRICE_TYPE_CONTACT], true) && $this->filled('price')),
            ],
            'municipality' => ['sometimes', 'nullable', 'string', 'max:100'],
            'barangay' => ['sometimes', 'nullable', 'string', 'max:100'],
            'user_id' => ['prohibited'],
            'status' => ['prohibited'],
            'published_at' => ['prohibited'],
            'currency' => ['prohibited'],
            'province' => ['prohibited'],
        ];
    }
}