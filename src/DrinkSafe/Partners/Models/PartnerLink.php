<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Models;

use Database\Factories\DrinkSafe\PartnerLinkFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PartnerLink Model
 *
 * A short link printed as a partner's QR code. Opening it redirects to the
 * destination and adds one to that partner's total for the day. Nothing
 * about the person who opened it is kept.
 *
 * @property int $id
 * @property string $code Lowercase letters, digits and hyphens, used in /go/{code}
 * @property string $label Readable partner name
 * @property string $destination Internal path the link redirects to
 * @property bool $active Inactive links redirect home without counting
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read int|null $total Sum of daily counts, when loaded with withSum
 *
 * @method static PartnerLinkFactory factory($count = null, $state = [])
 */
#[UseFactory(PartnerLinkFactory::class)]
final class PartnerLink extends Model
{
    use HasFactory;

    /**
     * What a code may contain, matching the partner render in print/artwork/render.mjs.
     */
    public const string CODE_PATTERN = '/^[a-z0-9-]+$/';

    /**
     * An internal path: one leading slash, then only URL-safe characters. A
     * second slash or a backslash straight after the first would make browsers
     * treat it as another host, so neither is allowed.
     */
    public const string DESTINATION_PATTERN = '#^/(?![/\\\\])[A-Za-z0-9\-._~/?=&%]*\z#';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'partner_links';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'label',
        'destination',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Daily scan totals for this link.
     *
     * @return HasMany<PartnerLinkDailyCount, $this>
     */
    public function dailyCounts(): HasMany
    {
        return $this->hasMany(PartnerLinkDailyCount::class);
    }

    /**
     * Whether a destination is a path on this site, so a link can never be
     * turned into an open redirect.
     */
    public static function isInternalPath(string $destination): bool
    {
        return preg_match(self::DESTINATION_PATTERN, $destination) === 1;
    }

    /**
     * The path to redirect to, falling back to the home page if the stored
     * destination is not an internal path.
     */
    public function redirectPath(): string
    {
        return self::isInternalPath($this->destination) ? $this->destination : '/';
    }

    /**
     * Add one to today's total in a single upsert, so two scans at the same
     * moment can't overwrite each other.
     *
     * The day is the UK calendar day, so a scan after midnight on a night out
     * counts towards that UK date rather than the UTC one.
     */
    public function recordScan(): void
    {
        PartnerLinkDailyCount::query()->upsert(
            [['partner_link_id' => $this->id, 'date' => today('Europe/London')->toDateString(), 'count' => 1]],
            ['partner_link_id', 'date'],
            ['count' => DB::raw('count + 1')],
        );
    }

    /**
     * The address the partner's QR code points at.
     */
    public function url(): string
    {
        return sprintf('https://drinksafe.app/go/%s', $this->code);
    }
}
