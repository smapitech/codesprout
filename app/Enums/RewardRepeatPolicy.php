<?php

namespace App\Enums;

enum RewardRepeatPolicy: string
{
    case OncePerSource = 'once_per_source';
    case OncePerDay = 'once_per_day';
    case Repeatable = 'repeatable';
    case Limited = 'limited';
}
