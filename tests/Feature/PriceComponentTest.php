<?php

declare(strict_types=1);

namespace Tests\Feature;

use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class PriceComponentTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_formats_price()
    {
        $view = $this->blade(
            '<x-price>100</x-price>'
        );

        $view->assertSee('€1.00');
    }

    public function test_formats_negative_price()
    {
        $view = $this->blade(
            '<x-price>-100</x-price>'
        );

        $view->assertSee('-€1.00');
    }

    public function test_zero()
    {
        $view = $this->blade(
            '<x-price>0</x-price>'
        );

        $view->assertSee('€0.00');
    }

    public function test_handle_not_numbers()
    {
        $view = $this->blade(
            '<x-price>abc</x-price>'
        );

        $view->assertSee('€0.00');
    }
}
