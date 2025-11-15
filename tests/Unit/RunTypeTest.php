<?php

namespace Tests\Unit;

use App\Models\RunType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RunTypeTest extends TestCase
{
    public static function practice_session_file_names()
    {
        return [
            ['2 - LIBERE - 125 KZ N ROOKIE   KZ N UNDER   KZ N OVER   KZ N OVER 50   KZ 2 - Results.xml'],
            ['2 - FREE PRACTICE - 125 tdm junior   tdm senior   x30 - results.xml'],
            ['2 - PRACTICE - 125 tdm junior   tdm senior   x30 - results.xml'],
            ['warmup - 4 TEMPI TUTTI - Results.xml'],
            ['prove libere - 60 MINI GR.3 - Results.xml'],
            ['warm-up - 60 MINI GR.3 - Results.xml'],
        ];
    }
    
    public static function qualifying_session_file_names()
    {
        return [
            ['2 - QUALIFICHE   QUALIFYING - 125 KZ N ROOKIE   KZ N UNDER   KZ N OVER   KZ N OVER 50   KZ 2 - Results.xml'],
            ['2 - qualifiche - 125 tdm junior   tdm senior   x30 - results.xml'],
            ['qualifying - 4 TEMPI TUTTI - Results.xml'],
            ['cronometrate - 60 MINI GR.3 - Results.xml'],
            ['prove cronometrate - 60 MINI GR.3 - Results.xml'],
        ];
    }
    
    public static function first_race_session_file_names()
    {
        return [
            ['3 - RACE 1 - 125 KZ N ROOKIE   KZ N UNDER   KZ N OVER   KZ N OVER 50   KZ 2 - Results.xml'],
            ['3 - GARA 1 - 125 OK JUNIOR   OK SENIOR   OK N JUNIOR   OK N SENIOR - Results.xml'],
            ['3 - GARA 1   RACE 1 - 125 TDM JUNIOR   TDM SENIOR   X30 - Results.xml'],
            ['Pre-finale - T4 CLUB SOLO TILLOTSON - Results.xml'],
            ['Prefinale - T4 CLUB SOLO TILLOTSON - Results.xml'],
        ];
    }
    
    public static function second_race_session_file_names()
    {
        return [
            ['4 - GARA 2 - 125 KZ N ROOKIE   KZ N UNDER   KZ N OVER   KZ N OVER 50   KZ 2 - Results.xml'],
            ['RACE 2 - 125 OK JUNIOR   OK SENIOR   OK N JUNIOR   OK N SENIOR - Results.xml'],
            ['4 - RACE 2 - 125 TDM JUNIOR   TDM SENIOR   X30 - Results.xml'],
            ['4 - Finale - 60 MINI GR.3 - Results.xml'],
        ];
    }
    
    public static function unknown_session_file_names()
    {
        return [
            ['false'],
            ['null'],
            [null],
            [''],
            ['2023-03-31'],
            ['gara - results.xml'],
            
        ];
    }

    #[DataProvider('practice_session_file_names')]
    public function test_practice_sessions_recognized(string $file): void
    {
        $runType = RunType::fromString($file);

        $this->assertEquals(RunType::WARM_UP, $runType);
        $this->assertTrue($runType->isPractice());
    }

    #[DataProvider('qualifying_session_file_names')]
    public function test_qualifying_sessions_recognized(string $file): void
    {
        $runType = RunType::fromString($file);

        $this->assertEquals(RunType::QUALIFY, $runType);
        $this->assertTrue($runType->isQualify());
    }
    
    #[DataProvider('first_race_session_file_names')]
    public function test_race_one_sessions_recognized(string $file): void
    {
        $runType = RunType::fromString($file);

        $this->assertEquals(RunType::RACE_1, $runType);
        $this->assertTrue($runType->isRace());
    }
    
    #[DataProvider('second_race_session_file_names')]
    public function test_race_two_sessions_recognized(string $file): void
    {
        $runType = RunType::fromString($file);

        $this->assertEquals(RunType::RACE_2, $runType);
        $this->assertTrue($runType->isRace());
    }
    
    #[DataProvider('unknown_session_file_names')]
    public function test_run_not_recognized(?string $file): void
    {
        try {
            $runType = RunType::fromString($file);
            $this->fail("Expected InvalidArgumentException not thrown for file name: {$file}");
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Cannot identify run from ', $e->getMessage());
        }
    }
}
