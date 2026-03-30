<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentItem extends Model
{
    protected $fillable = [
        'document_id', 'description', 'quantity', 'unit', 'unit_price', 'amount', 'sort_order',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    protected static function booted(): void
    {
        static::saving(function ($item) {
            $item->amount = $item->quantity * $item->unit_price;
        });

        static::saved(function ($item) {
            $item->document->recalculate();
        });

        static::deleted(function ($item) {
            $item->document->recalculate();
        });
    }
}
