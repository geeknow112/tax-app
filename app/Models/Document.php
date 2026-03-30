<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'fiscal_year_id', 'type', 'document_number', 'issue_date', 'due_date',
        'client_name', 'client_address', 'subject', 'subtotal', 'tax', 'total',
        'status', 'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public const TYPES = [
        'estimate' => '見積書',
        'order' => '発注書',
        'invoice' => '請求書',
        'delivery' => '納品書',
    ];

    public const STATUSES = [
        'draft' => '下書き',
        'sent' => '送付済',
        'paid' => '入金済',
        'cancelled' => 'キャンセル',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentItem::class)->orderBy('sort_order');
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function recalculate(): void
    {
        $subtotal = $this->items()->sum('amount');
        $tax = (int) round($subtotal * 0.1);
        $this->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
        ]);
    }

    public static function generateNumber(string $type, int $year): string
    {
        $prefix = match($type) {
            'estimate' => 'EST',
            'order' => 'ORD',
            'invoice' => 'INV',
            'delivery' => 'DLV',
            default => 'DOC',
        };
        $count = self::where('type', $type)
            ->whereYear('issue_date', $year)
            ->count() + 1;
        return sprintf('%s-%d-%04d', $prefix, $year, $count);
    }
}
