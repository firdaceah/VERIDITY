@php
    $checks = $validation['checks'] ?? [];
    $statusColor = [
        'passed' => 'var(--green)',
        'failed' => 'var(--red)',
        'review_required' => 'var(--yellow)',
        'error' => 'var(--red)',
    ];
@endphp

<div style="background:#fff; border:1px solid var(--border); border-radius:16px; padding:22px;">
    <div style="font-size:11px; color:var(--muted); font-weight:800; letter-spacing:1px; text-transform:uppercase;">Detail Validasi Isi Nota</div>
    <h3 style="font-size:20px; margin-top:4px;">{{ $validation['summary'] ?? 'Belum ada detail validasi.' }}</h3>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:12px; margin-top:18px;">
        @forelse ($checks as $key => $check)
            @php $checkStatus = $check['status'] ?? 'review_required'; @endphp
            <div style="background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px;">
                <div style="font-size:11px; color:var(--muted); font-weight:800; text-transform:uppercase; margin-bottom:8px;">
                    {{ str_replace('_', ' ', $key) }}
                </div>
                <div style="font-size:13px; font-weight:800; color:{{ $statusColor[$checkStatus] ?? 'var(--muted)' }}; text-transform:uppercase;">
                    {{ str_replace('_', ' ', $checkStatus) }}
                </div>
                <p style="font-size:12px; color:var(--navy2); line-height:1.6; margin-top:8px;">
                    {{ $check['message'] ?? '-' }}
                </p>
                @if (!empty($check['expected']))
                    <div style="font-size:11px; color:var(--muted); margin-top:8px;">Expected: {{ $check['expected'] }}</div>
                @endif
            </div>
        @empty
            <div style="font-size:13px; color:var(--muted);">Belum ada item validasi.</div>
        @endforelse
    </div>

    @if (!empty($validation['ocr_text']))
        <details style="margin-top:18px;">
            <summary style="cursor:pointer; font-size:12px; font-weight:800; color:var(--accent);">Lihat teks OCR</summary>
            <pre style="white-space:pre-wrap; background:var(--card); border:1px solid var(--border); border-radius:12px; padding:14px; margin-top:10px; font-size:12px; color:var(--navy2);">{{ $validation['ocr_text'] }}</pre>
        </details>
    @endif
</div>
