<?php

namespace Database\Seeders;

use App\Models\Curriculum;
use App\Models\LearningClass;
use App\Models\User;
use App\Services\Curriculum\CurriculumImportService;
use Database\Seeders\Data\CodeSproutCurriculumSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $importService = app(CurriculumImportService::class);

        DB::transaction(function () use ($importService): void {
            $curriculum = $importService->import(CodeSproutCurriculumSeedData::build(), dryRun: false);

            if ($admin = User::query()->where('email', 'admin@childsbridge.test')->first()) {
                Curriculum::query()
                    ->whereKey($curriculum->getKey())
                    ->update([
                        'created_by' => $admin->id,
                        'updated_by' => $admin->id,
                    ]);
            }

            $keyboardIsland = $curriculum
                ->worlds()
                ->where('slug', 'keyboard-island')
                ->first();

            if ($keyboardIsland) {
                LearningClass::query()
                    ->where('class_code', 'CB-KEY-01')
                    ->update(['curriculum_world_id' => $keyboardIsland->getKey()]);
            }
        });
    }
}
