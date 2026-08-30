<?php

namespace App;

enum SkpdAllocationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function isHeldByLoket(): bool
    {
        return in_array($this, [self::Accepted, self::Completed], true);
    }
}
