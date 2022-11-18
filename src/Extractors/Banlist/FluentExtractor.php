<?php

namespace Astrotomic\SourceBansSdk\Extractors\Banlist;

use Astrotomic\SourceBansSdk\Data\Ban;
use Astrotomic\SourceBansSdk\Extractors\Extractor;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use SteamID;
use Symfony\Component\DomCrawler\Crawler;

class FluentExtractor extends Extractor
{
    public function canHandle(Crawler $crawler): bool
    {
        $css = $crawler->filter('head link[rel=stylesheet]')->each(fn (Crawler $link) => $link->attr('href'));

        return collect($css)->contains(
            fn (string $href) => str_starts_with(trim($href, '/'), 'themes/sourcebans-web-theme-fluent/style')
        );
    }

    public function handle(Crawler $crawler): ?LengthAwarePaginator
    {
        $tables = $crawler->filter('#mainwrapper table tbody tr.table_hide div.collapse_content ul.ban_list_detal');

        if ($tables->count() === 0) {
            return null;
        }

        $bans = $tables->each(function (Crawler $table): ?Ban {
            $data = new Fluent();

            $table->filter('li')->each(function (Crawler $row) use ($data): void {
                $cells = $row->filter('span');

                $key = Str::slug($cells->eq(0)->innerText(), '_');
                $value = rescue(fn () => $cells->eq(1)->innerText(), report: false);

                $data->{$key} = $value;
            });

            return rescue(
                callback: fn () => new Ban(
                    steam_id: new SteamID($data->steam_id),
                    invoked_on: $this->toCarbonImmutable($data->invoked_on),
                    ban_length: $this->toCarbonInterval($data->ban_length),
                    expires_on: $this->toCarbonImmutable($data->expires_on),
                    ban_reason: $data->reason ?: null,
                    unban_reason: $data->unban_reason ?: null,
                    total_bans: (int) $data->total_bans,
                ),
                report: false
            );
        });

        $pagination = $this->paginationFromString(
            $crawler->filter('#mainwrapper .pagination span')->innerText()
        );

        return new LengthAwarePaginator(
            items: collect($bans)
                ->filter()
                ->values(),
            total: $pagination['total'],
            perPage: $pagination['end'] - $pagination['start'],
        );
    }
}
