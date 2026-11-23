<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMarketplaceLocation implements ValidationRule
{
    public function __construct(private ?string $municipality = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($attribute === 'municipality') {
            if (! is_string($value) || ! isset(config('marketplace_locations.municipalities', [])[$value])) {
                $fail('The selected municipality is not allowed.');
            }

            return;
        }

        if ($value === null) {
            return;
        }

        if (! is_string($value)) {
            $fail('The selected barangay is invalid.');

            return;
        }

        $municipality = $this->municipality;

        if ($municipality === null || ! isset(config('marketplace_locations.municipalities', [])[$municipality])) {
            $fail('The selected barangay is not allowed.');

            return;
        }

        if (! in_array($value, config('marketplace_locations.municipalities', [])[$municipality]['barangays'] ?? [], true)) {
            $fail('The selected barangay is not allowed.');
        }
    }
}
