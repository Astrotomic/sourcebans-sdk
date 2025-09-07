<?php

namespace Astrotomic\SourceBansSdk\Paginator;

use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\PagedPaginator;

class FirstResponsePagedPaginator extends PagedPaginator
{
    protected ?int $total = null;

    public function __construct(
        Connector $connector,
        Request $originalRequest,
    ) {
        parent::__construct(
            connector: $connector,
            request: $originalRequest,
        );

        $this->limit = null;
    }

    protected function applyPagination(Request $request): Request
    {
        $request->query()->add('page', $this->getCurrentPage());

        return $request;
    }

    protected function getPageItems(Response $response, Request $request): array
    {
        return $response->dto()->items();
    }

    public function limit(): int
    {
        if (is_null($this->currentResponse)) {
            $this->current();
        }

        if (is_null($this->limit)) {
            $this->limit = $this->currentResponse->dto()->perPage();

            if (is_null($this->limit)) {
                throw new PaginatorException('Unable to calculate the limit from the response. Make sure the limit callback is correct.');
            }
        }

        return $this->limit;
    }

    public function totalResults(): int
    {
        if (is_null($this->currentResponse)) {
            $this->current();
        }

        if (is_null($this->total)) {
            $this->total = $this->currentResponse->dto()->total();

            if (is_null($this->total)) {
                throw new PaginatorException('Unable to calculate the total results from the response. Make sure the callback key is correct.');
            }
        }

        return $this->total;
    }

    public function totalPages(): int
    {
        return (int) ceil($this->totalResults() / $this->limit());
    }

    protected function isLastPage(Response $response): bool
    {
        return $this->getCurrentPage() > $this->totalPages();
    }
}
