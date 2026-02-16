<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Modules\Auth\Notifications\ResetPasswordNotification;
use Modules\User\Models\User;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'forgot@example.com',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'forgot@example.com',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Si el correo esta registrado, recibiras un enlace para restablecer tu contrasena.',
            ]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_requires_email(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_returns_200_for_non_existent_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        // Should still return 200 to prevent email enumeration
        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Si el correo esta registrado, recibiras un enlace para restablecer tu contrasena.',
            ]);

        Notification::assertNothingSent();
    }
}
