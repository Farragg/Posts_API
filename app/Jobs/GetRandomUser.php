<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetRandomUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Make the HTTP request to the random user API
        $response = Http::get('https://randomuser.me/api/');

        if ($response->successful()) {
            Log::info('Random User Data:', $response->json('results'));
        } else {
            Log::error('Failed to fetch random user data', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
