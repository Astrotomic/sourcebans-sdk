<?php

namespace Astrotomic\SourceBansSdk;

use Astrotomic\SourceBansSdk\Data\Ban;
use Astrotomic\SourceBansSdk\Paginator\FirstResponsePagedPaginator;
use Astrotomic\SourceBansSdk\Requests\QueryBansRequest;
use Astrotomic\SteamSdk\SteamID;
use DateTimeInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\PaginationPlugin\Contracts\HasPagination;
use Saloon\PaginationPlugin\Paginator;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class SourceBansConnector extends Connector implements HasPagination
{
    use AlwaysThrowOnErrors;

    public function __construct(
        public readonly string $baseUrl,
    ) {}

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function queryBans(
        ?SteamID $steamid = null,
        ?DateTimeInterface $date = null,
        ?int $page = null,
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
            ->map(fn (Ban $ban) => $ban);
    }

    public function paginate(Request $request): Paginator
    {
        $paginator = new FirstResponsePagedPaginator(
            connector: $this,
            originalRequest: $request,
        );

        return $paginator;
    }
}
