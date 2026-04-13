<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Saya - VeriDity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-blue: #0d6efd; --bg-light: #f8faff; --sidebar-white: #ffffff; --border-color: #eef2f7; }
        body { background-color: var(--bg-light); font-family: 'Inter', sans-serif; }
        .sidebar { height: 100vh; width: 260px; position: fixed; background: var(--sidebar-white); border-right: 1px solid var(--border-color); padding-top: 25px; }
        .sidebar .nav-link { color: #6c757d; padding: 14px 25px; font-weight: 500; display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .sidebar .nav-link.active { color: var(--primary-blue); background: rgba(13, 110, 253, 0.05); border-right: 4px solid var(--primary-blue); }
        .main-content { margin-left: 260px; padding: 40px; }
        .card-history { background: #fff; border-radius: 16px; border: 1px solid var(--border-color); overflow: hidden; }
        .table thead th { font-size: 0.75rem; text-transform: uppercase; color: #8c98a4; padding: 18px 20px; }
        .badge-soft-success { background: rgba(25, 135, 84, 0.1); color: #198754; }
        .badge-soft-danger { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        .badge-soft-warning { background: rgba(255, 193, 7, 0.1); color: #997404; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="px-4 mb-5"><span class="fw-bold text-primary fs-3">Veri<span class="text-dark">Dity</span></span></div>
        <nav class="nav flex-column">
            <a class="nav-link" href="{{ route('user.dashboard') }}"><i class="bi bi-cloud-arrow-up"></i> Dashboard</a>
            <a class="nav-link active" href="{{ route('user.history') }}"><i class="bi bi-clock-history"></i> Riwayat Saya</a>
            <div class="mt-auto" style="padding-top: 200px;">
                <form action="{{ route('logout') }}" method="POST">@csrf
                    <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100 text-start"><i class="bi bi-box-arrow-left"></i> Keluar</button>
                </form>
            </div>
        </nav>
    </div>

    <div class="main-content">
        <h3 class="fw-bold mb-4">Semua <span class="text-primary">Hasil Audit</span></h3>
        <div class="card-history shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Tanggal</th>
                            <th>Nama File</th>
                            <th class="text-center">Skor ELA</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myAudits as $audit)
                        <tr>
                            <td class="ps-4 small text-muted">{{ $audit->created_at->format('d/m/Y H:i') }}</td>
                            <td class="fw-semibold">{{ Str::limit($audit->image_name, 35) }}</td>
                            <td class="text-center font-mono fw-bold text-primary">{{ $audit->ela_score }}%</td>
                            <td class="text-center">
                                @php $label = $audit->final_result['summary_label'] ?? 'Aman'; @endphp
                                <span class="badge {{ $label == 'Aman' ? 'badge-soft-success' : ($label == 'Sangat Berbahaya' ? 'badge-soft-danger' : 'badge-soft-warning') }} rounded-pill px-3">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('user.result', $audit->id) }}" class="btn btn-sm btn-light border px-3">
                                    <i class="bi bi-eye-fill"></i> Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-5">Kosong. Silakan upload gambar dulu di dashboard.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>