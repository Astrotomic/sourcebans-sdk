<?php

namespace Astrotomic\SourceBansSdk\Requests;

use Astrotomic\SourceBansSdk\Extractors\Extractor;
use Astrotomic\SteamSdk\SteamID;
use DateTimeInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use OutOfBoundsException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use Saloon\Traits\Request\CreatesDtoFromResponse;

class QueryBansRequest extends Request implements Paginatable
{
    use CreatesDtoFromResponse;

    protected Method $method = Method::GET;

    public function __construct(
        public readonly ?SteamID $steamid = null,
        public readonly ?DateTimeInterface $date = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '';
    }

    public function defaultQuery(): array
    {
        return array_merge([
            'p' => 'banlist',
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

    public function createDtoFromResponse(Response $response): ?LengthAwarePaginator
    {
        $crawler = $response->dom();

        /** @var Extractor $extractor */
        $extractor = collect(app()->tagged('extractors.banlist'))
            ->first(fn (Extractor $extractor) => $extractor->canHandle($crawler));

        if ($extractor === null) {
            throw new OutOfBoundsException("[{$response->getPendingRequest()->getUrl()}] is not supported by any extractor.");
        }

        return $extractor->handle($crawler)
            ?->setPath($response->getPendingRequest()->getUrl())
            ?->appends($response->getPendingRequest()->query()->all());

    }
}
