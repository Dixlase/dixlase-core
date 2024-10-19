<?php

namespace Database\Seeders;

use App\Models\User;
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
            UsersTableSeeder::class,
            AdminsTableSeeder::class,
            ApplicationsTableSeeder::class,
            ApplicationDetailTableSeeder::class,
            EventsTableSeeder::class,
            EventSortsTableSeeder::class,
            EventTimetablesTableSeeder::class,
            FilesTableSeeder::class,
            OptionsTableSeeder::class,
            OptionCategoriesTableSeeder::class,
            SettingFrontTableSeeder::class,
            SettingSystemTableSeeder::class,
            SlotsTableSeeder::class,
            SlotCategoriesTableSeeder::class,
            SlotEventsTableSeeder::class,
            SlotSchedulesTableSeeder::class
        ]);
    }
}
