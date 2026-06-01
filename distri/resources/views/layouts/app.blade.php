<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title') - Distri</title>
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

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--offwhite); color: var(--navy); min-height: 100vh; }
        .navbar { background: var(--navy); height: 60px; display: flex; align-items: center; padding: 0 48px; position: sticky; top: 0; z-index: 100; }
        .logo { font-family: 'Fraunces', serif; font-size: 24px; font-weight: 700; color: #fff; text-decoration: none; margin-right: 36px; letter-spacing: -0.5px; }
        .logo span { color: var(--accent2); }
        .nav-links { display: flex; gap: 4px; flex: 1; }
        .nb { background: none; border: none; color: rgba(255,255,255,.68); font-size: 13px; font-weight: 700; padding: 8px 16px; border-radius: 8px; cursor: pointer; text-decoration: none; transition: .15s; }
        .nb:hover, .nb.active { color: #fff; background: rgba(255,255,255,.14); }
        .nb-icon { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; padding: 0; }
        .nav-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }
        .nav-toggle { display:none; width:38px; height:38px; border:0; border-radius:10px; background:rgba(255,255,255,.12); color:#fff; align-items:center; justify-content:center; cursor:pointer; }
        .profile-menu { position: relative; }
        .profile-trigger { border:0; }
        .profile-dropdown { display:none; position:absolute; top:44px; right:0; min-width:190px; background:#fff; border:1px solid var(--border); border-radius:14px; box-shadow:0 12px 28px rgba(13,37,69,.14); padding:8px; z-index:200; }
        .profile-menu:hover .profile-dropdown, .profile-menu:focus-within .profile-dropdown { display:block; }
        .dropdown-link, .dropdown-button { width:100%; border:0; background:transparent; color:var(--navy); text-align:left; text-decoration:none; display:flex; gap:8px; align-items:center; padding:10px 12px; border-radius:10px; font-family:inherit; font-size:13px; font-weight:800; cursor:pointer; }
        .dropdown-link:hover, .dropdown-button:hover { background:var(--card); }
        .dropdown-button.logout { color:var(--red); }
        .logout-btn { background: none; border: 1px solid var(--red-border); color: var(--red); font-size: 12px; font-weight: 800; padding: 9px 12px; border-radius: 10px; cursor: pointer; }
        .auth-container { max-width: 400px; margin: 80px auto; background: #fff; padding: 32px; border-radius: 24px; border: 1px solid var(--border); box-shadow: 0 10px 30px rgba(13,37,69,.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 12px; font-weight: 800; margin-bottom: 8px; color: var(--navy); }
        .form-control { width: 100%; padding: 12px 16px; border: 1.5px solid var(--border); border-radius: 12px; font-family: inherit; font-size: 14px; color: var(--navy); background: #fff; }
        .form-control:focus { border-color: var(--accent); outline: none; }
        input[type="file"] { width: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: 12px; background: #fff; color: var(--muted); font-family: inherit; }
        input[type="file"]::file-selector-button { border: 0; border-radius: 10px; background: var(--navy); color: #fff; padding: 10px 14px; margin-right: 12px; font-family: inherit; font-weight: 800; cursor: pointer; }
        .password-wrap { position: relative; }
        .password-wrap .form-control { padding-right: 48px; }
        .toggle-password { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: 0; background: transparent; color: var(--muted); width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        @media (max-width: 900px) {
            .navbar { height: auto; min-height: 60px; padding: 12px 18px; align-items:center; gap:10px; flex-wrap:wrap; }
            .logo { margin-right: 0; }
            .nav-toggle { display:inline-flex; }
            .nav-links { display:none; order:5; width:100%; flex-direction:column; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); border-radius:14px; padding:8px; }
            .nav-links.open { display:flex; }
            .nb { white-space:nowrap; padding:8px 12px; }
            .nav-right { margin-left:auto; }
            .auth-container { margin:72px auto; max-width:440px; }
            .profile-dropdown { right:0; }
            .shop-hero, .hero, .product-shell, .co-wrap, .cart-wrap, .profile-wrap, .order-card, .v-card { grid-template-columns:1fr !important; }
            .v-card { display:block !important; }
            .proof-thumb { width:100% !important; height:220px !important; margin-bottom:14px; }
        }
        @media (max-width: 640px) {
            .navbar { padding:10px 14px; }
            .logo { font-size:21px; }
            .auth-container { margin:28px 16px; max-width:none; padding:24px; }
            .filter-card, .searchbar { display:grid !important; grid-template-columns:1fr !important; }
            .prod-grid, .feature-row, .stats { grid-template-columns:1fr !important; }
            .o-card, .item { grid-template-columns:1fr !important; align-items:stretch !important; }
            table { display:block; width:100%; overflow-x:auto; white-space:nowrap; }
            form { max-width:100%; }
            .admin-header { flex-direction:column; align-items:stretch !important; gap:12px; }
            .card, .pcard, .admin-container, .orders-layout, .home-wrap, .shop-wrap, .detail-wrap { padding-left:16px !important; padding-right:16px !important; }
            h1 { font-size:34px !important; }
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
                <button class="nav-toggle" type="button" aria-label="Buka menu" onclick="toggleDistriNav()">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
                </button>
                <div class="nav-links" id="distri-nav-links">
                    <a href="{{ route('admin.products.index') }}" class="nb {{ (request()->routeIs('admin.products.index') || request()->routeIs('admin.products.create') || request()->routeIs('admin.products.edit')) ? 'active' : '' }}">Kelola Produk</a>
                    <a href="{{ route('admin.stores.index') }}" class="nb {{ request()->routeIs('admin.stores.index') ? 'active' : '' }}">Pantau Toko</a>
                    <a href="{{ route('admin.orders.index') }}" class="nb {{ request()->routeIs('admin.orders.index') ? 'active' : '' }}">Pantau Pesanan</a>
                    <a href="{{ route('admin.products.veridity') }}" class="nb {{ request()->routeIs('admin.products.veridity') ? 'active' : '' }}">Validasi Nota</a>
                </div>
            @else
                <a href="{{ route('distri.landing') }}" class="logo">distri<span>.</span></a>
                <button class="nav-toggle" type="button" aria-label="Buka menu" onclick="toggleDistriNav()">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
                </button>
                <div class="nav-links" id="distri-nav-links">
                    <a href="{{ route('distri.landing') }}" class="nb {{ request()->routeIs('distri.landing') ? 'active' : '' }}">Beranda</a>
                    <a href="{{ route('distri.catalog') }}" class="nb {{ request()->routeIs('distri.catalog') ? 'active' : '' }}">Katalog Produk</a>
                    <a href="{{ route('distri.orders') }}" class="nb {{ request()->routeIs('distri.orders') ? 'active' : '' }}">Pesanan Saya</a>
                    <a href="{{ route('distri.vouchers') }}" class="nb {{ request()->routeIs('distri.vouchers') ? 'active' : '' }}">Voucher</a>
                </div>
            @endif
        @else
            <a href="#" class="logo">distri<span>.</span></a>
        @endauth

        <div class="nav-right">
            @auth
                @if (auth()->user()->role === 'admin')
                    <div class="profile-menu">
                        <button type="button" class="nb nb-icon profile-trigger {{ request()->routeIs('distri.profile') ? 'active' : '' }}" title="Profile Admin">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                        </button>
                        <div class="profile-dropdown">
                            <a class="dropdown-link" href="{{ route('distri.profile') }}">Profile</a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-button logout" type="submit">Logout</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('distri.cart') }}" class="nb nb-icon {{ request()->routeIs('distri.cart') ? 'active' : '' }}" title="Keranjang">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.96a2 2 0 0 0 1.95-1.57l1.35-6.43H5.12"/></svg>
                    </a>
                    <div class="profile-menu">
                        <button type="button" class="nb nb-icon profile-trigger {{ request()->routeIs('distri.profile') ? 'active' : '' }}" title="Profile">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                        </button>
                        <div class="profile-dropdown">
                            <a class="dropdown-link" href="{{ route('distri.profile') }}">Profile</a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-button logout" type="submit">Logout</button>
                            </form>
                        </div>
                    </div>
                @endif
            @endauth
        </div>
    </nav>

    <div class="main-content">
        @yield('content')
    </div>
    <script>
        function toggleDistriNav() {
            const nav = document.getElementById('distri-nav-links');
            if (nav) nav.classList.toggle('open');
        }
    </script>
</body>

</html>
