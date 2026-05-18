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
     * 定額法の月額償却額を計算
     */
    public function calcMonthlyDepreciation(): int
    {
        if ($this->useful_life <= 0) return 0;
        return (int) floor($this->acquisition_cost / $this->useful_life / 12);
    }

    /**
     * 指定年度の償却月数を計算
     * @param int $year 対象年度
     * @return int 償却月数（0〜12）
     */
    public function getDepreciationMonths(int $year): int
    {
        $acquisitionYear = $this->acquisition_date->year;
        $acquisitionMonth = $this->acquisition_date->month;
        
        // 取得年より前の年度は0ヶ月
        if ($year < $acquisitionYear) {
            return 0;
        }
        
        // 取得年は取得月から12月までの月数
        if ($year == $acquisitionYear) {
            return 12 - $acquisitionMonth + 1;
        }
        
        // 取得年より後は12ヶ月
        return 12;
    }

    /**
     * 指定年度までの累計償却額を計算
     * @param int $year 対象年度
     * @param bool $includeCurrent 当年度を含むか
     * @return int 累計償却額
     */
    public function calcAccumulatedDepreciation(int $year, bool $includeCurrent = true): int
    {
        $acquisitionYear = $this->acquisition_date->year;
        $monthly = $this->calcMonthlyDepreciation();
        $maxDepreciation = $this->acquisition_cost - 1; // 備忘価額1円を残す
        
        $accumulated = 0;
        $endYear = $includeCurrent ? $year : $year - 1;
        
        for ($y = $acquisitionYear; $y <= $endYear; $y++) {
            $months = $this->getDepreciationMonths($y);
            $yearDepreciation = $monthly * $months;
            
            // 最終年度調整：備忘価額1円を残す
            if ($accumulated + $yearDepreciation > $maxDepreciation) {
                $accumulated = $maxDepreciation;
                break;
            }
            $accumulated += $yearDepreciation;
        }
        
        return $accumulated;
    }

    /**
     * 償却額を再計算して保存
     */
    public function recalculate(): void
    {
        $currentYear = $this->fiscalYear->year;
        $monthly = $this->calcMonthlyDepreciation();
        $months = $this->getDepreciationMonths($currentYear);
        
        // 前年度までの累計償却額
        $accumulatedPrev = $this->calcAccumulatedDepreciation($currentYear, false);
        
        // 当年度の償却額
        $maxDepreciation = $this->acquisition_cost - 1; // 備忘価額1円を残す
        $remaining = $maxDepreciation - $accumulatedPrev;
        $currentDepreciation = min($monthly * $months, $remaining);
        if ($currentDepreciation < 0) $currentDepreciation = 0;

        $this->depreciation_amount = $currentDepreciation;
        $this->accumulated_depreciation = $accumulatedPrev + $currentDepreciation;
        $this->book_value = $this->acquisition_cost - $this->accumulated_depreciation;
    }
}
