<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\Service;

class BookingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bookings = Booking::factory(100)->create();

        // Seed services
        $services = Service::all();

        // Attach random services to each booking
        $bookings->each(function ($booking) use ($services) {
            $booking->services()->attach($services->random(rand(1, 5))); // Adjust the number of services attached as needed
        });
    }
}
