<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define a custom authentication guard named 'remote-sanctum'
        Auth::viaRequest('remote-sanctum', function (Request $request) {
            $token = $request->bearerToken();

            if (!$token) {
                return null; // No token provided, request is unauthenticated
            }

            try {
                // Forward the token to the Primary App's validation endpoint
                $response = Http::withToken($token)
                    ->timeout(3) // Keep timeout low for performance
                    ->get(config('services.bims.auth_url'));

                if ($response->successful()) {
                    $userData = $response->json();

                    $user = User::firstOrCreate(
                        ['email' => $userData['data']['email']],
                        [
                            'name' => $userData['data']['name'],
                            'password' => bcrypt(str()->random(16)), // Dummy password
                            'role' => 'user',
                        ]
                    );

                    // Return a GenericUser instance populated with the Primary App's data
                    return new GenericUser($userData['data']);

                }
                if ($response->failed()) {
                    Log::error('Remote Sanctum authentication failed: ' . $response->body());
                    return null;
                }
            } catch (\Exception $e) {
                // Log error or handle primary app downtime
                Log::error('Remote Sanctum authentication failed: ' . $e->getMessage());
                return null;
            }

            return null;
        });
    }
}
