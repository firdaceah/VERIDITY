<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VeriDity</title>
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
            border-radius: 0;
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

        /* Stat Cards */
        .stat-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 8px 0;
        }

        .stat-label {
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 1.2px;
            color: #6c757d;
            font-weight: 700;
        }

        .stat-trend {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .fraud-indicator {
            border-left: 4px solid #dc3545;
        }

        /* Tables */
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
            border-bottom: 1px solid var(--border-color);
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 18px 20px;
            font-size: 0.9rem;
            color: #495057;
            border-bottom: 1px solid #f8faff;
        }

        /* Badge Custom */
        .badge-soft-success {
            background-color: rgba(25, 135, 84, 0.1);
            color: #198754;
            border: 1px solid rgba(25, 135, 84, 0.2);
        }

        .badge-soft-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #997404;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }

        .badge-soft-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .badge-soft-primary {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            border: 1px solid rgba(13, 110, 253, 0.2);
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="px-4 mb-5">
            <span class="fw-bold text-primary fs-3">Veri<span style="color: var(--text-dark);">Dity</span></span>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="{{ route('admin.dashboard') }}"><i class="bi bi-grid-fill"></i>
                Dashboard</a>
            <a class="nav-link" href="{{ route('admin.audit-logs') }}"><i class="bi bi-journal-text"></i> Audit Logs</a>
            {{-- <a class="nav-link" href="#"><i class="bi bi-shield-slash"></i> Blacklist Data</a> --}}
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
                <h3 class="fw-bold mb-1">Welcome back, {{ Auth::user()->name }}!</h3>
                <p class="text-muted small mb-0">Monitoring Forensic Activity & Integration Logs.</p>
            </div>

        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="stat-card">
                    <span class="stat-label">Total Audit</span>
                    <div class="stat-value text-primary">{{ $totalAudit }}</div>
                     <span class="text-muted small">Data</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card fraud-indicator">
                    <span class="stat-label">Fraud Detected</span>
                    <div class="stat-value text-danger">{{ $fraudCount }}</div>
                    <span class="text-muted small">Potensial Manipulasi</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <span class="stat-label">Connected Clients</span>
                    <div class="stat-value">{{ $totalUser }}</div>
                    <span class="text-muted small">Total User Terdaftar</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <span class="stat-label">System Plugins</span>
                    <div class="stat-value">1</div>
                    <span class="text-muted small">Active Installations</span>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-activity text-primary me-2 fs-4"></i>
                <h5 class="fw-bold mb-0">Recent Forensic Audit</h5>
            </div>
            <div class="card-table">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Client ID</th>
                            <th>Object Name</th>
                            <th>Confidence Score</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAudits as $audit)
                            @php
                                $label = $audit->final_result['summary_label'] ?? 'Aman';
                            @endphp
                            <tr>
                                <td class="fw-semibold text-primary italic">{{ $audit->user->name ?? 'Guest' }}</td>
                                <td class="text-muted small">{{ Str::limit($audit->image_name, 20) }}</td>
                                <td>
                                    <span class="fw-bold {{ $audit->ela_score > 20 ? 'text-danger' : 'text-success' }}">
                                        ELA: {{ $audit->ela_score }}%
                                    </span>
                                </td>
                                <td>
                                    @if ($label == 'Sangat Berbahaya')
                                        <span class="badge badge-soft-danger rounded-pill px-3">BAHAYA</span>
                                    @elseif($label == 'Mencurigakan / Warning')
                                        <span class="badge badge-soft-warning rounded-pill px-3">WARNING</span>
                                    @else
                                        <span class="badge badge-soft-success rounded-pill px-3">AMAN</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.audit.show', $audit->id) }}"
                                        class="btn btn-sm btn-light border">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="50"
                                        class="mb-3 opacity-25" alt="Empty">
                                    <p class="text-muted italic mb-0">Belum ada aktivitas analisis masuk saat ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
