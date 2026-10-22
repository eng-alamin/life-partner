<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\District;
use App\Models\Division;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds Bangladesh's 8 divisions and 64 districts. Safe to run many times:
 * existing rows are matched by English name and only their Bangla name is updated.
 */
class GeographySeeder extends Seeder
{
    public function run(): void
    {
        $data = require __DIR__.'/data/bd_geography.php';

        DB::transaction(function () use ($data): void {
            foreach ($data as $divisionEn => [$divisionBn, $districts]) {
                $division = Division::query()->updateOrCreate(
                    ['name_en' => $divisionEn],
                    ['name_bn' => $divisionBn],
                );

                foreach ($districts as $districtEn => $districtBn) {
                    District::query()->updateOrCreate(
                        ['division_id' => $division->getKey(), 'name_en' => $districtEn],
                        ['name_bn' => $districtBn],
                    );
                }
            }
        });
    }
}
