<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocationApiTest extends TestCase
{
    public function test_locations_endpoint_returns_http_200(): void
    {
        $response = $this->getJson('/api/v1/locations');

        $response->assertOk();
    }

    public function test_locations_endpoint_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/locations');

        $response->assertOk();
    }

    public function test_locations_returns_seven_municipalities(): void
    {
        $response = $this->getJson('/api/v1/locations');

        $response->assertOk()
            ->assertJsonCount(7, 'data');
    }

    public function test_locations_total_barangays_is_173(): void
    {
        $response = $this->getJson('/api/v1/locations');

        $response->assertOk();

        $total = collect($response->json('data'))->sum(fn ($item) => count($item['barangays']));

        $this->assertSame(173, $total);
    }

    public function test_naga_city_entry_has_expected_details(): void
    {
        $response = $this->getJson('/api/v1/locations');

        $response->assertOk();

        $naga = collect($response->json('data'))->firstWhere('municipality', 'Naga City');

        $this->assertNotNull($naga);
        $this->assertSame('City of Naga', $naga['official_name']);
        $this->assertSame('0501724000', $naga['psgc_code']);
        $this->assertCount(27, $naga['barangays']);
    }

    public function test_locations_order_is_deterministic(): void
    {
        $response = $this->getJson('/api/v1/locations');

        $response->assertOk();

        $municipalities = collect($response->json('data'))->pluck('municipality')->all();

        $this->assertSame('Naga City', $municipalities[0]);
        $this->assertSame('Pili', $municipalities[count($municipalities) - 1]);
    }

    public function test_each_municipality_has_unique_psgc_code(): void
    {
        $response = $this->getJson('/api/v1/locations');

        $response->assertOk();

        $psgcCodes = collect($response->json('data'))->pluck('psgc_code')->all();

        $this->assertCount(count(array_unique($psgcCodes)), $psgcCodes);
    }

    public function test_no_duplicate_barangays_within_municipality(): void
    {
        $response = $this->getJson('/api/v1/locations');

        $response->assertOk();

        foreach ($response->json('data') as $item) {
            $barangays = $item['barangays'];
            $this->assertCount(count(array_unique($barangays)), $barangays);
        }
    }

    public function test_barangay_arrays_are_non_empty(): void
    {
        $response = $this->getJson('/api/v1/locations');

        $response->assertOk();

        foreach ($response->json('data') as $item) {
            $this->assertNotEmpty($item['barangays']);
        }
    }

    public function test_locations_match_config(): void
    {
        $response = $this->getJson('/api/v1/locations');

        $response->assertOk();

        $expected = config('marketplace_locations.municipalities');

        $this->assertSame(array_keys($expected), collect($response->json('data'))->pluck('municipality')->all());
    }
}
