<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Modules\Auth\Notifications\VerifyEmailNotification;
use Modules\User\Models\User;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    public function test_authenticated_user_can_request_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/email/verification-notification');

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Se ha enviado un enlace de verificacion a tu correo.',
            ]);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_unauthenticated_user_cannot_request_verification(): void
    {
        $response = $this->postJson('/api/auth/email/verification-notification');

        $response->assertUnauthorized();
    }

    public function test_already_verified_user_gets_message(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/email/verification-notification');

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Tu correo ya ha sido verificado.',
            ]);

        Notification::assertNothingSent();
    }

    public function test_user_can_verify_email_with_valid_hash(): void
    {
        $user = User::factory()->create([
            'email' => 'toverify@example.com',
            'email_verified_at' => null,
            'status' => 'active',
        ]);

        $hash = sha1($user->email);

        $response = $this->getJson("/api/auth/email/verify/{$user->id}/{$hash}");

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Tu correo ha sido verificado exitosamente.',
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_invalid_hash_returns_403(): void
    {
        $user = User::factory()->create([
            'email' => 'badhash@example.com',
            'email_verified_at' => null,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/auth/email/verify/{$user->id}/invalidhash");

        $response->assertForbidden()
            ->assertJsonFragment([
                'message' => 'Enlace de verificacion invalido.',
            ]);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_already_verified_user_returns_message_on_verify(): void
    {
        $user = User::factory()->create([
            'email' => 'alreadyverified@example.com',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $hash = sha1($user->email);

        $response = $this->getJson("/api/auth/email/verify/{$user->id}/{$hash}");

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Tu correo ya ha sido verificado.',
            ]);
    }

    public function test_register_sends_verification_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'securepassword',
        ]);

        $response->assertOk();

        $user = User::where('email', 'newuser@example.com')->first();
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }
}
