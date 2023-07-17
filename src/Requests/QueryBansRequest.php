<?php

namespace Astrotomic\SourceBansSdk\Requests;

use Astrotomic\SourceBansSdk\Extractors\Extractor;
use DateTimeInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use OutOfBoundsException;
use Saloon\Contracts\Response;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Request\CastDtoFromResponse;
use SteamID;

class QueryBansRequest extends Request
{
    use CastDtoFromResponse;

    protected Method $method = Method::GET;

    public function __construct(
        public readonly ?SteamID $steamid = null,
        public readonly ?DateTimeInterface $date = null,
    ) {
    }

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

        /** @var \Astrotomic\SourceBansSdk\Extractors\Extractor $extractor */
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
