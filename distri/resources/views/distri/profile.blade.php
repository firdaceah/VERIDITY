@extends('layouts.app')
@section('title', 'Profile')

@section('styles')
    <style>
        .profile-wrap { max-width:1080px; margin:40px auto; padding:0 24px; display:grid; grid-template-columns:1fr 1.1fr; gap:22px; }
        .card { background:#fff; border:1px solid var(--border); border-radius:20px; padding:26px; }
        .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-top:18px; }
        .stat { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:16px; }
        .btn { border:0; border-radius:12px; padding:12px 16px; background:var(--accent); color:#fff; font-weight:900; cursor:pointer; text-decoration:none; }
        .order-row { display:flex; justify-content:space-between; gap:12px; padding:12px 0; border-bottom:1px solid var(--border); font-size:13px; }
        .logout-inline { background:var(--red-bg); color:var(--red); border:1px solid var(--red-border); border-radius:12px; padding:12px 16px; font-weight:900; cursor:pointer; margin-left:10px; }
    </style>
@endsection

@section('content')
    <div class="profile-wrap">
        <div class="card">
            <div style="display:flex; gap:18px; align-items:center;">
                <div style="width:74px; height:74px; border-radius:24px; background:var(--navy); color:#fff; display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:900;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h2 style="font-family:'Fraunces',serif; font-size:30px;">{{ $user->name }}</h2>
                    <p style="font-size:13px; color:var(--muted); margin-top:4px;">{{ $user->email }} · {{ strtoupper($user->role) }}</p>
                </div>
            </div>

            <div class="stats">
                <div class="stat"><div style="font-size:24px; font-weight:900;">{{ $summary['orders'] }}</div><div style="font-size:11px; color:var(--muted);">Pesanan valid</div></div>
                <div class="stat"><div style="font-size:24px; font-weight:900;">{{ $summary['paid'] }}</div><div style="font-size:11px; color:var(--muted);">Pembayaran sah</div></div>
                <div class="stat"><div style="font-size:24px; font-weight:900;">{{ $summary['rejected'] }}</div><div style="font-size:11px; color:var(--muted);">Rejected</div></div>
                <div class="stat"><div style="font-size:24px; font-weight:900;">{{ $summary['cart'] }}</div><div style="font-size:11px; color:var(--muted);">Keranjang</div></div>
            </div>

            <form method="POST" action="{{ route('distri.profile.update') }}" style="margin-top:24px;">
                @csrf
                @method('PUT')
                @if (session('success'))
                    <div style="background:var(--green-bg); border:1px solid var(--green-border); color:var(--green); padding:12px; border-radius:12px; margin-bottom:14px; font-size:13px; font-weight:800;">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div style="background:var(--red-bg); border:1px solid var(--red-border); color:var(--red); padding:12px; border-radius:12px; margin-bottom:14px; font-size:13px; font-weight:800;">{{ $errors->first() }}</div>
                @endif
                <div class="form-group"><label>Nama Toko / Reseller</label><input class="form-control" type="text" name="name" value="{{ old('name', $user->name) }}" required></div>
                <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
                <div class="form-group"><label>Password Baru</label><input class="form-control" type="password" name="password" placeholder="Isi jika ingin mengganti password"></div>
                <div class="form-group"><label>Konfirmasi Password</label><input class="form-control" type="password" name="password_confirmation" placeholder="Ulangi password baru"></div>
                <button class="btn" type="submit">Simpan Profile</button>
            </form>

            <form action="{{ route('logout') }}" method="POST" style="margin-top:12px;">
                @csrf
                <button type="submit" class="logout-inline">Logout</button>
            </form>

            <div style="margin-top:28px; border-top:1px solid var(--border); padding-top:22px;">
                <h3 style="font-family:'Fraunces',serif; font-size:22px;">Daftar Alamat Pengiriman</h3>
                @foreach ($addresses as $address)
                    <div style="background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px; margin-top:10px;">
                        <strong>{{ $address->label }} - {{ $address->recipient_name }}</strong>
                        <p style="font-size:12px; color:var(--muted); line-height:1.6; margin-top:4px;">{{ $address->phone }} · {{ $address->address_line }}, {{ $address->city }} {{ $address->postal_code }}</p>
                    </div>
                @endforeach
                <form method="POST" action="{{ route('distri.addresses.store') }}" style="margin-top:16px;">
                    @csrf
                    <div class="form-group"><label>Label Alamat</label><input class="form-control" name="label" placeholder="Rumah / Toko / Gudang" required></div>
                    <div class="form-group"><label>Nama Penerima</label><input class="form-control" name="recipient_name" required></div>
                    <div class="form-group"><label>No. HP</label><input class="form-control" name="phone"></div>
                    <div class="form-group"><label>Alamat Lengkap</label><textarea class="form-control" name="address_line" rows="3" required></textarea></div>
                    <div style="display:grid; grid-template-columns:1fr 140px; gap:10px;">
                        <div class="form-group"><label>Kota</label><input class="form-control" name="city"></div>
                        <div class="form-group"><label>Kode Pos</label><input class="form-control" name="postal_code"></div>
                    </div>
                    <label style="font-size:13px; font-weight:800;"><input type="checkbox" name="is_default" value="1"> Jadikan alamat utama</label>
                    <div style="margin-top:14px;"><button class="btn" type="submit">Tambah Alamat</button></div>
                </form>
            </div>
        </div>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:end;">
                <div>
                    <h3 style="font-family:'Fraunces',serif; font-size:24px;">Pesanan Terbaru</h3>
                    <p style="font-size:12px; color:var(--muted); margin-top:4px;">Rejected tetap ditampilkan, tapi tidak dihitung sebagai pesanan valid.</p>
                </div>
                <a href="{{ route('distri.orders') }}" style="color:var(--accent); font-weight:900; text-decoration:none;">Semua</a>
            </div>
            <div style="margin-top:16px;">
                @forelse ($orders as $order)
                    <div class="order-row">
                        <div>
                            <strong>{{ $order->order_id_string }}</strong>
                            <div style="color:var(--muted); margin-top:3px;">{{ strtoupper($order->payment_status ?? 'pending') }} · {{ $order->created_at }}</div>
                        </div>
                        <a href="{{ route('distri.order.show', $order->id) }}" style="color:var(--accent); font-weight:900; text-decoration:none;">Detail</a>
                    </div>
                @empty
                    <div style="color:var(--muted); padding:22px 0;">Belum ada pesanan.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
