<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Listing;
use App\Rules\SelectableCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PublishListingRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'category_id' => ['sometimes', 'required', 'integer', new SelectableCategory()],
            'price_type' => ['sometimes', 'required', 'string', Rule::in([Listing::PRICE_TYPE_FIXED, Listing::PRICE_TYPE_FREE, Listing::PRICE_TYPE_CONTACT])],
            'price' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:99999999.99',
                'decimal:0,2',
                'required_if:price_type,' . Listing::PRICE_TYPE_FIXED,
                'prohibited_if:price_type,' . Listing::PRICE_TYPE_FREE . ',' . Listing::PRICE_TYPE_CONTACT,
            ],
            'municipality' => ['sometimes', 'required', 'string', 'max:100'],
            'barangay' => ['nullable', 'string', 'max:100'],
            'currency' => ['sometimes', 'required', Rule::in(['PHP'])],
            'province' => ['sometimes', 'required', Rule::in(['Camarines Sur'])],
        ];
    }

    public function validateListing(Listing $listing): void
    {
        Validator::make($listing->toArray(), $this->rules())->validate();
    }
}