<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Black-box API smoke tests that need no database/tenant — they exercise routing,
 * validation and auth gates. Safe to run anywhere (`php artisan test`).
 */
class ApiSmokeTest extends TestCase
{
    /** Login validates its input (no DB needed). */
    public function test_login_requires_credentials(): void
    {
        $this->postJson('/api/login', [])->assertStatus(422);
    }

    /** App-facing identity endpoint is protected. */
    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    /** Tenant billing overview requires authentication. */
    public function test_billing_overview_requires_authentication(): void
    {
        $this->getJson('/api/billing/overview')->assertStatus(401);
    }
}
