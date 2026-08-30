<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using a username and password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('email is not an authentication credential', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'username' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
});

test('users cannot authenticate with an invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('users cannot authenticate with an unknown username', function () {
    $this->post(route('login.store'), [
        'username' => 'tidak-terdaftar',
        'password' => 'password',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('inactive users cannot authenticate', function () {
    $user = User::factory()->inactive()->create();

    $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'password',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->username, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
