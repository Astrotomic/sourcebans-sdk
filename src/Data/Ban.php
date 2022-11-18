<?php

namespace Astrotomic\SourceBansSdk\Data;

use Astrotomic\SourceBansSdk\Enums\BanStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Spatie\LaravelData\Data;
use SteamID;

final class Ban extends Data
{
    public function __construct(
        public readonly SteamID $steam_id,
        public readonly CarbonImmutable $invoked_on,
        public readonly ?CarbonInterval $ban_length,
        public readonly ?CarbonImmutable $expires_on,
        public readonly ?string $ban_reason,
        public readonly ?string $unban_reason,
        public readonly int $total_bans,
    ) {
    }

    public function status(): BanStatus
    {
        if ($this->expires_on !== null && $this->expires_on->isPast()) {
            return BanStatus::Expired;
        }

        if ($this->unban_reason !== null) {
            return BanStatus::Unbanned;
        }

        return BanStatus::Banned;
    }
}
