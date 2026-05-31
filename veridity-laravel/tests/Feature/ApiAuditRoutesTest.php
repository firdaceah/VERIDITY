<?php

use App\Models\ForensicAnalysis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns authenticated audit history through the canonical audits endpoint', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    ForensicAnalysis::create([
        'user_id' => $user->id,
        'image_name' => 'owned.pdf',
        's3_path' => 'uploads/owned.pdf',
        'ela_score' => 0,
        'is_deepfake' => false,
        'metadata_details' => ['summary' => ['verdict' => 'MIXED TEXT']],
        'noise_status' => 'Not Applicable',
        'final_result' => [
            'summary_label' => 'MIXED TEXT',
            'summary_color' => 'warning',
            'full_report' => ['final_score' => 52],
        ],
    ]);

    ForensicAnalysis::create([
        'user_id' => $otherUser->id,
        'image_name' => 'other.pdf',
        's3_path' => 'uploads/other.pdf',
        'final_result' => [
            'summary_label' => 'FULL AI',
            'summary_color' => 'danger',
            'full_report' => ['final_score' => 91],
        ],
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/audits')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.file_name', 'owned.pdf')
        ->assertJsonMissing(['file_name' => 'other.pdf']);
});

it('shows and deletes only audits owned by the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownedAudit = ForensicAnalysis::create([
        'user_id' => $user->id,
        'image_name' => 'owned.jpg',
        's3_path' => 'uploads/owned.jpg',
        'final_result' => [
            'summary_label' => 'FOTO ASLI / JEPRETAN MURNI',
            'summary_color' => 'success',
            'full_report' => ['final_score' => 12],
        ],
    ]);

    $otherAudit = ForensicAnalysis::create([
        'user_id' => $otherUser->id,
        'image_name' => 'other.jpg',
        's3_path' => 'uploads/other.jpg',
        'final_result' => [
            'summary_label' => 'SANGAT BERBAHAYA',
            'summary_color' => 'danger',
            'full_report' => ['final_score' => 98],
        ],
    ]);

    Sanctum::actingAs($user);

    $this->getJson("/api/audits/{$ownedAudit->id}")
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $ownedAudit->id);

    $this->getJson("/api/audits/{$otherAudit->id}")
        ->assertNotFound();

    $this->deleteJson("/api/audits/{$otherAudit->id}")
        ->assertForbidden();

    $this->deleteJson("/api/audits/{$ownedAudit->id}")
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect(ForensicAnalysis::find($ownedAudit->id))->toBeNull();
    expect(ForensicAnalysis::find($otherAudit->id))->not->toBeNull();
});

it('uses the configured python engine url when analyzing documents', function () {
    config(['services.veridity.python_engine_url' => 'http://python-engine.test']);
    Http::preventStrayRequests();
    Http::fake([
        '*' => Http::response([
            'status' => 'success',
            'summary_label' => 'MIXED TEXT',
            'summary_color' => 'warning',
            'final_score' => 47,
            'results' => [],
            'classification_map' => [
                'Kalimat ini dibantu oleh AI.' => 'Human-written & AI-refined',
            ],
        ], 200),
    ]);

    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/audits', [
        'image' => UploadedFile::fake()->create('sample.pdf', 32, 'application/pdf'),
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.summary_label', 'MIXED TEXT');

    Http::assertSent(fn ($request) => $request->url() === 'http://python-engine.test/analyze-document');
});
