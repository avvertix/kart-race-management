<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A confirmed Enrollment was withdrawn: its driver no longer counts as enrolled in the championship.
 */
class EnrollmentRevoked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Enrollment $enrollment
    ) {}
}
