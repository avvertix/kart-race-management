<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EntitlementMode;
use App\Enums\PromoCodeStatus;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * A code that gives Entitlements to race fees in one championship once claimed, see
 * docs/reference/lifecycles.md#promo-code.
 *
 * @property string $ulid
 * @property string $code
 * @property int $championship_id
 * @property EntitlementMode $mode
 * @property int $amount
 * @property PromoCodeStatus $status
 * @property ?CarbonImmutable $claimed_at
 * @property ?int $claimed_by_user_id
 * @property ?int $licence_id
 * @property ?string $licence_hash
 * @property int $created_by
 * @property-read Championship $championship
 * @property-read User $createdBy
 */
class PromoCode extends Model
{
    /** @use HasFactory<\Database\Factories\PromoCodeFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * The characters of random codes: no 0/O nor 1/I, which are easily confused.
     */
    public const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const RANDOM_LENGTH = 8;

    protected $fillable = [
        'code',
        'championship_id',
        'mode',
        'amount',
        'status',
        'claimed_at',
        'claimed_by_user_id',
        'licence_id',
        'licence_hash',
        'created_by',
    ];

    /**
     * The code as stored: trimmed and uppercase, so codes are case-insensitive.
     */
    public static function normalize(string $code): string
    {
        return mb_strtoupper(mb_trim($code));
    }

    /**
     * A random code of RANDOM_LENGTH characters from ALPHABET.
     */
    public static function randomCode(): string
    {
        $alphabet = self::ALPHABET;

        return collect(range(1, self::RANDOM_LENGTH))
            ->map(fn () => $alphabet[random_int(0, mb_strlen($alphabet) - 1)])
            ->join('');
    }

    /**
     * The code typed by someone, ignoring case and surrounding spaces.
     */
    public static function findByCode(string $code): ?self
    {
        return self::query()->where('code', self::normalize($code))->first();
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /**
     * Get the route key for the model: the ULID makes the public page unguessable.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * @return BelongsTo<Championship, $this>
     */
    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The Entitlement the code became once claimed.
     *
     * @return MorphOne<Entitlement, $this>
     */
    public function entitlement(): MorphOne
    {
        return $this->morphOne(Entitlement::class, 'source');
    }

    /**
     * The QR code of the code's public page, as an SVG.
     */
    public function qrCodeSvg(): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd
            )
        ))->writeString(route('public.promo-codes.show', $this));

        return mb_trim(mb_substr($svg, mb_strpos($svg, "\n") + 1));
    }

    /**
     * What the code gives, e.g. "2 race fees".
     */
    public function benefit(): string
    {
        return $this->mode->describe($this->amount);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => EntitlementMode::class,
            'status' => PromoCodeStatus::class,
            'amount' => 'integer',
            'claimed_at' => 'datetime',
        ];
    }
}
