<?php

namespace Astrotomic\SourceBansSdk;

use Astrotomic\SourceBansSdk\Requests\QueryBansRequest;
use Astrotomic\SourceBansSdk\Responses\SourceBansResponse;
use DateTimeInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Sammyjo20\Saloon\Http\SaloonConnector;
use Sammyjo20\Saloon\Traits\Plugins\AlwaysThrowsOnErrors;
use SteamID;

class SourceBansConnector extends SaloonConnector
{
    use AlwaysThrowsOnErrors;

    protected ?string $response = SourceBansResponse::class;

    public function __construct(
        public readonly string $baseUrl,
    ) {
    }

    public function defineBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function queryBans(
        int $page = 1,
        SteamID $steamid = null,
        DateTimeInterface $date = null,
        int $perPage = null
    ): ?LengthAwarePaginator {
        return $this->send(
            new QueryBansRequest(
                page: $page,
                steamid: $steamid,
                date: $date,
                perPage: $perPage
            )
        )->dto();
    }
}
