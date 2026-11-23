<?php

namespace App\Services;

class MarketplaceLocationService
{
    public function municipalities(): array
    {
        return array_keys(config('marketplace_locations.municipalities', []));
    }

    public function location(string $municipality): ?array
    {
        return config('marketplace_locations.municipalities', [])[$municipality] ?? null;
    }

    public function barangays(string $municipality): array
    {
        return $this->location($municipality)['barangays'] ?? [];
    }

    public function isValidMunicipality(string $municipality): bool
    {
        return isset(config('marketplace_locations.municipalities', [])[$municipality]);
    }

    public function isValidBarangay(string $municipality, ?string $barangay): bool
    {
        if ($barangay === null) {
            return true;
        }

        if (! $this->isValidMunicipality($municipality)) {
            return false;
        }

        return in_array($barangay, $this->barangays($municipality), true);
    }

    public function isValid(string $municipality, ?string $barangay): bool
    {
        return $this->isValidMunicipality($municipality) && $this->isValidBarangay($municipality, $barangay);
    }
}
