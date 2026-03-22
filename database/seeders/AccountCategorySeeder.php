<?php

namespace Database\Seeders;

use App\Models\AccountCategory;
use Illuminate\Database\Seeder;

class AccountCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            '保険料', '旅費交通費', '通信費', '食費', '水道光熱費',
            '消耗品費', '備品', '地代家賃', '福利厚生費', '接待交際費',
            '租税公課', '租税公課_事業所得', '書籍費', '事業主貸', '医療費',
            '雑費', '車両費', '法人経費', '法人_通信費',
            '法人_旅費交通費', '法人_接待交際費',
            '福利厚生費(食費)', '福利厚生費(zaim食費)', '外注工賃',
            '減価償却費', '証券購入費', '損害保険料',
        ];

        foreach ($categories as $i => $name) {
            AccountCategory::firstOrCreate(
                ['name' => $name],
                ['sort_order' => $i]
            );
        }
    }
}
