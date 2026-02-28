<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['id' => 1],
            [
                'name'     => 'Admin',
                'email'    => 'luyendt2k8@gmail.com',
                'password' => bcrypt('Kem@1234'),
            ]
        );
    }
}
