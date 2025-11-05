<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;

class NormalizeClientPhones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:normalize-phones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize all existing client phone numbers to international format (+30)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting phone number normalization...');
        
        // Get all clients
        $clients = Client::all();
        $total = $clients->count();
        $updated = 0;
        
        if ($total === 0) {
            $this->info('No clients found.');
            return 0;
        }
        
        $this->info("Found {$total} clients to process.");
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        foreach ($clients as $client) {
            $originalPhone = $client->getOriginal('telephone');
            
            // Skip if phone is empty
            if (empty($originalPhone)) {
                $bar->advance();
                continue;
            }
            
            // Normalize the phone number
            $normalizedPhone = $this->normalizePhoneNumber($originalPhone);
            
            // Only update if the phone number changed
            if ($originalPhone !== $normalizedPhone) {
                // Use direct DB update to bypass the mutator (avoid recursion)
                \DB::table('clients')
                    ->where('id', $client->id)
                    ->update(['telephone' => $normalizedPhone]);
                
                $updated++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Normalization complete!");
        $this->info("Total clients: {$total}");
        $this->info("Updated: {$updated}");
        $this->info("Unchanged: " . ($total - $updated));
        
        return 0;
    }
    
    /**
     * Normalize phone number to a consistent format
     */
    private function normalizePhoneNumber($phone)
    {
        if (empty($phone)) {
            return $phone;
        }

        // Remove all spaces, dashes, parentheses, and other non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // If it starts with 00, replace with +
        if (substr($phone, 0, 2) === '00') {
            $phone = '+' . substr($phone, 2);
        }
        
        // If it starts with +30, keep it as is
        if (substr($phone, 0, 3) === '+30') {
            return $phone;
        }
        
        // If it starts with 30, add +
        if (substr($phone, 0, 2) === '30') {
            return '+' . $phone;
        }
        
        // If it starts with 6 or 2 and is 10 digits (Greek mobile/landline), add +30
        if (preg_match('/^[62]\d{9}$/', $phone)) {
            return '+30' . $phone;
        }
        
        // If it doesn't start with +, assume it needs +30 prefix (Greek number)
        if (substr($phone, 0, 1) !== '+') {
            return '+30' . $phone;
        }
        
        return $phone;
    }
}
