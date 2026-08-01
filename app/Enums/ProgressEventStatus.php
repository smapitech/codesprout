<?php

namespace App\Enums;

enum ProgressEventStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Failed = 'failed';
    case Ignored = 'ignored';
}
