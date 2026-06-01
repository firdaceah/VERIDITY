@extends('layouts.app')
@section('title', 'Edit Produk Master')

@section('styles')
<style>
    .form-layout {
        max-width: 600px;
        margin: 40px auto;
        padding: 0 24px;
    }
    .form-card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 32px;
        box-shadow: 0 10px 30px rgba(13,37,69,0.03);
    }
    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: var(--muted);
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 20px;
        transition: 0.2s;
    }
    .back-btn:hover {
        color: var(--navy);
    }
    .btn-submit {
        background: var(--accent);
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        padding: 14px;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        width: 100%;
        transition: 0.2s;
        margin-top: 10px;
    }
    .btn-submit:hover {
        background: var(--navy2);
    }
    .img-preview-box {
        display: flex;
        gap: 16px;
        align-items: center;
        background: var(--card);
        border: 1px solid var(--border);
        padding: 12px;
        border-radius: 12px;
        margin-top: 8px;
    }
    .current-img {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid var(--border);
        background: #fff;
    }
</style>
@endsection

@section('content')
<div class="form-layout">
    <a href="{{ route('admin.products.index') }}" class="back-btn">
        ← Kembali ke Manajemen Produk
    </a>

    <div class="form-card">
        <div style="margin-bottom: 24px;">
            <h2 style="font-family: 'Fraunces', serif; font-size: 24px; margin-bottom: 4px;">Edit Produk Grosir</h2>
            <p style="font-size: 13px; color: var(--muted);">Perbarui data komoditas atau stok barang di skema Oracle.</p>
        </div>

        {{-- Tampilkan Error Validasi jika ada bumbu yang salah --}}
        @if ($errors->any())
            <div style="background: var(--red-bg); color: var(--red); border: 1px solid var(--red-border); padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 13px;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT') {{-- WAJIB pakai method PUT untuk update data di Laravel --}}

            <div class="form-group">
                <label>Nama Produk / Komoditas</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" placeholder="Contoh: Beras Lopo Ijo Premium" required>
            </div>

            <div class="form-group">
                <label>Kategori Produk</label>
                <select name="category_id" class="form-control">
                    <option value="">Tanpa kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Brand</label>
                <input type="text" name="brand" class="form-control" value="{{ old('brand', $product->brand) }}" placeholder="Contoh: Distri Mart">
            </div>

            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi produk">{{ old('description', $product->description) }}</textarea>
            </div>

            <div style="display: flex; gap: 16px;">
                <div class="form-group" style="flex: 1;">
                    <label>Satuan Unit Grosir</label>
                    <input type="text" name="unit" class="form-control" value="{{ old('unit', $product->unit) }}" placeholder="Contoh: Sak, Koli, Dus" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Minimal Pembelian</label>
                    <input type="number" name="min_qty" class="form-control" value="{{ old('min_qty', $product->min_qty) }}" min="1" required>
                </div>
            </div>

            <div class="form-group">
                <label>Harga Satuan Kontrak Grosir (Rp)</label>
                <input type="number" name="price" class="form-control" value="{{ old('price', $product->price) }}" placeholder="Contoh: 145000" min="0" required>
            </div>

            <div style="display: flex; gap: 16px;">
                <div class="form-group" style="flex: 1;">
                    <label>Stok</label>
                    <input type="number" name="stock" class="form-control" value="{{ old('stock', $product->stock ?? 0) }}" min="0">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Diskon (%)</label>
                    <input type="number" name="discount_percentage" class="form-control" value="{{ old('discount_percentage', $product->discount_percentage ?? 0) }}" min="0" max="100">
                </div>
            </div>

            <div class="form-group">
                <label>Foto Produk Komoditas</label>
                <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(this)">
                <small style="display: block; color: var(--muted); margin-top: 4px; font-size: 11px;">Biarkan kosong jika tidak ingin mengganti foto lama.</small>
                
                {{-- Preview Foto Lama vs Baru --}}
                <div class="img-preview-box">
                    @if($product->image)
                        <img src="{{ asset('products/' . $product->image) }}" id="img-display" class="current-img">
                    @else
                        <div id="img-display" style="width: 60px; height: 60px; border-radius: 8px; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 20px;">📦</div>
                    @endif
                    <div>
                        <div style="font-size: 12px; font-weight: 700;" id="img-status">Foto Saat Ini</div>
                        <div style="font-size: 11px; color: var(--muted);">Oracle File: {{ $product->image ?? 'Tidak ada foto' }}</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">Simpan Perubahan Produk →</button>
        </form>
    </div>
</div>

<script>
    // JS Interaktif untuk live preview foto baru pas dipilih oleh admin
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('img-display').src = e.target.result;
                // Jika sebelumnya gak ada foto (berupa box emoji), ubah tipenya jadi elemen img
                if(document.getElementById('img-display').tagName === 'DIV') {
                    location.reload(); // Quick refresh jika kondisi awal no-image
                }
                document.getElementById('img-status').innerHTML = "✨ <span style='color:var(--accent);'>Preview Foto Baru</span>";
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
