<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccessControlSeeder::class,
            CodeSproutFoundationSeeder::class,
            CurriculumSeeder::class,
            AssignmentSeeder::class,
            GameSeeder::class,
            RewardSeeder::class,
            TypingSeeder::class,
            HtmlSeeder::class,
        ]);
    }
}
