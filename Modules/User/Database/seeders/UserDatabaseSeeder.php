<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Modules\User\Models\User;

class UserDatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $this->call([
            UserSeeder::class,
        ]);
        Log::info('UserDatabaseSeeder executed successfully.');
    }
}
