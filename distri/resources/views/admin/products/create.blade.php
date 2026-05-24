@extends('layouts.app')
@section('title', 'Admin — Tambah Produk')

@section('content')
    <div class="auth-container" style="max-w: 500px;">
        <h2 style="font-family: 'Fraunces', serif; margin-bottom: 24px;">Tambah Produk Master</h2>

        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Nama Produk</label>
                <input type="text" name="name" class="form-control" placeholder="Contoh: Beras Premium 50kg" required>
            </div>
            <div class="form-group">
                <label>Satuan Jual</label>
                <input type="text" name="unit" class="form-control" placeholder="Contoh: per karung / koli" required>
            </div>
            <div class="form-group">
                <label>Minimal Order</label>
                <input type="number" name="min_qty" class="form-control" value="1" min="1" required>
            </div>
            <div class="form-group">
                <label>Harga Satuan (Rp)</label>
                <input type="number" name="price" class="form-control" placeholder="Contoh: 650000" required>
            </div>
            <div class="form-group">
                <label>Foto Produk (Rill Gambar)</label>
                <input type="file" name="image" class="form-control" accept="image/*" required>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="form-control"
                    style="background: var(--accent); color: #fff; font-weight:700; cursor:pointer; flex:1;">Simpan Ke
                    Oracle</button>
                <a href="{{ route('admin.products.index') }}" class="form-control"
                    style="background: #fff; text-align: center; text-decoration: none; color: var(--muted); font-weight:600; flex:1;">Batal</a>
            </div>
        </form>
    </div>
@endsection
