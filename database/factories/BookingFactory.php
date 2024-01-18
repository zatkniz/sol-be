<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get random client and user IDs
        $clientId = \App\Models\Client::inRandomOrder()->first()->id;
        $userId = \App\Models\User::inRandomOrder()->first()->id;

        // Set the month and year for all events
        $month = 1; // January
        $year = 2024;

        // Generate a random day for each event in January 2024
        $day = $this->faker->numberBetween(1, 31);

        // Generate random time
        $time = $this->faker->time('H:i:s', '00:15:00');

        // Combine date and time into a DateTime object
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', "{$year}-{$month}-{$day} {$time}");

        return [
            'client_id' => $clientId,
            'user_id' => $userId,
            'duration' => $this->faker->time('H:i:s', '00:15:00'),
            'cost' => $this->faker->randomFloat(2, 5, 30),
            'date' => $dateTime->format('Y-m-d'),
            'time' => $dateTime->format('H:i:s'),
        ];
    }
}
