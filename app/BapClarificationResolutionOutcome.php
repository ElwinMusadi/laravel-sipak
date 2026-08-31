<?php

namespace App;

enum BapClarificationResolutionOutcome: string
{
    case Resolved = 'resolved';
    case Reopened = 'reopened';

    public function label(): string
    {
        return match ($this) {
            self::Resolved => 'Penyelesaian diterima',
            self::Reopened => 'Perlu klarifikasi ulang',
        };
    }
}
