<?php

namespace Tests\Unit;

use App\Models\ResultStatus;
use App\Models\RunType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ResultStatusTest extends TestCase
{
    public static function result_statuses()
    {
        return [
            ['DSQ', ResultStatus::DISQUALIFIED],
            ['DQ', ResultStatus::DISQUALIFIED],
            ['DNF', ResultStatus::DID_NOT_FINISH],
            ['DNS', ResultStatus::DID_NOT_START],
            ['FINISHED', ResultStatus::FINISHED],
            ['dsq', ResultStatus::DISQUALIFIED],
            ['dq', ResultStatus::DISQUALIFIED],
            ['dnf', ResultStatus::DID_NOT_FINISH],
            ['dns', ResultStatus::DID_NOT_START],
            ['finished', ResultStatus::FINISHED],
            ['any other text', ResultStatus::FINISHED],
        ];
    }


    #[DataProvider('result_statuses')]
    public function test_result_status_parsed_from_string(string $value, ResultStatus $expected): void
    {
        $status = ResultStatus::fromString($value);

        $this->assertEquals($expected, $status);
    }
    
    
    public function test_status_finished(): void
    {
        $status = ResultStatus::FINISHED;

        $this->assertTrue($status->finished());
        $this->assertFalse($status->disqualified());
        $this->assertFalse($status->didNotFinish());
        $this->assertFalse($status->didNotStart());
        $this->assertFalse($status->unfinishedOrPenalty());
    }
    
    public function test_status_disqualified(): void
    {
        $status = ResultStatus::DISQUALIFIED;

        $this->assertFalse($status->finished());
        $this->assertTrue($status->disqualified());
        $this->assertFalse($status->didNotFinish());
        $this->assertFalse($status->didNotStart());
        $this->assertTrue($status->unfinishedOrPenalty());
    }
    
    public function test_status_did_not_finish(): void
    {
        $status = ResultStatus::DID_NOT_FINISH;

        $this->assertFalse($status->finished());
        $this->assertFalse($status->disqualified());
        $this->assertTrue($status->didNotFinish());
        $this->assertFalse($status->didNotStart());
        $this->assertTrue($status->unfinishedOrPenalty());
    }
    
    public function test_status_did_not_start(): void
    {
        $status = ResultStatus::DID_NOT_START;

        $this->assertFalse($status->finished());
        $this->assertFalse($status->disqualified());
        $this->assertFalse($status->didNotFinish());
        $this->assertTrue($status->didNotStart());
        $this->assertTrue($status->unfinishedOrPenalty());
    }

}
