<?php

declare(strict_types=1);

namespace DrinkSafe\Partners\Models;

use Database\Factories\DrinkSafe\PartnerLinkDailyCountFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PartnerLinkDailyCount Model
 *
 * How many times a partner link was opened on one day. This total is the
 * only thing recorded about a scan.
 *
 * @property int $id
 * @property int $partner_link_id
 * @property string $date Y-m-d, in the app timezone
 * @property int $count
 * @property-read PartnerLink $partnerLink
 *
 * @method static PartnerLinkDailyCountFactory factory($count = null, $state = [])
 */
#[UseFactory(PartnerLinkDailyCountFactory::class)]
final class PartnerLinkDailyCount extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'partner_link_daily_counts';

    /**
     * Daily totals carry no timestamps, so nothing records when the last scan happened.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'partner_link_id',
        'date',
        'count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * The date stays a Y-m-d string. A date cast would store a time as well
     * on SQLite, and the upsert's unique key would stop matching it.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'count' => 'integer',
        ];
    }

    /**
     * The partner link these scans belong to.
     *
     * @return BelongsTo<PartnerLink, $this>
     */
    public function partnerLink(): BelongsTo
    {
        return $this->belongsTo(PartnerLink::class);
    }
}
