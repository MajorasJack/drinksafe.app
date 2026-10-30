<?php

use Inertia\Testing\AssertableInertia;

test('the registration page does not exist', function () {
    $this->getJson('/register')->assertNotFound();
});

test('nobody can register an account', function () {
    $password = fake()->password(minLength: 12);

    $this->postJson('/register', [
        'name' => fake()->name(),
        'email' => fake()->safeEmail(),
        'password' => $password,
        'password_confirmation' => $password,
    ])->assertNotFound();

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
});

test('the login page does not offer registration', function () {
    $this->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('auth/Login')->missing('canRegister'));
});
