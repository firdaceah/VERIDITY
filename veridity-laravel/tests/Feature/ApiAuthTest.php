<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('api login returns the standard token response shape for mobile clients', function () {
    $user = User::factory()->create([
        'email' => 'mobile@example.test',
        'password' => Hash::make('secret-password'),
    ]);

    $this
        ->postJson('/api/login', [
            'email' => 'mobile@example.test',
            'password' => 'secret-password',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Login berhasil!')
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => ['id', 'name', 'email'],
            'user' => ['id', 'name', 'email'],
            'access_token',
            'token',
            'token_type',
        ]);
});

test('api register returns the same token response shape as login', function () {
    $this
        ->postJson('/api/register', [
            'name' => 'Mobile User',
            'email' => 'new-mobile@example.test',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])
        ->assertCreated()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Registrasi berhasil!')
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('data.email', 'new-mobile@example.test')
        ->assertJsonPath('user.email', 'new-mobile@example.test')
        ->assertJsonStructure([
            'status',
            'message',
            'data' => ['id', 'name', 'email'],
            'user' => ['id', 'name', 'email'],
            'access_token',
            'token',
            'token_type',
        ]);
});

test('api logout revokes the current token and keeps the standard response shape', function () {
    $user = User::factory()->create();
    $token = $user->createToken('veridity_token');

    $this
        ->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Berhasil logout, token telah dihapus.');

    expect($user->tokens()->count())->toBe(0);
});
