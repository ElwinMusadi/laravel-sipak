<?php

namespace App;

enum BapCancellationReason: string
{
    case Cancelled = 'cancelled';
    case Damaged = 'damaged';
}
