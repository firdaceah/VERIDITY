@extends('layouts.app')
@section('title', 'Admin — Kelola Produk')

@section('styles')
    <style>
        .admin-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 24px;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .add-btn {
            background: var(--accent);
            color: #fff;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
        }

        .prod-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        .prod-table th,
        .prod-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .prod-table th {
            background: var(--navy);
            color: #fff;
            font-size: 13px;
            text-transform: uppercase;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .edit-btn {
            background: #EEF4FF;
            color: var(--accent);
            border: 1px solid #B8D0EE;
        }

        .delete-btn {
            background: #FEF2F2;
            color: var(--red);
            border: 1px solid #FCA5A5;
            margin-left: 6px;
        }
        .search-form { background:#fff; border:1px solid var(--border); border-radius:16px; padding:16px; margin-bottom:18px; display:flex; gap:10px; }
    </style>
@endsection

@section('content')
    <div class="admin-container">
        <div class="admin-header">
            <div>
                <h2 style="font-family: 'Fraunces', serif; font-size: 28px;">Dashboard Manajemen Produk</h2>
                <p style="font-size: 13px; color: var(--muted);">POV Admin Database: Operasi CRUD Tabel PRODUCTS Skema
                    Oracle.</p>
            </div>
            <div style="display:flex; gap:10px;">
                <form method="POST" action="{{ route('admin.products.sync-dummy') }}">
                    @csrf
                    <button class="add-btn" style="border:0; cursor:pointer;" type="submit">Sync DummyJSON</button>
                </form>
                <a href="{{ route('admin.products.create') }}" class="add-btn">+ Tambah Produk Baru</a>
            </div>
        </div>

        @if (session('success'))
            <div
                style="background: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; font-size: 14px;">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" action="{{ route('admin.products.index') }}" class="search-form">
            <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Cari produk, brand, atau kategori...">
            <button class="add-btn" type="submit" style="border:0; cursor:pointer;">Cari</button>
            @if (request('q'))
                <a class="add-btn" href="{{ route('admin.products.index') }}" style="background:var(--card); color:var(--navy);">Reset</a>
            @endif
        </form>

        <table class="prod-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Harga</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $p)
                    <tr>
                        <td>
                            <div
                                style="width: 50px; height: 50px; border-radius: 8px; overflow: hidden; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                @if ($p->image)
                                    <img src="{{ asset('products/' . $p->image) }}"
                                        style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    📦
                                @endif
                            </div>
                        </td>
                        <td style="font-weight: 700;">{{ $p->name }}</td>
                        <td>{{ $p->category_name ?? '-' }}</td>
                        <td>{{ $p->stock ?? 0 }}</td>
                        <td style="font-weight: 700; color: var(--accent);">Rp {{ number_format($p->price, 0, ',', '.') }}
                        </td>
                        <td>
                            <a href="{{ route('admin.products.edit', $p->id) }}" class="action-btn edit-btn">Edit</a>
                            <form action="{{ route('admin.products.destroy', $p->id) }}" method="POST" class="delete-form"
                                style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="action-btn delete-btn btn-trigger-delete">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Script Interaktif Hapus Gaya Modern SweetAlert2 --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.btn-trigger-delete');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const form = this.closest('.delete-form');

                    Swal.fire({
                        title: 'Hapus Produk?',
                        text: "Data komoditas ini akan dihapus permanen dari Oracle!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#2E7CF6',
                        cancelButtonColor: '#637899',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal',
                        background: '#FFFFFF',
                        customClass: {
                            popup: 'animated fadeIn'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endsection
