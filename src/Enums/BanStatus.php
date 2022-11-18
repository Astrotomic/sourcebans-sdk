<?php

namespace Astrotomic\SourceBansSdk\Enums;

enum BanStatus: string
{
    case Banned = 'banned';
    case Expired = 'expired';
    case Unbanned = 'unbanned';
}
