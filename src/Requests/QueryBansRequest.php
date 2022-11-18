<?php

namespace Astrotomic\SourceBansSdk\Requests;

use Astrotomic\SourceBansSdk\Extractors\Extractor;
use DateTimeInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use OutOfBoundsException;
use Sammyjo20\Saloon\Http\SaloonRequest;
use Sammyjo20\Saloon\Http\SaloonResponse;
use Sammyjo20\Saloon\Traits\Plugins\CastsToDto;
use SteamID;

class QueryBansRequest extends SaloonRequest
{
    use CastsToDto;

    protected ?string $method = 'GET';

    public function __construct(
        public readonly int $page = 1,
        public readonly ?SteamID $steamid = null,
        public readonly ?DateTimeInterface $date = null,
        public readonly ?int $perPage = null,
    ) {
    }

    public function defaultQuery(): array
    {
        return array_merge([
            'p' => 'banlist',
            'page' => $this->page,
        ], $this->filter());
    }

    /**
     * @return array{advType: string, advSearch: string}
     */
    protected function filter(): array
    {
        return match (true) {
            filled($this->steamid) => ['advType' => 'steam', 'advSearch' => Str::after($this->steamid->RenderSteam2(), ':')],
            filled($this->date) => ['advType' => 'date', 'advSearch' => $this->date->format('d,m,y')],
            default => [],
        };
    }

    protected function castToDto(SaloonResponse $response): ?LengthAwarePaginator
    {
        $crawler = $response->dom();

        /** @var \Astrotomic\SourceBansSdk\Extractors\Extractor $extractor */
        $extractor = collect(app()->tagged('extractors.banlist'))
            ->first(fn (Extractor $extractor) => $extractor->canHandle($crawler));

        if ($extractor === null) {
            throw new OutOfBoundsException("[{$response->getOriginalRequest()->getFullRequestUrl()}] is not supported by any extractor.");
        }

        $paginator = $extractor->handle($crawler);

        if ($paginator === null) {
            return null;
        }

        return new LengthAwarePaginator(
            items: $paginator->items(),
            total: $paginator->total(),
            perPage: max($paginator->perPage(), $this->perPage),
            currentPage: $this->page,
            options: [
                'path' => $response->getOriginalRequest()->getFullRequestUrl(),
                'query' => $response->getOriginalRequest()->getQuery(),
            ]
        );
    }
}
