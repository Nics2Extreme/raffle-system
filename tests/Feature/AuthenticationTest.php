<?php

use App\Models\User;

test('guests are redirected to login', function () {
    $this->get(route('draw.index'))->assertRedirect(route('login'));
    $this->get(route('participants.index'))->assertRedirect(route('login'));
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('operators can authenticate and reach the draw stage', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('draw.index'));

    $this->assertAuthenticated();

    $this->get(route('draw.index'))->assertOk();
});

test('operators can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('invalid credentials fail authentication', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
