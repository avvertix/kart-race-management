<?php

use App\Models\Participant;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperation;

return new class extends OneTimeOperation
{
    /**
     * This moves all participants registered
     * in TDM categories to the corresponding
     * territorial ones
     */
    public function process(): void
    {
        $categoriesToTransfer = [
            '60 MINI TDM ROK' => '60 MINI TERR',
            '60 MINI TDM EASY' => '60 MINI TERR',
            '60 MINI TDM FR ROTAX' => '60 MINI TERR',
            '60 MINI TDM X30' => '60 MINI TERR',

            '125 JUNIOR TDM ROK' => '125 JUNIOR TERR',
            '125 JUNIOR TDM X30' => '125 JUNIOR TERR',

            '125 SENIOR TDM ROK' => '125 SENIOR TERR',
            '125 SENIOR TDM SUPEROK' => '125 SENIOR TERR',
            '125 SENIOR TDM ROTAX MAX' => '125 SENIOR TERR',
            '125 SENIOR TDM BMB' => '125 SENIOR TERR',
            '125 SENIOR TDM X30' => '125 SENIOR TERR',
            '125 SENIOR TDM SHIFTER ROK' => '125 SENIOR TERR',
            '125 SENIOR TDM KGP SHIFTER' => '125 SENIOR TERR',
            '125 SENIOR TDM X30 SUPER SHIFTER' => '125 SENIOR TERR',
            '125 SENIOR TDM KGP DIRECT DRIVE' => '125 SENIOR TERR',
            '125 SENIOR TDM ROTAX DD2' => '125 SENIOR TERR',
            '125 SENIOR TDM X30 SUPER' => '125 SENIOR TERR',
        ];


        Participant::query()
            ->whereIn('category', array_keys($categoriesToTransfer))
            ->lazy()->each(function($participant) use ($categoriesToTransfer) {
                $participant->category = $categoriesToTransfer[$participant->category] ?? $participant->category;
                
                logs()->info("Migrating participant category", [
                    'id' => $participant->getKey(),
                    'new' => $participant->category,
                    'old' => $participant->getOriginal('category'),
                ]);

                $participant->save();

            });
    }
};
