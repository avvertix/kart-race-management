<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver',
        'bonus_type',
        'driver_licence',
        'driver_licence_hash',
        'driver_fiscal_code',
        'driver_fiscal_code_hash',
        'contact_email',
        'amount',
    ];

    protected $withCount = [
        'usages',
    ];

    /**
     * Get the championship
     */
    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }

    public function usages()
    {
        return $this->belongsToMany(Participant::class, 'participant_bonus')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function scopeLicenceHash($query, $licenceHash)
    {
        return $query->where('driver_licence_hash', $licenceHash);
    }

    public function scopeLicence($query, $licence)
    {
        return $query->where('driver_licence_hash', hash('sha512', $licence));
    }

    public function scopeFiscalCodeHash($query, $fiscalCodeHash)
    {
        return $query->where('driver_fiscal_code_hash', $fiscalCodeHash);
    }

    public function scopeFiscalCode($query, $fiscalCodeHash)
    {
        return $query->where('driver_fiscal_code_hash', hash('sha512', $fiscalCodeHash));
    }

    public function hasRemaining(): bool
    {
        return $this->remaining > 0;
    }

    /**
     * Get the user's first name.
     */
    protected function remaining(): Attribute
    {
        return Attribute::make(
            get: function () {

                $bonusMode = $this->championship->bonuses->bonus_mode ?? BonusMode::CREDIT;

                if ($bonusMode === BonusMode::CREDIT) {
                    // we subtract the count of usages

                    $usageCount = $this->usages_count ?? $this->usages()->count();

                    return max(0, $this->amount - $usageCount);

                }

                // we subtract the sum of amounts used
                $usedAmount = (int) ($this->used_amount ?? $this->usages->sum('pivot.amount'));

                return max(0, $this->amount - $usedAmount);
            },
        );

    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'bonus_type' => BonusType::class,
            'driver_licence' => 'encrypted',
            'driver_fiscal_code' => 'encrypted',
        ];
    }
}
