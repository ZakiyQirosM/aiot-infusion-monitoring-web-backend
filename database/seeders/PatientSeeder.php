<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\InfusionSession;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{

    public function run()
    {
        Patient::create([
            'identifier' => '123456789001',
            'name' => 'Hime Kimito',
            'gender' => 'female',
            'age' => 45,
            'location' => '22.22.01'
        ]);

        Patient::create([
            'identifier' => '123456789002',
            'name' => 'jhon doee',
            'gender' => 'male',
            'age' => 38,
            'location' => '22.22.02'
        ]);
    }

}
