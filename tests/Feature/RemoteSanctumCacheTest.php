<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RemoteSanctumCacheTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_successful_authentication_caches_user_and_prevents_duplicate_http_requests(): void
    {
        $token = 'test-token-valid-abc123xyz';
        $cacheKey = 'remote_sanctum_user:'.hash('sha256', $token);
        $authUrl = config('services.bims.auth_url');

        Http::fake([
            $authUrl => Http::response([
                'data' => [
                    'name' => 'Eng. John Doe',
                    'email' => 'john.doe@example.com',
                    'bims_id' => 'BIMS-12345',
                ],
            ], 200),
        ]);

        $request1 = Request::create('/test', 'GET', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        // First auth attempt: cache miss, makes HTTP request
        $user1 = Auth::guard('api')->setRequest($request1)->user();

        $this->assertNotNull($user1);
        $this->assertEquals('john.doe@example.com', $user1->email);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals($user1->id, Cache::get($cacheKey));
        Http::assertSentCount(1);

        // Second auth attempt: cache hit, must NOT make an HTTP request
        Auth::guard('api')->forgetUser();
        $request2 = Request::create('/test', 'GET', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        $user2 = Auth::guard('api')->setRequest($request2)->user();

        $this->assertNotNull($user2);
        $this->assertEquals($user1->id, $user2->id);
        // Assert Http was NOT called again
        Http::assertSentCount(1);
    }

    public function test_failed_authentication_does_not_cache_result(): void
    {
        $token = 'test-token-invalid-456';
        $cacheKey = 'remote_sanctum_user:'.hash('sha256', $token);
        $authUrl = config('services.bims.auth_url');

        Http::fake([
            $authUrl => Http::response(['message' => 'Unauthenticated'], 401),
        ]);

        $request = Request::create('/test', 'GET', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        $user = Auth::guard('api')->setRequest($request)->user();

        $this->assertNull($user);
        $this->assertFalse(Cache::has($cacheKey));
    }
}
