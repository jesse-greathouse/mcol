<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            NetworkSeeder::class,
            ChannelSeeder::class,
            NickSeeder::class,
            ClientSeeder::class,
            InstanceSeeder::class,
            ServerSeeder::class,
            FileExtensionSeeder::class,
        ]);
    }
}
