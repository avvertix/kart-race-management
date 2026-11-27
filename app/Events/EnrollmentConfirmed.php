<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The organizer confirmed an Enrollment: its driver now counts as enrolled in the championship.
 */
class EnrollmentConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Enrollment $enrollment
    ) {}
}
