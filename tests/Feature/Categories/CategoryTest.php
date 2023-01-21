<?php

namespace Tests\Feature\Categories;

use App\Categories\Category;
use App\Rules\ExistsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CategoryTest extends TestCase
{

    public function test_default_categories_are_loaded()
    {
        config([
            'categories.default' => [
                'category_key' => [
                    'name' => 'CAT 1',
                    'tires' => 'T1',
                ],
            ],
        ]);


        $categories = Category::all();

        $this->assertEquals(1, $categories->count());

        $this->assertEquals('CAT 1', $categories->first()->get('name'));
        $this->assertEquals('T1', $categories->first()->get('tires'));
    }

    public function test_categories_from_file_are_loaded()
    {
        Storage::fake('local');

        config([
            'categories.file' => 'test-categories.json',
            'categories.disk' => 'local',
            'categories.default' => [
                'category_key' => [
                    'name' => 'CAT 1',
                    'tires' => 'T1',
                ],
            ],
        ]);

        Storage::disk('local')->put('test-categories.json', json_encode([
            'category2_key' => [
                'name' => 'CAT 2',
                'tires' => 'T2',
            ],
        ]));

        $categories = Category::all();

        $this->assertEquals(2, $categories->count());

        $this->assertEquals('CAT 1', $categories->first()->get('name'));
        $this->assertEquals('T1', $categories->first()->get('tires'));
        $this->assertEquals('CAT 2', $categories->last()->get('name'));
        $this->assertEquals('T2', $categories->last()->get('tires'));
    }

    public function test_category_found_by_key()
    {
        config([
            'categories.default' => [
                'category_key' => [
                    'name' => 'CAT 1',
                    'tires' => 'T1',
                ],
            ],
        ]);


        $category = Category::find('category_key');

        $this->assertInstanceOf(Category::class, $category);

        $this->assertEquals('CAT 1', $category->get('name'));
        $this->assertEquals('T1', $category->get('tires'));
    }

    public function test_category_not_found_by_key()
    {
        config([
            'categories.default' => [
                'category_key' => [
                    'name' => 'CAT 1',
                    'tires' => 'T1',
                ],
            ],
        ]);


        $category = Category::find('other');

        $this->assertNull($category);
    }

    public function test_validation_rule_validates_existing_category()
    {

        config([
            'categories.default' => [
                'category_key' => [
                    'name' => 'CAT 1',
                    'tires' => 'T1',
                ],
            ],
        ]);

        $validated = Validator::validate([
            'category' => 'category_key',
        ], [
            'category' => new ExistsCategory
        ]);

        $this->assertEquals(['category' => 'category_key',], $validated);
    }

    public function test_validation_rule_fails_for_invalid_category()
    {

        config([
            'categories.default' => [
                'category_key' => [
                    'name' => 'CAT 1',
                    'tires' => 'T1',
                ],
            ],
        ]);

        $validator = Validator::make([
            'category' => 'other',
        ], [
            'category' => new ExistsCategory
        ]);

        $this->assertTrue($validator->fails());

        $errors = $validator->errors();

        $this->assertEquals('The category must be a valid category', $errors->first('category'));
    }
}
