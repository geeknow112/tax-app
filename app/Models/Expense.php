<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'entity_id', 'fiscal_year_id', 'date', 'vendor_name', 'description',
        'amount', 'payment_method', 'account_category_id', 'memo',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function accountCategory(): BelongsTo
    {
        return $this->belongsTo(AccountCategory::class);
    }

    public function isClassified(): bool
    {
        return $this->account_category_id !== null;
    }
}
