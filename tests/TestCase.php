<?php

namespace Tests;

use Astrotomic\SourceBansSdk\SourceBansConnector;
use Astrotomic\SourceBansSdk\SourceBansSdkServiceProvider;
use Illuminate\Support\Arr;
use Orchestra\Testbench\TestCase as Orchestra;
use Saloon\Http\Faking\Fixture;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Laravel\Facades\Saloon;

abstract class TestCase extends Orchestra
{
    protected $enablesPackageDiscoveries = true;

    protected function setUp(): void
    {
        parent::setUp();

        Saloon::fake([
            SourceBansConnector::class => function (PendingRequest $request): Fixture {
                $name = implode('/', array_filter([
                    parse_url($request->getUrl(), PHP_URL_HOST),
                    $request->getMethod()->value,
                    parse_url($request->getUrl(), PHP_URL_PATH),
                    Arr::query(collect($request->query()->all())->diffKeys(array_flip(['key', 'format']))->sortKeys()->all()),
                ]));

                return MockResponse::fixture($name);
            },
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            SourceBansSdkServiceProvider::class,
        ];
    }

    protected function sourcebans(string $baseUrl): SourceBansConnector
    {
        return new SourceBansConnector($baseUrl);
    }
}
