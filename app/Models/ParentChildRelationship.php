<?php

namespace App\Models;

use Database\Factories\ParentChildRelationshipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ParentChildRelationship extends Pivot
{
    /** @use HasFactory<ParentChildRelationshipFactory> */
    use HasFactory;

    protected $table = 'parent_child_relationships';

    public $incrementing = true;

    protected $fillable = [
        'parent_user_id',
        'child_user_id',
        'relationship_type',
        'is_primary_contact',
        'can_manage_pin',
        'can_view_progress',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_primary_contact' => 'boolean',
            'can_manage_pin' => 'boolean',
            'can_view_progress' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
