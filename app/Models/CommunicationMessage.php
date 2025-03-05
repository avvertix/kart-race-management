<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationMessage extends Model implements Htmlable
{
    use HasFactory;

    protected $fillable = [
        'message',
        'theme',
        'target_path',
        'target_user_role',
        'starts_at',
        'ends_at',
        'dismissable',
    ];

    /**
     * Selects the messages that are configured to be displayed
     */
    public function scopeActive($query)
    {
        $now = now();

        return $query->where('starts_at', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->where('ends_at', '>=', $now)
                    ->orWhereNull('ends_at');
            });
    }

    /**
     * Selects the messages that are configured to be displayed to a specific user role or to all users
     */
    public function scopeTargetUser($query, $role)
    {
        return $query->where(function($query) use ($role) {
            $query->whereJsonContains('target_user_role', $role)
                ->orWhereJsonContains('target_user_role', 'all');
        });
    }

    /**
     * Selects the messages that are configured to be displayed to anonymous users or to all users
     */
    public function scopeTargetAnonymousUsers($query)
    {
        return $query->where(function($query) {

            $query
                ->WhereJsonContains('target_user_role', 'all')
                ->orWhereJsonContains('target_user_role', 'anonim')
                ->orWhereNull('target_user_role');
        });
    }

    public function toHtml()
    {
        return str($this->message)->markdown([
            'html_input' => 'strip',
        ]);
    }

    protected function status(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function ($value = null) {
            $now = now();
            if ($this->ends_at && $now->greaterThan($this->ends_at)) {
                return __('Expired');
            }
            if ($this->starts_at && $now->greaterThan($this->starts_at)) {
                return __('Active');
            }
            if (! $this->starts_at) {
                return __('Inactive');
            }

            return __('Scheduled');
        });
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'dismissable' => 'boolean',
            'target_user_role' => AsCollection::class,
        ];
    }
}
