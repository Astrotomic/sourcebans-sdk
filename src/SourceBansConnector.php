<?php

namespace Astrotomic\SourceBansSdk;

use Astrotomic\SourceBansSdk\Paginator\FirstResponsePagedPaginator;
use Astrotomic\SourceBansSdk\Requests\QueryBansRequest;
use DateTimeInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;
use Saloon\Contracts\HasPagination;
use Saloon\Contracts\Request;
use Saloon\Contracts\Response;
use Saloon\Http\Connector;
use Saloon\Http\Paginators\PagedPaginator;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use SteamID;

class SourceBansConnector extends Connector implements HasPagination
{
    use AlwaysThrowOnErrors;

    public function __construct(
        public readonly string $baseUrl,
    ) {
    }

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function queryBans(
        SteamID $steamid = null,
        DateTimeInterface $date = null,
        int $page = null,
    ): LengthAwarePaginator|LazyCollection|null {
        $request = new QueryBansRequest(
            steamid: $steamid,
            date: $date,
        );

        if (! is_null($page)) {
            $request->query()->add('page', $page);

            return $this->send($request)->dto();
        }

        return $this->paginate($request)
            ->collect()
            ->map(fn (Response $response) => $response->dto()->items())
            ->collapse();
    }

    public function paginate(Request $request, ...$additionalArguments): PagedPaginator
    {
        $paginator = new FirstResponsePagedPaginator(
            connector: $this,
            originalRequest: $request,
            limitCallback: fn (Response $response) => $response->dtoOrFail()->perPage(),
            totalCallback: fn (Response $response) => $response->dtoOrFail()->total(),
        );

        $paginator->setLimitKeyName('_limit');
        $paginator->setPageKeyName('page');

        return $paginator;
    }
}
