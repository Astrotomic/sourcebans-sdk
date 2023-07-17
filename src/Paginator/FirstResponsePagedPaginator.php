<?php

namespace Astrotomic\SourceBansSdk\Paginator;

use Closure;
use Saloon\Contracts\Connector;
use Saloon\Contracts\Request;
use Saloon\Exceptions\PaginatorException;
use Saloon\Http\Paginators\PagedPaginator;

class FirstResponsePagedPaginator extends PagedPaginator
{
    protected ?int $total = null;

    public function __construct(
        Connector $connector,
        Request $originalRequest,
        protected Closure $limitCallback,
        protected Closure $totalCallback,
        int $page = 1
    ) {
        parent::__construct(
            connector: $connector,
            originalRequest: $originalRequest,
            perPage: PHP_INT_MAX,
            page: $page
        );

        $this->limit = null;
    }

    protected function applyPagination(Request $request): void
    {
        $request->query()->add($this->getPageKeyName(), $this->getCurrentPage());
    }

    public function limit(): int
    {
        if (is_null($this->currentResponse)) {
            $this->current();
        }

        if (is_null($this->limit)) {
            $this->limit = call_user_func($this->limitCallback, $this->currentResponse);

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
            $this->total = call_user_func($this->totalCallback, $this->currentResponse);

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

    protected function isFinished(): bool
    {
        return $this->getCurrentPage() > $this->totalPages();
    }
}
