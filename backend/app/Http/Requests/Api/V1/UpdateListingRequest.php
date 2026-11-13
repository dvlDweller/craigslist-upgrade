<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Listing;
use App\Rules\SelectableCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
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
                'max:99999999.99',
                'decimal:0,2',
                'gt:0',
                Rule::requiredIf(function () {
                    $listing = $this->route('listing');
                    return $this->input('price_type') === Listing::PRICE_TYPE_FIXED
                        && (! $listing instanceof Listing || $listing->price_type !== Listing::PRICE_TYPE_FIXED);
                }),
                Rule::prohibitedIf(fn () => in_array($this->input('price_type'), [Listing::PRICE_TYPE_FREE, Listing::PRICE_TYPE_CONTACT], true) && $this->filled('price')),
            ],
            'municipality' => ['sometimes', 'nullable', 'string', 'max:100'],
            'barangay' => ['sometimes', 'nullable', 'string', 'max:100'],
            'user_id' => ['missing'],
            'status' => ['missing'],
            'published_at' => ['missing'],
            'currency' => ['missing'],
            'province' => ['missing'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $listing = $this->route('listing');
            $priceTypeSubmitted = $this->has('price_type');
            $priceSubmitted = $this->has('price');

            $effectivePriceType = $priceTypeSubmitted
                ? $this->input('price_type')
                : ($listing instanceof Listing ? $listing->price_type : null);

            $price = $this->input('price');

            if (in_array($effectivePriceType, [Listing::PRICE_TYPE_FREE, Listing::PRICE_TYPE_CONTACT], true)) {
                if ($priceSubmitted && $price !== null) {
                    $validator->errors()->add('price', 'The price field must be null for free/contact listings.');
                }
                return;
            }

            if ($effectivePriceType === Listing::PRICE_TYPE_FIXED) {
                if ($priceTypeSubmitted) {
                    $existingIsFixed = $listing instanceof Listing && $listing->price_type === Listing::PRICE_TYPE_FIXED;
                    if ($existingIsFixed) {
                        // existing fixed: if price submitted and null -> error; if omitted -> okay
                        if ($priceSubmitted && ($price === null || $price === '')) {
                            $validator->errors()->add('price', 'The price field cannot be null for fixed listings.');
                        }
                    } else {
                        // transitioning from free/contact: require price not null/empty
                        if ($price === null || $price === '') {
                            $validator->errors()->add('price', 'The price field is required for fixed listings.');
                        }
                    }
                } else {
                    // price_type omitted (existing fixed)
                    if ($priceSubmitted && ($price === null || $price === '')) {
                        $validator->errors()->add('price', 'The price field cannot be null for fixed listings.');
                    }
                    // if omitted preserve existing
                }
            }

            // Active listings cannot clear municipality.
            if ($listing instanceof Listing && $listing->status === Listing::STATUS_ACTIVE && $this->has('municipality')) {
                $submittedMunicipality = $this->input('municipality');
                if ($submittedMunicipality === null || $submittedMunicipality === '') {
                    $validator->errors()->add('municipality', 'The municipality field cannot be empty for active listings.');
                }
            }
        });
    }
}