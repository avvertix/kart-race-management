<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PriceComponentTest extends TestCase
{
    use RefreshDatabase;


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
