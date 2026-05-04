<?php

namespace Database\Seeders;

use App\Models\Entity;
use Illuminate\Database\Seeder;

class EntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Entity::firstOrCreate(
            ['name' => '個人事業'],
            [
                'type' => 'individual',
                'fiscal_year_start' => 1, // 1月始まり
            ]
        );

        Entity::firstOrCreate(
            ['name' => '法人'],
            [
                'type' => 'corporation',
                'fiscal_year_start' => 4, // 4月始まり
            ]
        );
    }
}
