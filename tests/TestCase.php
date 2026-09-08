<?php

namespace Tests;

use Astrotomic\SourceBansSdk\SourceBansConnector;
use Astrotomic\SourceBansSdk\SourceBansSdkServiceProvider;
use Illuminate\Support\Arr;
use Orchestra\Testbench\TestCase as Orchestra;
use Saloon\Http\Faking\Fixture;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\PendingRequest;

abstract class TestCase extends Orchestra
{
    protected $enablesPackageDiscoveries = true;

    protected function setUp(): void
    {
        parent::setUp();

        MockClient::destroyGlobal();
        MockClient::global([
            SourceBansConnector::class => function (PendingRequest $request): Fixture {
                $name = implode('/', array_filter([
                    parse_url($request->getUrl(), PHP_URL_HOST),
                    $request->getMethod()->value,
                    parse_url($request->getUrl(), PHP_URL_PATH),
                    Arr::query(collect($request->query()->all())->diffKeys(array_flip(['key', 'format']))->sortKeys()->all()),
                ]));

                return new class($name) extends Fixture
                {
                    public function getFixturePath(): string
                    {
                        return sprintf('%s.%s', $this->name, static::$fixtureExtension);
                    }
                };
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
