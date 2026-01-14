<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Practitioner;

class PractitionerSeeder extends Seeder
{
    public function run()
    {
        Practitioner::create([
            'name_pract' => 'Buna',
            'identifier_pract' => '252525',
            'role_pract' => 'admin',
            'password' => Hash::make('worker321'),
            'no_wa' => '085156186177',
        ]);

        Practitioner::create([
            'name_pract' => 'Prabski',
            'identifier_pract' => '242424',
            'role_pract' => 'perawat',
            'password' => Hash::make('worker123'),
            'no_wa' => '085190083495',
        ]);
    }
}
