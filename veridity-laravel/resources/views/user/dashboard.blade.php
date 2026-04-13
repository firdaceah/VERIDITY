<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Audit - VeriDity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #0d6efd;
            --bg-light: #f8faff;
            --sidebar-white: #ffffff;
            --border-color: #eef2f7;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
        }

        .sidebar {
            height: 100vh;
            width: 260px;
            position: fixed;
            background: var(--sidebar-white);
            border-right: 1px solid var(--border-color);
            padding-top: 25px;
        }

        .sidebar .nav-link {
            color: #6c757d;
            padding: 14px 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .sidebar .nav-link.active {
            color: var(--primary-blue);
            background: rgba(13, 110, 253, 0.05);
            border-right: 4px solid var(--primary-blue);
        }

        .main-content {
            margin-left: 260px;
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .upload-box {
            background: white;
            border: 2px dashed #d1d9e6;
            border-radius: 30px;
            padding: 60px 40px;
            text-align: center;
            transition: all 0.3s;
            max-width: 600px;
            width: 100%;
        }

        .upload-box:hover {
            border-color: var(--primary-blue);
            background: #f0f7ff;
        }

        .upload-icon {
            font-size: 4rem;
            color: var(--primary-blue);
            opacity: 0.5;
            margin-bottom: 20px;
        }

        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .spinner-audit {
            width: 3rem;
            height: 3rem;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="px-4 mb-5"><span class="fw-bold text-primary fs-3">Veri<span class="text-dark">Dity</span></span>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link active" href="{{ route('user.dashboard') }}"><i class="bi bi-cloud-arrow-up-fill"></i>
                Dashboard</a>
            <a class="nav-link" href="{{ route('user.history') }}"><i class="bi bi-clock-history"></i> Riwayat Saya</a>
            <div class="mt-auto" style="padding-top: 200px;">
                <form action="{{ route('logout') }}" method="POST">@csrf
                    <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100 text-start"><i
                            class="bi bi-box-arrow-left"></i> Keluar</button>
                </form>
            </div>
        </nav>
    </div>

    <div class="main-content">
        <div class="upload-box shadow-sm">
            <i class="bi bi-images upload-icon"></i>
            <h3 class="fw-bold mb-2">Mulai Analisis Baru</h3>
            <p class="text-muted mb-4">Unggah gambar JPG/PNG untuk mendeteksi manipulasi digital.</p>

            <form action="{{ route('audit.analyze') }}" method="POST" enctype="multipart/form-data"
                id="mainUploadForm">
                @csrf
                <div class="mb-4 text-start">
                    <input type="file" name="image" class="form-control form-control-lg rounded-4" accept="image/*"
                        required>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-4 fw-bold shadow-sm">
                    <i class="bi bi-cpu me-2"></i> Jalankan Forensik
                </button>
            </form>
        </div>
    </div>

    <div id="loadingOverlay">
        <div class="spinner-audit mb-3"></div>
        <h5 class="fw-bold text-primary italic">System Processing...</h5>
        <p class="text-muted small">Menjalankan ELA, Metadata Scan, dan Noise Detection.</p>
    </div>

    <script>
        document.getElementById('mainUploadForm').onsubmit = function() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        };
    </script>
</body>

</html>
