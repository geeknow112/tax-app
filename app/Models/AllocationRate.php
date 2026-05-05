<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllocationRate extends Model
{
    protected $fillable = ['account_category_id', 'entity_id', 'rate'];

    protected $casts = [
        'rate' => 'decimal:2',
    ];

    public function accountCategory(): BelongsTo
    {
        return $this->belongsTo(AccountCategory::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
