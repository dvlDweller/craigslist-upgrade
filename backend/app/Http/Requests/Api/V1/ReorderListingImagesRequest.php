<?php
namespace App\Http\Requests\Api\V1;
use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderListingImagesRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'image_ids' => ['required', 'array', 'min:1', 'distinct'],
            'image_ids.*' => ['integer', 'exists:listing_images,id'],
        ];
    }
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $listing = $this->route('listing');
            if (! $listing instanceof Listing) { return; }
            $current = $listing->images()->pluck('id')->sort()->values()->all();
            $submitted = collect($this->input('image_ids', []))->sort()->values()->all();
            if ($current !== $submitted) {
                $validator->errors()->add('image_ids', 'The image_ids must exactly match the listing images.');
            }
        });
    }
}
