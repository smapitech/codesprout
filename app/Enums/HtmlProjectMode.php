<?php

namespace App\Enums;

enum HtmlProjectMode: string
{
    case GuidedBlocks = 'guided_blocks';
    case GuidedCode = 'guided_code';
    case SyncedBlocksCode = 'synced_blocks_code';
    case StructuredFreeCode = 'structured_free_code';

    public static function values(): array
    {
        return array_map(static fn (self $mode): string => $mode->value, self::cases());
    }
}
