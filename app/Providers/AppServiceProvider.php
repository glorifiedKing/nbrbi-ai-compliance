<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

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
                    //->withoutVerifying()
                    ->timeout(10) // Keep timeout low for performance
                    ->get(config('services.bims.auth_url'));

                if ($response->successful()) {
                    // Extract user record (handles {"data": [{"name": "...", "email": "..."}]})
                    $userData = $response->json('data.0') ?? $response->json('data');

                    if (!$userData || empty($userData['email'])) {
                        Log::error('Remote Sanctum user payload missing email: ' . $response->body());

                        return null;
                    }

                    $user = User::firstOrCreate(
                        ['email' => $userData['email']],
                        [
                            'name' => $userData['name'] ?? 'User',
                            'password' => bcrypt(str()->random(16)), // Dummy password
                            'bims_id' => $userData['bims_id'] ?? $userData['id'] ?? null,
                            'role' => 'user',
                        ]
                    );

                    // Sync bims_id if not previously set
                    $bimsId = $userData['bims_id'] ?? $userData['id'] ?? null;
                    if ($bimsId && empty($user->bims_id)) {
                        $user->update(['bims_id' => (string) $bimsId]);
                    }

                    return $user;
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
