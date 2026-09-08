<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_confirmation_endpoints_are_not_exposed(): void
    {
        $this->get('/confirm-password')->assertNotFound();
    }
}
