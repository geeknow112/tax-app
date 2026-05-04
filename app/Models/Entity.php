<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends Model
{
    protected $fillable = ['name', 'type', 'fiscal_year_start'];

    /**
     * 個人事業かどうか
     */
    public function isIndividual(): bool
    {
        return $this->type === 'individual';
    }

    /**
     * 法人かどうか
     */
    public function isCorporation(): bool
    {
        return $this->type === 'corporation';
    }

    /**
     * 指定年の決算期間を取得
     * @param int $year 決算年度（法人の場合は期末年）
     * @return array ['start' => Carbon, 'end' => Carbon]
     */
    public function getFiscalPeriod(int $year): array
    {
        if ($this->fiscal_year_start === 1) {
            // 1月始まり（個人事業）: 1/1 〜 12/31
            return [
                'start' => \Carbon\Carbon::create($year, 1, 1),
                'end' => \Carbon\Carbon::create($year, 12, 31),
            ];
        } else {
            // 例: 4月始まり（法人）: 前年4/1 〜 当年3/31
            $startYear = $year - 1;
            return [
                'start' => \Carbon\Carbon::create($startYear, $this->fiscal_year_start, 1),
                'end' => \Carbon\Carbon::create($year, $this->fiscal_year_start, 1)->subDay(),
            ];
        }
    }

    public function fiscalYears(): HasMany
    {
        return $this->hasMany(FiscalYear::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(Depreciation::class);
    }

    public function accountCategories(): HasMany
    {
        return $this->hasMany(AccountCategory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
