<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depreciation extends Model
{
    protected $fillable = [
        'entity_id', 'fiscal_year_id', 'asset_name', 'acquisition_date', 'acquisition_cost',
        'useful_life', 'method', 'depreciation_amount',
        'accumulated_depreciation', 'book_value', 'memo',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    /**
     * 定額法の年間償却額を計算
     */
    public function calcStraightLine(): int
    {
        if ($this->useful_life <= 0) return 0;
        return (int) floor($this->acquisition_cost / $this->useful_life);
    }

    /**
     * 償却額を再計算して保存
     */
    public function recalculate(): void
    {
        $annual = $this->calcStraightLine();
        $yearsElapsed = $this->fiscalYear->year - $this->acquisition_date->year;
        if ($yearsElapsed < 0) $yearsElapsed = 0;

        $accumulatedPrev = min($annual * $yearsElapsed, $this->acquisition_cost - 1);
        $remaining = $this->acquisition_cost - $accumulatedPrev;
        $currentDepreciation = min($annual, $remaining - 1);
        if ($currentDepreciation < 0) $currentDepreciation = 0;

        $this->depreciation_amount = $currentDepreciation;
        $this->accumulated_depreciation = $accumulatedPrev + $currentDepreciation;
        $this->book_value = $this->acquisition_cost - $this->accumulated_depreciation;
    }
}
