<?php

namespace App\Enums;

enum BadgeAwardStatus: string
{
    case Earned = 'earned';
    case Withdrawn = 'withdrawn';
}
