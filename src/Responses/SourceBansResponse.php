<?php

namespace Astrotomic\SourceBansSdk\Responses;

use Astrotomic\SourceBansSdk\Exceptions\BadGatewayException;
use Astrotomic\SourceBansSdk\Exceptions\BadResponseException;
use Astrotomic\SourceBansSdk\Exceptions\ClientException;
use Astrotomic\SourceBansSdk\Exceptions\ServerException;
use Sammyjo20\Saloon\Http\SaloonResponse;

class SourceBansResponse extends SaloonResponse
{
    public function toException(): ?BadResponseException
    {
        return match (true) {
            $this->clientError() => ClientException::fromResponse($this),
            $this->serverError() => match ($this->status()) {
                502 => BadGatewayException::fromResponse($this),
                default => ServerException::fromResponse($this),
            },
            $this->failed() => BadResponseException::fromResponse($this),
            default => null,
        };
    }
}
