<?php

namespace App;

enum BapClarificationStatus: string
{
    case WaitingResponse = 'waiting_response';
    case Responded = 'responded';
    case Resolved = 'resolved';
    case Reopened = 'reopened';

    public function canReceiveResponse(): bool
    {
        return in_array($this, [self::WaitingResponse, self::Reopened], true);
    }
}
