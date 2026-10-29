<?php

declare(strict_types=1);

use DrinkSafe\Partners\Models\PartnerLink;
use DrinkSafe\Partners\Models\PartnerLinkDailyCount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('GET /go/{code}', function (): void {
    beforeEach(function (): void {
        $this->phoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';
    });

    it('redirects an active code to its destination', function (): void {
        $partnerLink = PartnerLink::factory()->create(['destination' => '/submit-report']);

        $response = $this->getJson(sprintf('/go/%s', $partnerLink->code));

        $response->assertFound()->assertRedirect('/submit-report');
    });

    it('redirects to a destination set for that code', function (): void {
        $partnerLink = PartnerLink::factory()->create(['destination' => '/map?search=Manchester']);

        $response = $this->getJson(sprintf('/go/%s', $partnerLink->code));

        $response->assertFound()->assertRedirect('/map?search=Manchester');
    });

    it('matches the code regardless of case', function (): void {
        $partnerLink = PartnerLink::factory()->create(['destination' => '/submit-report']);

        $response = $this->getJson(sprintf('/go/%s', strtoupper($partnerLink->code)));

        $response->assertFound()->assertRedirect('/submit-report');
    });

    it('redirects home for any path that is not an active code', function (string $path): void {
        PartnerLink::factory()->inactive()->create(['code' => 'switched-off']);

        $response = $this->getJson($path);

        $response->assertFound()->assertRedirect('/');
    })->with([
        'unknown code' => '/go/not-a-partner',
        'inactive code' => '/go/switched-off',
        'no code' => '/go',
        'trailing slash' => '/go/',
        'extra segments' => '/go/uom/extra',
    ]);

    it('redirects home if the stored destination is not a path on this site', function (string $destination): void {
        $partnerLink = PartnerLink::factory()->create();
        DB::table('partner_links')->where('id', $partnerLink->id)->update(['destination' => $destination]);

        $response = $this->getJson(sprintf('/go/%s', $partnerLink->code));

        $response->assertFound()->assertRedirect('/');
    })->with([
        'protocol relative' => '//evil.example',
        'absolute' => 'https://evil.example',
        'backslash' => '/\\evil.example',
        'scheme' => 'javascript:alert(1)',
    ]);

    it('counts the first scan of the day as one', function (string $userAgent): void {
        $partnerLink = PartnerLink::factory()->create();

        $this->withHeader('User-Agent', $userAgent)->getJson(sprintf('/go/%s', $partnerLink->code));

        expect($partnerLink->dailyCounts()->get(['date', 'count'])->toArray())
            ->toBe([['date' => today('Europe/London')->toDateString(), 'count' => 1]]);
    })->with([
        'iOS Safari' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
        'iOS Chrome' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/129.0.6668.69 Mobile/15E148 Safari/604.1',
        'Android Chrome' => 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Mobile Safari/537.36',
        'Samsung Internet' => 'Mozilla/5.0 (Linux; Android 14; SAMSUNG SM-S921B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/26.0 Chrome/122.0.0.0 Mobile Safari/537.36',
        'Google app' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) GSA/338.0.689074466 Mobile/15E148 Safari/604.1',
        'Firefox Android' => 'Mozilla/5.0 (Android 14; Mobile; rv:131.0) Gecko/131.0 Firefox/131.0',
        'Instagram in-app' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Instagram 350.0.0.20.95 (iPhone15,2; iOS 18_0; en_GB; en-GB; scale=3.00; 1179x2556; 652145187)',
    ]);

    it('counts a scan after midnight towards the UK date', function (): void {
        $partnerLink = PartnerLink::factory()->create();
        $this->travelTo(Carbon::parse('2026-07-05 00:30', 'Europe/London'));

        $this->withHeader('User-Agent', $this->phoneUserAgent)->getJson(sprintf('/go/%s', $partnerLink->code));

        expect($partnerLink->dailyCounts()->get(['date', 'count'])->toArray())
            ->toBe([['date' => '2026-07-05', 'count' => 1]]);
    });

    it('still redirects if the scan cannot be counted', function (): void {
        $partnerLink = PartnerLink::factory()->create(['destination' => '/submit-report']);
        Schema::drop('partner_link_daily_counts');

        $response = $this->withHeader('User-Agent', $this->phoneUserAgent)->getJson(sprintf('/go/%s', $partnerLink->code));

        $response->assertFound()->assertRedirect('/submit-report');
    });

    it('adds every scan on the same day to one row', function (): void {
        $partnerLink = PartnerLink::factory()->create();
        $scans = fake()->numberBetween(2, 6);

        foreach (range(1, $scans) as $scan) {
            $this->withHeader('User-Agent', $this->phoneUserAgent)->getJson(sprintf('/go/%s', $partnerLink->code));
        }

        expect($partnerLink->dailyCounts()->get(['date', 'count'])->toArray())
            ->toBe([['date' => today('Europe/London')->toDateString(), 'count' => $scans]]);
    });

    it('starts a new row on a new day', function (): void {
        $partnerLink = PartnerLink::factory()->create();
        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(23, 59));
        $this->withHeader('User-Agent', $this->phoneUserAgent)->getJson(sprintf('/go/%s', $partnerLink->code));
        $this->travelTo(now()->setDate(2026, 11, 2)->setTime(0, 1));

        $this->withHeader('User-Agent', $this->phoneUserAgent)->getJson(sprintf('/go/%s', $partnerLink->code));

        expect($partnerLink->dailyCounts()->orderBy('date')->get(['date', 'count'])->toArray())
            ->toBe([['date' => '2026-11-01', 'count' => 1], ['date' => '2026-11-02', 'count' => 1]]);
    });

    it('does not count an inactive code', function (): void {
        $partnerLink = PartnerLink::factory()->inactive()->create();

        $this->withHeader('User-Agent', $this->phoneUserAgent)->getJson(sprintf('/go/%s', $partnerLink->code));

        expect(PartnerLinkDailyCount::query()->count())->toBe(0);
    });

    it('redirects bots and link previews without counting them', function (string $userAgent): void {
        $partnerLink = PartnerLink::factory()->create(['destination' => '/submit-report']);

        $response = $this->withHeader('User-Agent', $userAgent)->getJson(sprintf('/go/%s', $partnerLink->code));

        $response->assertFound()->assertRedirect('/submit-report');
        expect(PartnerLinkDailyCount::query()->count())->toBe(0);
    })->with([
        'Googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'bingbot' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
        'Facebook' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
        'Slack' => 'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)',
        'WhatsApp' => 'WhatsApp/2.23.20.0',
        'Twitter' => 'Twitterbot/1.0',
        'Discord' => 'Mozilla/5.0 (compatible; Discordbot/2.0; +https://discordapp.com)',
        'Telegram' => 'TelegramBot (like TwitterBot)',
        'curl' => 'curl/8.7.1',
        'wget' => 'Wget/1.21.4',
        'spider' => 'Mozilla/5.0 (compatible; Baiduspider/2.0)',
        'crawler' => 'SomeCrawler/1.0',
        'Google Inspection Tool' => 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MMB29P) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.6668.70 Mobile Safari/537.36 (compatible; Google-InspectionTool/1.0;)',
        'Embedly' => 'Mozilla/5.0 (compatible; Embedly/0.2; +http://support.embed.ly/)',
        'Viber' => 'Viber/22.6.0',
        'Go' => 'Go-http-client/2.0',
        'node-fetch' => 'node-fetch/1.0 (+https://github.com/bitinn/node-fetch)',
        'axios' => 'axios/1.7.7',
        'OkHttp' => 'okhttp/4.12.0',
        'Apache HttpClient' => 'Apache-HttpClient/4.5.14 (Java/17.0.12)',
        'Python urllib' => 'Python-urllib/3.11',
        'ChatGPT-User' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot',
        'Perplexity-User' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Perplexity-User/1.0; +https://perplexity.ai/perplexity-user)',
        'no user agent' => '',
    ]);

    it('does not count a HEAD request', function (): void {
        $partnerLink = PartnerLink::factory()->create();

        $this->withHeader('User-Agent', $this->phoneUserAgent)->call('HEAD', sprintf('/go/%s', $partnerLink->code));

        expect(PartnerLinkDailyCount::query()->count())->toBe(0);
    });

    it('runs no middleware that could keep per-visitor state', function (): void {
        expect(Route::getRoutes()->getByName('partners.go')->gatherMiddleware())->toBe([]);
    });

    it('sets no cookies', function (): void {
        $partnerLink = PartnerLink::factory()->create();

        $response = $this->withHeader('User-Agent', $this->phoneUserAgent)->getJson(sprintf('/go/%s', $partnerLink->code));

        $response->assertFound();
        expect($response->headers->has('Set-Cookie'))->toBeFalse()
            ->and($response->headers->getCookies())->toBeEmpty();
    });

    it('stores nothing about the person who scanned', function (): void {
        $partnerLink = PartnerLink::factory()->create();
        $userAgent = sprintf('Mozilla/5.0 (Linux; Android 15) DistinctiveScanner/%s Mobile Safari/537.36', fake()->uuid());
        $ipAddress = fake()->ipv4();
        $referrer = sprintf('https://%s/', fake()->domainName());

        $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
            ->withHeaders(['User-Agent' => $userAgent, 'Referer' => $referrer])
            ->getJson(sprintf('/go/%s', $partnerLink->code));

        $everyRow = collect(DB::select("select name from sqlite_master where type = 'table'"))
            ->map(fn (object $table): string => json_encode(DB::table($table->name)->get(), JSON_UNESCAPED_SLASHES))
            ->implode("\n");
        expect($partnerLink->dailyCounts()->sum('count'))->toBe(1)
            ->and($everyRow)->not->toContain($userAgent)
            ->and($everyRow)->not->toContain('DistinctiveScanner')
            ->and($everyRow)->not->toContain($ipAddress)
            ->and($everyRow)->not->toContain($referrer);
    });
});
