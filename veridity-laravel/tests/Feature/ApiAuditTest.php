<?php

use App\Models\ForensicAnalysis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('authenticated user can analyze a document through the audits endpoint using configured python engine url', function () {
    config()->set('services.veridity.python_engine_url', 'http://python-engine.test');
    Http::fake([
        'http://python-engine.test/analyze-document' => Http::response([
            'status' => 'success',
            'summary_label' => 'MIXED TEXT',
            'summary_color' => 'warning',
            'final_score' => 62.5,
            'results' => [],
            'classification_map' => [
                'Kalimat ini kemungkinan dibantu AI.' => 'AI-generated',
            ],
        ]),
    ]);

    $user = User::factory()->create();
    $file = UploadedFile::fake()->createWithContent('sample.pdf', '%PDF-1.4 sample document');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/audits', [
            'image' => $file,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.summary_label', 'MIXED TEXT')
        ->assertJsonPath('data.summary_color', 'warning')
        ->assertJsonPath('data.final_score', 62.5);

    Http::assertSent(fn ($request) => $request->url() === 'http://python-engine.test/analyze-document');
});

test('legacy analyze and history endpoints remain available as mobile compatibility aliases', function () {
    $user = User::factory()->create();

    ForensicAnalysis::create([
        'user_id' => $user->id,
        'image_name' => 'sample.pdf',
        's3_path' => 'uploads/sample.pdf',
        'ela_score' => 0,
        'is_deepfake' => false,
        'metadata_details' => ['summary' => ['verdict' => 'MIXED TEXT']],
        'noise_status' => 'Not Applicable',
        'final_result' => [
            'summary_label' => 'MIXED TEXT',
            'summary_color' => 'warning',
            'full_report' => [
                'final_score' => 62.5,
                'classification_map' => [],
            ],
        ],
    ]);

    $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/history')
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(1, 'data');
});

test('authenticated user can view and delete only their own audit records', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $ownAudit = ForensicAnalysis::create([
        'user_id' => $user->id,
        'image_name' => 'own.pdf',
        's3_path' => 'uploads/own.pdf',
        'ela_score' => 0,
        'is_deepfake' => false,
        'metadata_details' => ['summary' => ['verdict' => 'MIXED TEXT']],
        'noise_status' => 'Not Applicable',
        'final_result' => [
            'summary_label' => 'MIXED TEXT',
            'summary_color' => 'warning',
            'full_report' => ['final_score' => 60],
        ],
    ]);

    $otherAudit = ForensicAnalysis::create([
        'user_id' => $otherUser->id,
        'image_name' => 'other.pdf',
        's3_path' => 'uploads/other.pdf',
        'ela_score' => 0,
        'is_deepfake' => false,
        'metadata_details' => ['summary' => ['verdict' => 'MIXED TEXT']],
        'noise_status' => 'Not Applicable',
        'final_result' => [
            'summary_label' => 'MIXED TEXT',
            'summary_color' => 'warning',
            'full_report' => ['final_score' => 60],
        ],
    ]);

    $this
        ->actingAs($user, 'sanctum')
        ->getJson("/api/audits/{$ownAudit->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $ownAudit->id);

    $this
        ->actingAs($user, 'sanctum')
        ->getJson("/api/audits/{$otherAudit->id}")
        ->assertNotFound();

    $this
        ->actingAs($user, 'sanctum')
        ->deleteJson("/api/audits/{$ownAudit->id}")
        ->assertOk()
        ->assertJsonPath('status', 'success');

    expect(ForensicAnalysis::query()->whereKey($ownAudit->id)->exists())->toBeFalse();
});

test('authenticated user can download a document audit report as pdf through api', function () {
    config()->set('services.veridity.python_engine_url', 'http://python-engine.test');
    Http::fake([
        'http://python-engine.test/generate-pdf-report' => Http::response('%PDF-1.4 fake report', 200, [
            'Content-Type' => 'application/pdf',
        ]),
    ]);

    $user = User::factory()->create();
    Storage::disk('public')->put('uploads/document.pdf', '%PDF-1.4 source');

    $audit = ForensicAnalysis::create([
        'user_id' => $user->id,
        'image_name' => 'document.pdf',
        's3_path' => 'uploads/document.pdf',
        'ela_score' => 0,
        'is_deepfake' => false,
        'metadata_details' => ['summary' => ['verdict' => 'MIXED TEXT']],
        'noise_status' => 'Not Applicable',
        'final_result' => [
            'summary_label' => 'MIXED TEXT',
            'summary_color' => 'warning',
            'full_report' => [
                'final_score' => 62.5,
                'classification_map' => [
                    'Kalimat ini kemungkinan dibantu AI.' => 'AI-generated',
                ],
            ],
        ],
    ]);

    $this
        ->actingAs($user, 'sanctum')
        ->getJson("/api/audits/{$audit->id}/report")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});
