<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title') — Distri</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:ital,opsz,wght@0,9..144,700;1,9..144,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0D2545;
            --navy2: #1A3A6E;
            --navy3: #1E4A8A;
            --accent: #2E7CF6;
            --accent2: #4B9BFF;
            --white: #FFFFFF;
            --offwhite: #F0F4FA;
            --card: #F6F9FF;
            --border: #D8E4F4;
            --muted: #637899;
            --green: #15803D;
            --green-bg: #F0FDF4;
            --green-border: #BBF7D0;
            --red: #991B1B;
            --red-bg: #FEF2F2;
            --red-border: #FECACA;
            --yellow: #92400E;
            --yellow-bg: #FFFBEB;
            --yellow-border: #FDE68A;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--offwhite);
            color: var(--navy);
            min-height: 100vh;
        }

        .navbar {
            background: var(--navy);
            height: 60px;
            display: flex;
            align-items: center;
            padding: 0 48px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-family: 'Fraunces', serif;
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            margin-right: 36px;
            letter-spacing: -0.5px;
        }

        .logo span {
            color: var(--accent2);
        }

        .nav-links {
            display: flex;
            gap: 4px;
            flex: 1;
        }

        .nb {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: .15s;
        }

        .nb:hover,
        .nb.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.14);
        }

        .nav-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .nav-badge {
            background: rgba(75, 155, 255, 0.2);
            border: 1px solid rgba(75, 155, 255, 0.35);
            color: #7BB8FF;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 20px;
        }

        .logout-btn {
            background: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s;
        }

        .logout-btn:hover {
            background: #991B1B;
            color: #fff;
            border-color: #991B1B;
        }

        .auth-container {
            max-width: 400px;
            margin: 80px auto;
            background: #fff;
            padding: 32px;
            border-radius: 24px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 30px rgba(13, 37, 69, 0.05);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--navy);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
            color: var(--navy);
        }

        .form-control:focus {
            border-color: var(--accent);
            outline: none;
        }
    </style>
    @yield('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <nav class="navbar">
        @auth
            @if (auth()->user()->role === 'admin')
                <a href="{{ route('admin.products.index') }}" class="logo">distri<span>.</span>admin</a>
                <div class="nav-links">
                    {{-- Diubah polanya agar tidak tabrakan dengan halaman verifikasi nota --}}
                    <a href="{{ route('admin.products.index') }}"
                        class="nb {{ (request()->routeIs('admin.products.index') || request()->routeIs('admin.products.create') || request()->routeIs('admin.products.edit')) ? 'active' : '' }}">Kelola Produk (CRUD)</a>
                    
                    <a href="{{ route('admin.products.veridity') }}" 
                        class="nb {{ request()->routeIs('admin.products.veridity') ? 'active' : '' }}">Validasi Nota (Veridity)</a>
                </div>
            @else
                <a href="{{ route('distri.landing') }}" class="logo">distri<span>.</span></a>
                <div class="nav-links">
                    <a href="{{ route('distri.landing') }}"
                        class="nb {{ request()->routeIs('distri.landing') ? 'active' : '' }}">Beranda</a>
                    <a href="{{ route('distri.catalog') }}"
                        class="nb {{ request()->routeIs('distri.catalog') ? 'active' : '' }}">Katalog Produk</a>
                    <a href="{{ route('distri.orders') }}"
                        class="nb {{ request()->routeIs('distri.orders') ? 'active' : '' }}">Pesanan Saya</a>
                </div>
            @endif
        @else
            <a href="#" class="logo">distri<span>.</span></a>
        @endauth

        <div class="nav-right">
            <div class="nav-badge">✦ Veridity AI Protection</div>
            @auth
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            @endauth
        </div>
    </nav>

    <div class="main-content">
        @yield('content')
    </div>

</body>

</html>