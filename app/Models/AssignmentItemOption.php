<?php

namespace App\Models;

use Database\Factories\AssignmentItemOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentItemOption extends Model
{
    /** @use HasFactory<AssignmentItemOptionFactory> */
    use HasFactory;

    protected $fillable = [
        'assignment_item_id',
        'option_text',
        'image_path',
        'option_value',
        'is_correct',
        'matching_key',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AssignmentItem::class, 'assignment_item_id');
    }
}
