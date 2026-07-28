<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'demo@tif-planner.test'],
            [
                'name' => 'TIFデモ',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
