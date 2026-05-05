<?php

namespace Database\Seeders;

use App\Models\AccountCategory;
use App\Models\AllocationRate;
use App\Models\Entity;
use Illuminate\Database\Seeder;

class AllocationRateSeeder extends Seeder
{
    public function run(): void
    {
        $individual = Entity::where('type', 'individual')->first(); // 個人事業
        $corporation = Entity::where('type', 'corporation')->first(); // 法人

        if (!$individual || !$corporation) {
            $this->command->error('事業体が見つかりません');
            return;
        }

        // 按分率設定（法人の比率）
        // 画像から読み取った値: TCS列の按分率
        $rates = [
            '水道光熱費' => 89,
            '通信費' => 89,
            '旅費交通費' => 4,
            '車両費' => 89,
            '消耗品費' => 89,
            '地代家賃' => 76,
            '減価償却費' => 49,
            '接待交際費' => 0,
            '福利厚生費' => 0,
            '福利厚生費(食費)' => 89,
            '損害保険料' => 0,
            '租税公課' => 0,
            '外注工賃' => 60,
            '雑費' => 0,
            '事業主貸' => 1,
        ];

        foreach ($rates as $categoryName => $corpRate) {
            $category = AccountCategory::where('name', $categoryName)->first();
            if (!$category) {
                continue;
            }

            // 法人
            AllocationRate::updateOrCreate(
                ['account_category_id' => $category->id, 'entity_id' => $corporation->id],
                ['rate' => $corpRate]
            );

            // 個人事業（100 - 法人）
            AllocationRate::updateOrCreate(
                ['account_category_id' => $category->id, 'entity_id' => $individual->id],
                ['rate' => 100 - $corpRate]
            );
        }

        $this->command->info('按分率の初期値を設定しました');
    }
}
