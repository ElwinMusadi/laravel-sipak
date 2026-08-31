<?php

namespace App;

enum BapVerificationStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
