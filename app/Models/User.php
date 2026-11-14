<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationType;
use App\Enums\Role;
use App\Features\LicencesAndEnrollments;
use App\HasRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Pennant\Feature;

/**
 * @property ?string $locale
 * @property ?array<string, bool> $notification_preferences
 */
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail, PasskeyUser
{
    use HasFactory;
    use HasRole;
    use Notifiable;
    use PasskeyAuthenticatable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'locale',
        'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The organizers to notify: every organizer with early access, and the admins who turned the email on.
     *
     * @return Collection<int, self>
     */
    public static function organizersToNotify(NotificationType $type): Collection
    {
        $candidates = self::query()->whereIn('role', [Role::Organizer->value, Role::Admin->value])->get();

        Feature::for($candidates->all())->load([LicencesAndEnrollments::class]);

        return $candidates
            ->filter(fn (self $user) => ($user->hasRole(Role::Organizer) || $user->wantsEmail($type))
                && Feature::for($user)->active(LicencesAndEnrollments::class))
            ->values();
    }

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * Whether the User can receive the notifications sent to the organizers: organizers, and admins who opt in.
     */
    public function canReceiveOrganizerNotifications(): bool
    {
        return $this->hasRole(Role::Organizer) || $this->isAdmin();
    }

    /**
     * Whether the notification is also sent by email: yes unless turned off. Admins receive the organizers' ones only when turned on.
     */
    public function wantsEmail(NotificationType $type): bool
    {
        return $this->notification_preferences[$type->value] ?? ! ($type->isForOrganizers() && $this->isAdmin());
    }

    public function preferredLocale(): string
    {
        return $this->locale ?? config('app.locale');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }
}
