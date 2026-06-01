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
                <label>Kategori</label>
                <select name="category_id" class="form-control">
                    <option value="">Tanpa kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Brand</label>
                <input type="text" name="brand" class="form-control" placeholder="Contoh: Distri Mart">
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi produk"></textarea>
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
            <div style="display:flex; gap:12px;">
                <div class="form-group" style="flex:1;">
                    <label>Stok</label>
                    <input type="number" name="stock" class="form-control" value="50" min="0">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Diskon (%)</label>
                    <input type="number" name="discount_percentage" class="form-control" value="0" min="0" max="100">
                </div>
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
