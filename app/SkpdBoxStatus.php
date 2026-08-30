<?php

namespace App;

enum SkpdBoxStatus: string
{
    case Available = 'available';
    case PartiallyAllocated = 'partially_allocated';
    case FullyAllocated = 'fully_allocated';
    case Depleted = 'depleted';
}
