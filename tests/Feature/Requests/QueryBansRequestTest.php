<?php

use Astrotomic\SourceBansSdk\Data\Ban;
use Astrotomic\SteamSdk\SteamID;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\Assert;

it('can load first page of bans', function (string $baseUrl): void {
    $bans = $this->sourcebans($baseUrl)->queryBans(page: 1);

    Assert::assertGreaterThan(0, $bans->count());
    Assert::assertLessThanOrEqual($bans->perPage(), $bans->count());
    Assert::assertContainsOnlyInstancesOf(Ban::class, $bans);
})->with('baseurls');

it('can load specific page of bans', function (string $baseUrl, int $page): void {
    $bans = $this->sourcebans($baseUrl)->queryBans(page: $page);

    Assert::assertTrue($bans === null || $bans instanceof LengthAwarePaginator);

    if ($bans instanceof LengthAwarePaginator) {
        Assert::assertGreaterThanOrEqual(0, $bans->perPage());

        Assert::assertGreaterThanOrEqual(0, $bans->count());
        Assert::assertLessThanOrEqual($bans->perPage(), $bans->count());

        Assert::assertGreaterThanOrEqual(0, $bans->total());

        Assert::assertContainsOnlyInstancesOf(Ban::class, $bans->items());
    }
})->with('baseurls')->with(range(1, 20));

it('can load all bans', function (string $baseUrl): void {
    $bans = $this->sourcebans($baseUrl)->queryBans();

    Assert::assertGreaterThan(0, $bans->count());
    Assert::assertContainsOnlyInstancesOf(Ban::class, $bans);
})->with([
    'https://sourcebans.onetap.pl/index.php', // fluent
]);

it('can search for steamid', function (): void {
    $steamid = new SteamID('76561198928142028');

    $bans = $this->sourcebans('https://firepoweredgaming.com/sourcebanspp/index.php')->queryBans(steamid: $steamid);

    Assert::assertInstanceOf(LazyCollection::class, $bans);
    Assert::assertSame(2, $bans->count());
    Assert::assertContainsOnlyInstancesOf(Ban::class, $bans);

    $bans->each(function (Ban $ban) use ($steamid): void {
        Assert::assertSame($steamid->toSteamID(), $ban->steam_id->toSteamID());
        Assert::assertSame(2, $ban->total_bans);
    });
});

it('can search for date', function (): void {
    $date = CarbonImmutable::create(2022, 11, 16);

    $bans = $this->sourcebans('https://firepoweredgaming.com/sourcebans/index.php')->queryBans(date: $date);

    Assert::assertInstanceOf(LazyCollection::class, $bans);
    Assert::assertSame(3, $bans->count());
    Assert::assertContainsOnlyInstancesOf(Ban::class, $bans);

    $bans->each(function (Ban $ban) use ($date): void {
        Assert::assertTrue($date->isSameDay($ban->invoked_on));
    });
});
