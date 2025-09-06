<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_email()
    {
        $password = 'secret1234';
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'username' => 'jdoe',
            'password' => bcrypt($password),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'user@example.com',
            'password' => $password,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_username()
    {
        $password = 'secret1234';
        $user = User::factory()->create([
            'email' => 'user2@example.com',
            'username' => 'janedoe',
            'password' => bcrypt($password),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login' => 'janedoe',
            'password' => $password,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login()
    {
        $password = 'secret1234';
        User::factory()->create([
            'email' => 'inactive@example.com',
            'username' => 'inactiveuser',
            'password' => bcrypt($password),
            'is_active' => false,
        ]);

        $response = $this->from('/login')->post('/login', [
            'login' => 'inactiveuser',
            'password' => $password,
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }
}
