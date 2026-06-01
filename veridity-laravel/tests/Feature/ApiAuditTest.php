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
        'http://python-engine.test/generate-pdf-report' => Http::response('%PDF-1.4 generated report', 200, [
            'Content-Type' => 'application/pdf',
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

    $analysis = ForensicAnalysis::query()->first();
    expect($analysis->report_status)->toBe('ready');
    expect($analysis->report_pdf_path)->not->toBeNull();
    Storage::disk('public')->assertExists($analysis->report_pdf_path);
});

test('docx upload is rejected so document analysis remains pdf only', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/audits', [
            'image' => UploadedFile::fake()->create(
                'sample.docx',
                32,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');
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

test('mobile report download accepts sanctum token query for external browser', function () {
    config()->set('services.veridity.python_engine_url', 'http://python-engine.test');
    Http::fake([
        'http://python-engine.test/generate-pdf-report' => Http::response('%PDF-1.4 fake report', 200, [
            'Content-Type' => 'application/pdf',
        ]),
    ]);

    $user = User::factory()->create();
    Storage::disk('public')->put('uploads/mobile-document.pdf', '%PDF-1.4 source');

    $audit = ForensicAnalysis::create([
        'user_id' => $user->id,
        'image_name' => 'mobile-document.pdf',
        's3_path' => 'uploads/mobile-document.pdf',
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

    $token = $user->createToken('mobile')->plainTextToken;

    $this
        ->get("/api/audits/{$audit->id}/report-mobile?token=".urlencode($token))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('image audit can download summary pdf without python document report endpoint', function () {
    Http::fake();

    $user = User::factory()->create();

    $audit = ForensicAnalysis::create([
        'user_id' => $user->id,
        'image_name' => 'photo.jpg',
        's3_path' => 'uploads/photo.jpg',
        'ela_score' => 12.5,
        'is_deepfake' => false,
        'metadata_details' => ['summary' => ['verdict' => 'KAMERA FISIK REAL']],
        'noise_status' => 'Normal',
        'final_result' => [
            'summary_label' => 'AUTHENTIC',
            'summary_color' => 'success',
            'full_report' => [
                'final_score' => 82.5,
                'results' => [
                    'ai_detection' => ['metrics' => ['gan_score' => 0.1]],
                    'metadata' => ['summary' => ['verdict' => 'KAMERA FISIK REAL', 'authenticity_score' => 90]],
                    'noise' => ['interpretation' => 'Normal.'],
                ],
            ],
        ],
    ]);

    $this
        ->actingAs($user, 'sanctum')
        ->get("/api/audits/{$audit->id}/report")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    Http::assertNothingSent();
});

test('report download reuses stored pdf instead of generating different output', function () {
    Http::preventStrayRequests();

    $user = User::factory()->create();
    Storage::disk('public')->put('reports/'.$user->id.'/stored.pdf', '%PDF-1.4 stored report');

    $audit = ForensicAnalysis::create([
        'user_id' => $user->id,
        'image_name' => 'document.pdf',
        's3_path' => 'uploads/document.pdf',
        'ela_score' => 0,
        'is_deepfake' => false,
        'metadata_details' => ['summary' => ['verdict' => 'MIXED TEXT']],
        'noise_status' => 'Not Applicable',
        'report_pdf_path' => 'reports/'.$user->id.'/stored.pdf',
        'report_status' => 'ready',
        'report_version' => 4,
        'report_generated_at' => now(),
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
        ->get("/api/audits/{$audit->id}/report")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});
