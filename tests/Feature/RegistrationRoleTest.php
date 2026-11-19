<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationRoleTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Person',
            'email' => 'new.person@example.com',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ], $overrides);
    }

    public function test_registration_defaults_to_the_author_role(): void
    {
        $this->post('/register', $this->data())->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertSame(UserRole::Author, User::firstWhere('email', 'new.person@example.com')->role);
    }

    public function test_visitors_can_register_as_corporate_subscribers(): void
    {
        $this->post('/register', $this->data(['role' => 'subscriber', 'organization' => 'Acme Engineering']));

        $user = User::firstWhere('email', 'new.person@example.com');
        $this->assertSame(UserRole::Subscriber, $user->role);
        $this->assertSame('Acme Engineering', $user->organization);
    }

    public function test_privileged_roles_cannot_be_self_assigned(): void
    {
        foreach (['editor', 'executive', 'reviewer', 'admin'] as $role) {
            $this->post('/register', $this->data(['role' => $role]))->assertSessionHasErrors('role');
        }

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_login_and_logout_cycle(): void
    {
        $user = User::factory()->author()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
