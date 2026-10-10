<?php

declare(strict_types=1);

namespace Tests\Feature\Rules;

use App\Rules\ValidRole;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ValidRoleTest extends TestCase
{
    public static function valid_roles_provider()
    {
        return [
            ['admin'],
            ['organizer'],
            ['racemanager'],
            ['tireagent'],
            ['timekeeper'],
            ['driver'],
        ];
    }

    public static function invalid_roles_provider()
    {
        return [
            ['Admin'],
            ['administrator'],
            [''],
            ['*'],
            [1],
            [['admin']],
        ];
    }

    #[DataProvider('valid_roles_provider')]
    public function test_valid_roles_pass($value)
    {
        $validator = Validator::make(['role' => $value], ['role' => [new ValidRole]]);

        $this->assertTrue($validator->passes());
    }

    #[DataProvider('invalid_roles_provider')]
    public function test_invalid_roles_fail($value)
    {
        $validator = Validator::make(['role' => $value], ['role' => ['required', new ValidRole]]);

        $this->assertTrue($validator->fails());
    }

    public function test_error_message()
    {
        $validator = Validator::make(['role' => 'not-a-role'], ['role' => [new ValidRole]]);

        $this->assertEquals('The role must be a valid role.', $validator->errors()->first('role'));
    }
}
