<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DepartmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Vincent Eleazar Uykieng',
            'email' => 'vincenteleazar.uykieng04@gmail.com',
            'password' => Hash::make('testtest'),
        ]);
    }
}
