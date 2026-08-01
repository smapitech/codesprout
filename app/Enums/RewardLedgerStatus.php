<?php

namespace App\Enums;

enum RewardLedgerStatus: string
{
    case Awarded = 'awarded';
    case Reversed = 'reversed';
    case Adjustment = 'adjustment';
}
