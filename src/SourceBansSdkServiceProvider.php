<?php

namespace Astrotomic\SourceBansSdk;

use Astrotomic\SourceBansSdk\Extractors\Banlist\BlueExtractor;
use Astrotomic\SourceBansSdk\Extractors\Banlist\DefaultExtractor;
use Astrotomic\SourceBansSdk\Extractors\Banlist\FluentExtractor;
use Illuminate\Support\ServiceProvider;

class SourceBansSdkServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DefaultExtractor::class);
        $this->app->bind(FluentExtractor::class);
        $this->app->bind(BlueExtractor::class);

        $this->app->tag([
            DefaultExtractor::class,
            FluentExtractor::class,
            BlueExtractor::class,
        ], 'extractors.banlist');
    }
}
