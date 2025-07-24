<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminTablesSeeder::class);
        $this->call(UserAdminMenuSeeder::class);
        $this->call(McpTestDataSeeder::class);
        $this->call(DatabaseConnectionSeeder::class);
    }
}