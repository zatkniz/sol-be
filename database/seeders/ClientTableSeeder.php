<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $email = 'email' . $i . '@example.com';

            while (true) {
                try {
                    $client = new Client();
                    $client->last_name = 'Last Name ' . $i;
                    $client->first_name = 'First Name ' . $i;
                    $client->telephone = '1234567890';
                    $client->email = $email;
                    $client->address = '123 Main Street';
                    $client->comments = null;
                    $client->save();

                    break;
                } catch (Exception $e) {
                    // Email address already exists, so generate a new one
                    $email = 'email' . $i . rand(100000, 999999) . '@example.com';
                }
            }
        }
    }
}
