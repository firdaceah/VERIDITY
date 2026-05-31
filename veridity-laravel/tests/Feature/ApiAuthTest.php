<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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

test('authenticated user can update profile data and photo', function () {
    Storage::fake('public');
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.test',
    ]);

    $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/profile', [
            'name' => 'New Name',
            'email' => 'new@example.test',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.email', 'new@example.test');

    $this
        ->actingAs($user->fresh(), 'sanctum')
        ->postJson('/api/profile/photo', [
            'photo' => UploadedFile::fake()->createWithContent(
                'avatar.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
            ),
        ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data' => ['profile_photo_url']]);

    expect($user->fresh()->profile_photo_path)->not->toBeNull();
});

test('user can request and complete password reset through api', function () {
    User::factory()->create([
        'email' => 'reset@example.test',
        'password' => Hash::make('old-password'),
    ]);

    $token = $this
        ->postJson('/api/forgot-password', [
            'email' => 'reset@example.test',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->json('dev_reset_token');

    $this
        ->postJson('/api/reset-password', [
            'email' => 'reset@example.test',
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect(Hash::check('new-password', User::where('email', 'reset@example.test')->first()->password))->toBeTrue();
});
