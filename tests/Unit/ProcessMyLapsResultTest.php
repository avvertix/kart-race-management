<?php

namespace Tests\Unit;

use App\Actions\ProcessMyLapsResult;
use App\Data\Results\RacerQualifyingResultData;
use App\Data\Results\RacerRaceResultData;
use App\Data\Results\RunResultData;
use App\Models\ResultStatus;
use App\Models\RunType;
use PHPUnit\Framework\TestCase;

class ProcessMyLapsResultTest extends TestCase
{
    public function test_processes_qualifying_results(): void
    {
        $filePath = __DIR__ . '/../fixtures/qualifying-results.xml';

        $result = (new ProcessMyLapsResult())($filePath);

        $this->assertInstanceOf(RunResultData::class, $result);
        $this->assertEquals(RunType::QUALIFY, $result->session);
        $this->assertEquals('qualifying-results', $result->title);
        $this->assertTrue($result->session->isQualify());
        $this->assertCount(30, $result->results);

        // Check first result (P1)
        $firstResult = $result->results->first();
        $this->assertInstanceOf(RacerQualifyingResultData::class, $firstResult);
        $this->assertEquals(121, $firstResult->bib);
        $this->assertEquals('SMITH JOHN', $firstResult->name);
        $this->assertEquals('4T - BRIGGS', $firstResult->category);
        $this->assertEquals('1', $firstResult->position);
        $this->assertEquals('1', $firstResult->position_in_category);
        $this->assertEquals('56.053', $firstResult->best_lap_time);
        $this->assertEquals('6', $firstResult->best_lap_number);
        $this->assertEquals('56.063', $firstResult->second_best_time);
        $this->assertEquals('7', $firstResult->second_best_lap_number);
        $this->assertEquals(80.281, $firstResult->best_speed);
        $this->assertEquals(80.267, $firstResult->second_best_speed);
        $this->assertEquals(10.0, $firstResult->points);
        $this->assertEquals(ResultStatus::FINISHED, $firstResult->status);
        $this->assertFalse($firstResult->is_dnf);
        $this->assertFalse($firstResult->is_dns);
        $this->assertFalse($firstResult->is_dq);
        $this->assertEquals('520bc3b3', $firstResult->racer_hash);

        // Check a middle result (P10)
        $tenthResult = $result->results->get(9);
        $this->assertEquals(35, $tenthResult->bib);
        $this->assertEquals('MARTINEZ CHARLES', $tenthResult->name);
        $this->assertEquals('4T - TILLOTSON', $tenthResult->category);
        $this->assertEquals('10', $tenthResult->position);
        $this->assertEquals('8', $tenthResult->position_in_category);
        $this->assertEquals('56.883', $tenthResult->best_lap_time);
        $this->assertEquals('0.830', $tenthResult->gap_from_leader);
        $this->assertEquals('0.021', $tenthResult->gap_from_previous);

        // Check last result with no time (P30)
        $lastResult = $result->results->last();
        $this->assertEquals(111, $lastResult->bib);
        $this->assertEquals('WALKER JACOB', $lastResult->name);
        $this->assertEquals('4T - BRIGGS', $lastResult->category);
        $this->assertEquals('30', $lastResult->position);
        $this->assertEquals('5', $lastResult->position_in_category);
        $this->assertEquals('', $lastResult->best_lap_time);
        $this->assertEquals('0', $lastResult->best_lap_number);
        $this->assertNull($lastResult->best_speed);
        $this->assertEquals('', $lastResult->second_best_time);
        $this->assertEquals(6.0, $lastResult->points);
    }

    public function test_processes_race_1_results(): void
    {
        $filePath = __DIR__ . '/../fixtures/race-1-results.xml';

        $result = (new ProcessMyLapsResult())($filePath);

        $this->assertInstanceOf(RunResultData::class, $result);
        $this->assertEquals(RunType::RACE_1, $result->session);
        $this->assertTrue($result->session->isRace());
        $this->assertCount(10, $result->results);

        // Check first result (winner)
        $firstResult = $result->results->first();
        $this->assertInstanceOf(RacerRaceResultData::class, $firstResult);
        $this->assertEquals(214, $firstResult->bib);
        $this->assertEquals('PETERSON MAX', $firstResult->name);
        $this->assertEquals('MINI GR3', $firstResult->category);
        $this->assertEquals('1', $firstResult->position);
        $this->assertEquals('1', $firstResult->position_in_category);
        $this->assertEquals(9, $firstResult->laps);
        $this->assertEquals('8:17.256', $firstResult->total_race_time);
        $this->assertEquals('54.201', $firstResult->best_lap_time);
        $this->assertEquals('8', $firstResult->best_lap_number);
        $this->assertEquals('', $firstResult->gap_from_leader);
        $this->assertEquals('', $firstResult->gap_from_previous);
        $this->assertEquals(20.0, $firstResult->points);
        $this->assertEquals(ResultStatus::FINISHED, $firstResult->status);
        $this->assertFalse($firstResult->is_dnf);
        $this->assertFalse($firstResult->is_dns);
        $this->assertFalse($firstResult->is_dq);
        $this->assertEquals('9e015a39', $firstResult->racer_hash);

        // Check a middle result
        $fifthResult = $result->results->get(4);
        $this->assertEquals(959, $fifthResult->bib);
        $this->assertEquals('FISCHER THEO', $fifthResult->name);
        $this->assertEquals('5', $fifthResult->position);
        $this->assertEquals('5', $fifthResult->position_in_category);
        $this->assertEquals('11.582', $fifthResult->gap_from_leader);
        $this->assertEquals('4.560', $fifthResult->gap_from_previous);

        // Check DQ result (last entry)
        $dqResult = $result->results->last();
        $this->assertEquals(13, $dqResult->bib);
        $this->assertEquals('GRANT LIAM', $dqResult->name);
        $this->assertEquals('DQ', $dqResult->position);
        $this->assertEquals('DQ', $dqResult->position_in_category);
        $this->assertEquals(9, $dqResult->laps);
        $this->assertEquals(ResultStatus::DISQUALIFIED, $dqResult->status);
        $this->assertTrue($dqResult->is_dq);
        $this->assertFalse($dqResult->is_dnf);
        $this->assertFalse($dqResult->is_dns);
        $this->assertEquals(0.0, $dqResult->points);
    }

    public function test_processes_race_2_results(): void
    {
        $filePath = __DIR__ . '/../fixtures/race-2-results.xml';

        $result = (new ProcessMyLapsResult())($filePath);

        $this->assertInstanceOf(RunResultData::class, $result);
        $this->assertEquals(RunType::RACE_2, $result->session);
        $this->assertTrue($result->session->isRace());
        $this->assertCount(10, $result->results);

        // Check first result (winner)
        $firstResult = $result->results->first();
        $this->assertInstanceOf(RacerRaceResultData::class, $firstResult);
        $this->assertEquals(214, $firstResult->bib);
        $this->assertEquals('PETERSON MAX', $firstResult->name);
        $this->assertEquals('MINI GR3', $firstResult->category);
        $this->assertEquals('1', $firstResult->position);
        $this->assertEquals(9, $firstResult->laps);
        $this->assertEquals('8:19.448', $firstResult->total_race_time);
        $this->assertEquals(ResultStatus::FINISHED, $firstResult->status);

        // Check first DNF (P9 in XML, position 9)
        $dnfResult1 = $result->results->get(8);
        $this->assertEquals(93, $dnfResult1->bib);
        $this->assertEquals('BENNETT NOAH', $dnfResult1->name);
        $this->assertEquals('9', $dnfResult1->position); // Position recalculated based on index
        $this->assertEquals('9', $dnfResult1->position_in_category);
        $this->assertEquals(8, $dnfResult1->laps); // Only completed 8 laps
        $this->assertEquals('7:40.290', $dnfResult1->total_race_time);
        $this->assertEquals(ResultStatus::DID_NOT_FINISH, $dnfResult1->status);
        $this->assertTrue($dnfResult1->is_dnf);
        $this->assertFalse($dnfResult1->is_dns);
        $this->assertFalse($dnfResult1->is_dq);
        $this->assertEquals('1 Lap', $dnfResult1->gap_from_previous);
        $this->assertEquals(12.0, $dnfResult1->points);

        // Check second DNF (P10 in XML, position 10)
        $dnfResult2 = $result->results->last();
        $this->assertEquals(13, $dnfResult2->bib);
        $this->assertEquals('GRANT LIAM', $dnfResult2->name);
        $this->assertEquals('10', $dnfResult2->position);
        $this->assertEquals('10', $dnfResult2->position_in_category);
        $this->assertEquals(1, $dnfResult2->laps); // Only completed 1 lap
        $this->assertEquals('1:07.332', $dnfResult2->total_race_time);
        $this->assertEquals(ResultStatus::DID_NOT_FINISH, $dnfResult2->status);
        $this->assertTrue($dnfResult2->is_dnf);
        $this->assertEquals('7 Laps', $dnfResult2->gap_from_previous);
        $this->assertEquals(11.0, $dnfResult2->points);
    }
}
