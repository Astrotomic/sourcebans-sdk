<?php

namespace Astrotomic\SourceBansSdk\Extractors;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;

abstract class Extractor
{
    abstract public function canHandle(Crawler $crawler): bool;

    abstract public function handle(Crawler $crawler): ?LengthAwarePaginator;

    protected function toCarbonImmutable(?string $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        return collect([
            'Y-m-d H:i:s',
            'm-d-y H:i',
            'Y.m.d H:i',
            'd.m.Y H:i',
            'd.m.Y H:i:s',
            'd.m.y H:i:s',
            'l dS \o\f F Y h:i:s A',
            'd-m-Y \| H:i:s',
        ])
            ->map(fn (string $format) => rescue(fn () => CarbonImmutable::createFromFormat($format, $value), report: false))
            ->filter()
            ->first();
    }

    protected function toCarbonInterval(?string $value): ?CarbonInterval
    {
        if (blank($value)) {
            return null;
        }

        $value = (string) Str::of($value)
            ->lower()
            ->before('(')
            ->trim();

        if ($value === 'permanent') {
            return null;
        }

        return CarbonInterval::fromString(str_replace(
            [',', 'wk', 'hr', 'min', 'sec'],
            [' ', 'w', 'h', 'm', 's'],
            $value
        ));
    }

    /**
     * @return array{start: int, end: int, total: int}
     */
    protected function paginationFromElements(Crawler $elements): array
    {
        $data = $elements->each(function (Crawler $el): ?array {
            try {
                $value = $el->innerText();

                $text = html_entity_decode(
                    str_replace(
                        '&nbsp;',
                        ' ',
                        htmlentities($value, encoding: 'utf-8')
                    )
                );

                preg_match('/.+\s+(\d+)\s+-\s+(\d+)\s+.+\s+(\d+)\s+.+/', $text, $matches);

                if (empty($matches)) {
                    throw new InvalidArgumentException("Unable to extract pagination details from [{$value}] string.");
                }

                return [
                    'start' => (int) $matches[1],
                    'end' => (int) $matches[2],
                    'total' => (int) $matches[3],
                ];
            } catch (InvalidArgumentException) {
                return null;
            }
        });

        return collect($data)->filter()->first();
    }

    protected function currentPageFromSelect(Crawler $select): int
    {
        return rescue(
            callback: fn () => $select->filter('option[selected]')->attr('value'),
            report: false,
            rescue: 1,
        );
    }
}
