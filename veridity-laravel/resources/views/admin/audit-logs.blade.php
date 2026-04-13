<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - VeriDity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-blue: #0d6efd;
            --bg-light: #f8faff;
            --sidebar-white: #ffffff;
            --text-dark: #212529;
            --border-color: #eef2f7;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
        }

        /* Sidebar Styling */
        .sidebar {
            height: 100vh;
            width: 260px;
            position: fixed;
            background: var(--sidebar-white);
            border-right: 1px solid var(--border-color);
            padding-top: 25px;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: #6c757d;
            padding: 14px 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: var(--primary-blue);
            background: rgba(13, 110, 253, 0.05);
        }

        .sidebar .nav-link.active {
            border-right: 4px solid var(--primary-blue);
        }

        /* Main Content Area */
        .main-content {
            margin-left: 260px;
            padding: 40px;
        }

        /* Tables & Cards */
        .card-table {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
        }

        .table thead {
            background: #fcfdfe;
        }

        .table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #8c98a4;
            padding: 18px 20px;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 18px 20px;
            font-size: 0.9rem;
            color: #495057;
            border-bottom: 1px solid #f8faff;
        }

        /* Badge Custom */
        .badge-soft-success { background-color: rgba(25, 135, 84, 0.1); color: #198754; border: 1px solid rgba(25, 135, 84, 0.2); }
        .badge-soft-warning { background-color: rgba(255, 193, 7, 0.1); color: #997404; border: 1px solid rgba(255, 193, 7, 0.2); }
        .badge-soft-danger { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.2); }
        .badge-soft-primary { background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; border: 1px solid rgba(13, 110, 253, 0.2); }
        
        .avatar-sm {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="px-4 mb-5">
            <span class="fw-bold text-primary fs-3">Veri<span style="color: var(--text-dark);">Dity</span></span>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="bi bi-grid-fill"></i> Dashboard</a>
            <a class="nav-link active" href="{{ route('admin.audit-logs') }}"><i class="bi bi-journal-text"></i> Audit Logs</a>
            <a class="nav-link" href="#"><i class="bi bi-shield-slash"></i> Blacklist Data</a>
            <div class="mt-auto" style="padding-top: 150px;">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100 text-start">
                        <i class="bi bi-box-arrow-left"></i> Logout
                    </button>
                </form>
            </div>
        </nav>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h3 class="fw-bold mb-1">Forensic <span class="text-primary">Audit Logs</span></h3>
                <p class="text-muted small mb-0">Riwayat lengkap deteksi dan analisis sistem VeriDity.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-white border bg-white shadow-sm px-3 rounded-3">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
            </div>
        </div>

        <div class="card-table">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Image Details</th>
                            <th class="text-center">ELA Score</th>
                            <th class="text-center">Result</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        @php
                            $label = $log->final_result['summary_label'] ?? 'Aman';
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $log->created_at->format('d M Y') }}</div>
                                <div class="text-muted extra-small" style="font-size: 0.75rem;">{{ $log->created_at->format('H:i') }} WIB</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-sm bg-soft-primary rounded-circle text-primary">
                                        {{ strtoupper(substr($log->user->name ?? 'G', 0, 1)) }}
                                    </div>
                                    <span class="fw-semibold">{{ $log->user->name ?? 'Guest' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="text-dark">{{ Str::limit($log->image_name, 25) }}</div>
                                <div class="text-muted extra-small" style="font-size: 0.75rem;">ID: #VRD-{{ $log->id }}</div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold {{ $log->ela_score > 20 ? 'text-danger' : 'text-success' }}">
                                    {{ $log->ela_score }}%
                                </span>
                            </td>
                            <td class="text-center">
                                @if($label == 'Sangat Berbahaya')
                                    <span class="badge badge-soft-danger rounded-pill px-3">BAHAYA</span>
                                @elseif($label == 'Mencurigakan / Warning')
                                    <span class="badge badge-soft-warning rounded-pill px-3">WARNING</span>
                                @else
                                    <span class="badge badge-soft-success rounded-pill px-3">AMAN</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.audit.show', $log->id) }}" class="btn btn-sm btn-light border">
                                    <i class="bi bi-eye-fill text-primary"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <p class="text-muted italic mb-0">Belum ada aktivitas audit log.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($logs->hasPages())
            <div class="px-4 py-3 bg-light border-top">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>