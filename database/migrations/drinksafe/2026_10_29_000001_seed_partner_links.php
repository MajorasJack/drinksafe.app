<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The partner codes printed on the first round of posters.
     *
     * @var list<string>
     */
    private const array CODES = [
        'uom', 'mmu', 'salford', 'ugm', 'rncm', 'tmc', 'xaverian', 'hopwood', 'tscg', 'loreto',
        'cityco', 'village-angels', 'girl-gang', 'girls-who-walk', 'her-social', 'vim', 'sos',
        'hallam', 'liverpool-guild', 'ntu', 'bham', 'bristol-su', 'cardiff', 'glasgow', 'newcastle',
        'leeds-beckett', 'reddit', 'press',
    ];

    public function up(): void
    {
        DB::table('partner_links')->insertOrIgnore(array_map(fn (string $code): array => [
            'code' => $code,
            'label' => Str::headline($code),
            'destination' => '/submit-report',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], self::CODES));
    }

    public function down(): void
    {
        DB::table('partner_links')->whereIn('code', self::CODES)->delete();
    }
};
