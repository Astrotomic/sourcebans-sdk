<?php

namespace Tests\Feature\Requests;

use Astrotomic\SourceBansSdk\Data\Ban;
use Astrotomic\SteamSdk\SteamID;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;
use Tests\TestCase;

final class QueryBansRequestTest extends TestCase
{
    private const BASE_URLS = [
        // default
        'https://firepoweredgaming.com/sourcebans/index.php',
        'https://bans.harpoongaming.com/index.php',
        'https://bans.panda-community.com/index.php',
        'https://bans.floserver.de/index.php',
        'https://csgoskin.ir/server/index.php',
        'https://tf2-casual-fun.de/sourcebans/index.php',
        'https://vimo.lt/bans/index.php',
        'https://bans.blackwonder.tf/index.php',
        'https://hellbreak.pl/sb/index.php',
        'https://www.skial.com/sourcebans/index.php',
        // fluent
        'https://sourcebans.onetap.pl/index.php',
        'https://goodteemo.serverscstrike.com/index.php',
        // blue
        'https://neonheights.xyz/bans/index.php',
    ];

    public function test_can_load_first_page_of_bans(): void
    {
        foreach (self::BASE_URLS as $baseUrl) {
            $bans = $this->sourcebans($baseUrl)->queryBans(page: 1);

            self::assertGreaterThan(0, $bans->count(), $baseUrl);
            self::assertLessThanOrEqual($bans->perPage(), $bans->count());
            self::assertContainsOnlyInstancesOf(Ban::class, $bans);
        }
    }

    public function test_can_load_specific_page_of_bans(): void
    {
        foreach (range(1, 20) as $page) {
            $bans = $this->sourcebans('https://sourcebans.onetap.pl/index.php')->queryBans(page: $page);

            self::assertInstanceOf(LengthAwarePaginator::class, $bans);
            self::assertGreaterThanOrEqual(0, $bans->perPage());
            self::assertGreaterThan(0, $bans->count());
            self::assertLessThanOrEqual($bans->perPage(), $bans->count());
            self::assertGreaterThan(0, $bans->total());
            self::assertContainsOnlyInstancesOf(Ban::class, $bans->items());
        }
    }

    public function test_can_load_bans_across_pages(): void
    {
        $bans = $this->sourcebans('https://sourcebans.onetap.pl/index.php')->queryBans()->take(31);

        self::assertSame(31, $bans->count());
        self::assertContainsOnlyInstancesOf(Ban::class, $bans);
    }

    public function test_can_search_for_steamid(): void
    {
        $steamid = new SteamID('76561198928142028');

        $bans = $this->sourcebans('https://firepoweredgaming.com/sourcebanspp/index.php')->queryBans(steamid: $steamid);

        self::assertInstanceOf(LazyCollection::class, $bans);
        self::assertSame(2, $bans->count());
        self::assertContainsOnlyInstancesOf(Ban::class, $bans);

        $bans->each(function (Ban $ban) use ($steamid): void {
            self::assertSame($steamid->toSteamID(), $ban->steam_id->toSteamID());
            self::assertSame(2, $ban->total_bans);
        });
    }

    public function test_can_search_for_date(): void
    {
        $date = CarbonImmutable::create(2022, 11, 16);

        $bans = $this->sourcebans('https://firepoweredgaming.com/sourcebans/index.php')->queryBans(date: $date);

        self::assertInstanceOf(LazyCollection::class, $bans);
        self::assertSame(3, $bans->count());
        self::assertContainsOnlyInstancesOf(Ban::class, $bans);

        $bans->each(function (Ban $ban) use ($date): void {
            self::assertTrue($date->isSameDay($ban->invoked_on));
        });
    }
}
