<?php

use App\Models\User;
use Illuminate\Support\Facades\Password;

test('forgot password screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('password reset link can be requested', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => $user->email,
    ]);
});

test('reset password screen can be rendered', function () {
    $user = User::factory()->create();

    $token = Password::createToken($user);

    $response = $this->get('/reset-password/'.$token.'?email='.$user->email);

    $response->assertStatus(200);
});

test('password can be reset with valid token', function () {
    $user = User::factory()->create();

    $token = Password::createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertRedirect('/login');

    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
});
