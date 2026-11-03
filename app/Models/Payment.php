<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'id',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'path',
        'hash',
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * What the payment is for, e.g. an Order. Race participant payments use participant instead.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<Model, $this>
     */
    public function payable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Delete the record and, once the transaction commits, its file.
     */
    public function deleteWithFile(): void
    {
        $path = $this->path;

        $this->delete();

        DB::afterCommit(fn () => Storage::disk('payments')->delete($path));
    }

    protected function downloadUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function ($value = null) {
            return URL::temporarySignedRoute('payment-verification.show', 15 * Carbon::SECONDS_PER_MINUTE, ['payment' => $this->getKey()]);
        });
    }
}
