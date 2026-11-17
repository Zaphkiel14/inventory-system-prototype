<?php

namespace Database\Seeders;

use App\Models\Departments;
use App\Models\Sections;
use App\Models\Units;
use Illuminate\Database\Seeder;

class OrganizationalStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Departments::factory()
            ->count(3)
            ->create()
            ->each(function (Departments $department) {
                Sections::factory()
                    ->count(3)
                    ->for($department, 'department')
                    ->create()
                    ->each(function (Sections $section) {
                        Units::factory()
                            ->count(3)
                            ->for($section, 'section')
                            ->create();
                    });
            });
    }
}
