<?php

namespace Tests\Feature\Catalog;

use App\Enums\Freshness;
use App\Models\Coffee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FreshnessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<array{0: int, 1: Freshness}>
     */
    public static function roastAges(): array
    {
        return [
            [0, Freshness::Today],
            [1, Freshness::Peak],
            [3, Freshness::Peak],
            [4, Freshness::Fresh],
            [7, Freshness::Fresh],
            [8, Freshness::Ageing],
            [40, Freshness::Ageing],
        ];
    }

    #[DataProvider('roastAges')]
    public function test_freshness_is_derived_from_the_roast_date(int $daysAgo, Freshness $expected)
    {
        $coffee = Coffee::factory()->create();
        $coffee->detail->update(['roast_date' => now()->subDays($daysAgo)]);

        $this->assertSame($expected, $coffee->fresh()->freshness());
    }

    public function test_coffee_without_a_roast_date_has_no_freshness()
    {
        $coffee = Coffee::factory()->create();
        $coffee->detail->update(['roast_date' => null]);

        $this->assertNull($coffee->fresh()->freshness());
    }
}
