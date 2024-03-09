<?php

namespace App\Actions;

use App\Models\Race;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Models\Sex;
use App\Rules\ExistsCategory;
use App\Models\DriverLicence;
use App\Models\Participant;
use App\Models\CompetitorLicence;
use App\Models\TrashedParticipant;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteParticipant
{

    /**
     * Delete a participant and trash it
     *
     * @param  array  $input
     * @return \App\Models\TrashedParticipant
     */
    public function __invoke(Participant $participant)
    {
        $trashedParticipant = Cache::lock("participant:{$participant->bib}", 10)->block(5, function() use($participant){

            return DB::transaction(function() use($participant){

                $replica = Arr::only($participant->toArray(), [
                    'bib',
                    'category',
                    'category_id',
                    'first_name',
                    'last_name',
                    'added_by',
                    'confirmed_at',
                    'consents',
                    'race_id',
                    'championship_id',
                    'driver_licence',
                    'licence_type',
                    'competitor_licence',
                    'driver',
                    'competitor',
                    'mechanic',
                    'vehicles',
                    'use_bonus',
                    'locale',
                    'registration_completed_at',
                ]);

                $trashedParticipant = (new TrashedParticipant)->forceFill([
                    ...['uuid' => $participant->uuid],
                    ...$replica
                ]);

                $trashedParticipant->save();

                $participant->signatures()->delete();
                
                $participant->tires()->delete();
                
                $participant->transponders()->delete();

                $participant->activities()->delete();

                $activeBonuses = $participant->bonuses()->get();

                if($activeBonuses->isNotEmpty()){
                    $participant->bonuses()->detach($activeBonuses);
                }

                $participant->delete();
    
                return $trashedParticipant;
            });

        });
        
        return $trashedParticipant;
    }

    
    
}
