<?php
namespace App\Http\Requests\Api\V1;
use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreListingImageRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:8'],
            'images.*' => ['file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $listing = $this->route('listing');
            if (! $listing instanceof Listing) { return; }
            $existing = $listing->images()->count();
            $uploaded = count($this->file('images', []));
            if ($existing + $uploaded > 8) {
                $validator->errors()->add('images', 'A listing may not have more than 8 images.');
            }
        });
    }
}
