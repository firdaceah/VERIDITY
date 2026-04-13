<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VeriDity - Digital Authenticity Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #0d6efd;
            --light-bg: #ffffff;
            --soft-blue: #f8faff;
            --text-dark: #212529;
            --text-muted: #6c757d;
        }
        body {
            font-family: 'Inter', sans-serif;
            scroll-behavior: smooth;
            background-color: var(--light-bg);
            color: var(--text-dark);
        }
        .navbar { 
            background: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid #eee;
            backdrop-filter: blur(10px);
        }
        .hero-section { 
            padding: 120px 0 80px 0; 
            background: radial-gradient(circle at top right, #e7f0ff, #ffffff); 
        }
        
        .feature-card {
            background: #ffffff;
            border: 1px solid #eef2f7;
            border-radius: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(13, 110, 253, 0.1);
            border-color: var(--primary-blue);
        }
        
        .icon-box {
            width: 60px;
            height: 60px;
            background-color: rgba(13, 110, 253, 0.08);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto;
        }
        .icon-box i {
            font-size: 1.5rem;
            color: var(--primary-blue);
        }

        .btn-primary { 
            padding: 12px 30px; 
            border-radius: 10px; 
            font-weight: 600;
        }
        section { padding: 80px 0; }
        .bg-soft { background-color: var(--soft-blue); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary fs-3" href="{{ url('/') }}">Veri<span style="color: #212529;">Dity</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link mx-2 fw-medium text-dark" href="#tentang">Tentang</a></li>
                    <li class="nav-item"><a class="nav-link mx-2 fw-medium text-dark" href="#solusi">Solusi</a></li>
                    <li class="nav-item"><a class="nav-link mx-2 fw-medium text-dark" href="#metode">Metode</a></li>
                    
                    @guest
                        <li class="nav-item"><a class="btn btn-link text-decoration-none text-dark mx-2 fw-bold" href="{{ route('login') }}">Login</a></li>
                        <li class="nav-item"><a class="btn btn-primary mx-2 shadow-sm" href="{{ route('register') }}">Daftar Gratis</a></li>
                    @else
                        <li class="nav-item">
                            <a class="btn btn-primary mx-2 shadow-sm" href="{{ Auth::user()->role == 'admin' ? route('admin.dashboard') : route('user.dashboard') }}">
                                <i class="bi bi-speedometer2 me-1"></i> Ke Dashboard
                            </a>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-3 fw-bold">AI-Powered Security</span>
                    <h1 class="display-4 fw-bold mb-4">Pastikan Foto yang Anda Lihat Adalah <span class="text-primary">Nyata.</span></h1>
                    <p class="lead text-muted mb-5">VeriDity adalah platform deteksi autentikasi citra digital berbasis AI. Kami membantu Anda membedakan manusia asli dengan hasil Deepfake dalam hitungan detik.</p>
                    <div class="d-flex gap-3">
                        @guest
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg px-4 shadow">Coba Verifikasi Sekarang</a>
                        @else
                            <a href="{{ route('user.dashboard') }}" class="btn btn-primary btn-lg px-4 shadow">Mulai Audit Sekarang</a>
                        @endguest
                        <a href="#metode" class="btn btn-outline-primary btn-lg px-4">Pelajari Fitur</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center mt-5 mt-lg-0">
                    <img src="https://img.freepik.com/free-vector/biometric-authentication-concept_23-2148533408.jpg" class="img-fluid rounded-5 shadow-lg" alt="Security" style="max-height: 450px;">
                </div>
            </div>
        </div>
    </header>

    <section id="solusi" class="bg-soft">
        <div class="container">
            <div class="text-center mb-5 mx-auto" style="max-width: 700px;">
                <h2 class="fw-bold mb-3">Satu Fitur, Berbagai Solusi</h2>
                <p class="text-muted fs-5">Platform ini mampu memproses berbagai kategori gambar melalui 4 metode analisis simultan secara cerdas.</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 text-center border-0">
                        <div class="card-body">
                            <div class="icon-box"><i class="bi bi-person-bounding-box"></i></div>
                            <h5 class="fw-bold mb-3">Foto Manusia</h5>
                            <p class="text-muted small">Verifikasi keaslian foto profil, identitas wajah, dan deteksi rekayasa pada atribut fisik.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 text-center border-0">
                        <div class="card-body">
                            <div class="icon-box"><i class="bi bi-file-earmark-text"></i></div>
                            <h5 class="fw-bold mb-3">Dokumen & Teks</h5>
                            <p class="text-muted small">Analisis integritas dokumen scan, bukti transfer, atau ID card untuk mendeteksi editing teks.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card h-100 p-4 text-center border-0">
                        <div class="card-body">
                            <div class="icon-box"><i class="bi bi-search"></i></div>
                            <h5 class="fw-bold mb-3">Objek Umum</h5>
                            <p class="text-muted small">Deteksi manipulasi pada foto pemandangan, barang bukti, atau objek lingkungan lainnya.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    

    <footer class="py-5 border-top bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <h4 class="fw-bold text-primary mb-0">VeriDity</h4>
                    <p class="text-muted small">Platform Keamanan Digital untuk Masa Depan.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="text-muted mb-0 small">&copy; 2026 <strong>VeriDity Project</strong>. <br> Workshop Pemrograman Framework - Firda & Team.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>