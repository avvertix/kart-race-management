<?php

namespace Tests;

use App\Models\CompetitorLicence;

trait CreateCompetitor
{
    protected function generateValidCompetitor()
    {
        return [
            'competitor_first_name' => 'Parent',
            'competitor_last_name' => 'Racer',
            'competitor_licence_number' => 'C0002',
            'competitor_licence_type' => CompetitorLicence::LOCAL->value,
            'competitor_licence_renewed_at' => null,
            'competitor_nationality' => 'Italy',
            'competitor_email' => 'parent@racer.local',
            'competitor_phone' => '54444444',
            'competitor_birth_date' => '1979-11-11',
            'competitor_birth_place' => 'Milan',
            'competitor_residence_address' => 'via dei Platani, 40',
            'competitor_residence_city' => 'Milan',
            'competitor_residence_province' => 'Milan',
            'competitor_residence_postal_code' => '20146',
        ];
    }

}
