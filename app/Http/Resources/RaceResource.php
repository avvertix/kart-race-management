<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'track' => $this->track,
            'type' => $this->type?->localizedName(),
            'period' => $this->period,
            'event_start_at' => $this->event_start_at,
            'event_end_at' => $this->event_end_at,
            'is_registration_open' => $this->is_registration_open,
            'championship' => [
                'uuid' => $this->championship->uuid,
                'title' => $this->championship->title,
            ],
            'registration_url' => route('races.registration.create', $this->uuid),
        ];
    }
}
