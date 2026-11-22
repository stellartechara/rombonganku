<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'bapak@gmail.com'],
            [
                'name' => 'Pak Sopir',
                'password' => 'ade123',
            ]
        );
    }
}