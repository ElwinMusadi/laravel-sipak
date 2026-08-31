<?php

namespace App;

enum BapVerificationResult: string
{
    case Passed = 'passed';
    case Discrepancy = 'discrepancy';
}
