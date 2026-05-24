@extends('layouts.app')
@section('title', 'Form Checkout Grosir')

@section('styles')
    <style>
        .co-layout {
            display: flex;
            min-height: calc(100vh - 60px);
        }

        .co-main {
            flex: 1;
            padding: 40px 52px;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .co-panel {
            width: 380px;
            background: var(--white);
            border-left: 1px solid var(--border);
            padding: 40px 32px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .back-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--navy);
            margin-bottom: 4px;
        }

        .co-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px 28px;
        }

        .co-card-title {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 18px;
        }

        .rek-box {
            background: var(--navy);
            border-radius: 14px;
            padding: 20px 24px;
            color: #fff;
        }

        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: 14px;
            padding: 48px 24px;
            text-align: center;
            cursor: pointer;
            background: var(--card);
            transition: .2s;
        }

        .upload-zone:hover {
            border-color: var(--accent);
            background: #EEF4FF;
        }

        .veridity-bar {
            background: #EEF4FF;
            border: 1px solid #B8D0EE;
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .v-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--accent);
            margin-top: 4px;
            flex-shrink: 0;
        }

        .btn-full {
            width: 100%;
            background: var(--accent);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            padding: 15px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: .15s;
            text-align: center;
        }

        .order-summary {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
        }
    </style>
@endsection

@section('content')
    <form action="{{ route('distri.order.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="total_amount" id="form-total" value="{{ $product->price * $product->min_qty }}">

        <div class="co-layout">
            <div class="co-main">
                <a href="{{ route('distri.catalog') }}" class="back-bar">
                    <div style="width: 34px; height: 34px; border-radius: 10px; background: #fff; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center;">
                        ←</div>
                    <div style="font-size: 18px; font-weight: 800;">Kembali ke Katalog</div>
                </a>

                <div class="co-card">
                    <div class="co-card-title">Instruksi Transfer Bank</div>
                    <div class="rek-box">
                        <div style="font-size: 10px; opacity: 0.5; margin-bottom: 8px;">NOMOR REKENING TUJUAN</div>
                        <div style="font-size: 22px; font-weight: 800; letter-spacing: 3px; margin-bottom: 6px;">1234 5678 9012</div>
                        <div style="font-size: 12px; opacity: 0.7;">PT Distri Nusantara Jaya</div>
                    </div>
                </div>

                <div class="co-card">
                    <div class="co-card-title">Upload Bukti Transfer / Nota Pembayaran</div>
                    <div class="upload-zone" onclick="document.getElementById('proof-file').click()">
                        <div style="font-size: 40px; margin-bottom: 12px;">📎</div>
                        <div id="upload-label" style="font-size: 15px; font-weight: 700;">Klik di sini untuk mengunggah foto nota atau struk transfer</div>
                        <div style="font-size: 13px; color: var(--muted); margin-top: 4px;">Format: JPG, JPEG, PNG · Ukuran maksimal 10 MB</div>
                        <input type="file" name="proof_of_transfer" id="proof-file" accept="image/*" style="display: none;" onchange="fileChosen(this)" required>
                    </div>
                    <div class="veridity-bar" style="margin-top:16px;">
                        <div class="v-dot"></div>
                        <div style="font-size: 13px; line-height: 1.6;"><strong>Nota Anda akan diperiksa otomatis oleh Veridity Engine.</strong> Sistem AI memverifikasi keaslian piksel struk transfer guna menghindari manipulasi digital.</div>
                    </div>
                </div>

                <button type="submit" class="btn-full">Kirim Pesanan & Mulai Verifikasi Veridity →</button>
            </div>

            <div class="co-panel">
                <div style="font-size: 17px; font-weight: 800;">Ringkasan Pesanan</div>
                <div class="order-summary">
                    <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 20px;">
                        <div style="width: 50px; height: 50px; border-radius: 10px; overflow: hidden; background: #fff;">
                            @if ($product->image)
                                {{-- Diperbaiki ke folder public terluar mendampingi catalog & admin index --}}
                                <img src="{{ asset('products/' . $product->image) }}" style="width:100%; height:100%; object-fit:cover;">
                            @else
                                📦
                            @endif
                        </div>
                        <div>
                            <div style="font-size: 15px; font-weight: 700;">{{ $product->name }}</div>
                            <div style="font-size: 12px; color: var(--muted);">Minimal Order: {{ $product->min_qty }} {{ $product->unit }}</div>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 12px;">
                        <span style="color: var(--muted);">Harga Satuan</span>
                        <span style="font-weight: 600;">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; margin-bottom: 12px;">
                        <span style="color: var(--muted);">Jumlah Kelipatan</span>
                        <input type="number" name="quantity" id="order-qty" value="{{ $product->min_qty }}" min="{{ $product->min_qty }}" onchange="recalcTotal(this.value, {{ $product->price }})" style="width: 70px; padding: 6px; border: 1.5px solid var(--border); border-radius: 8px; font-weight: 700; text-align: center;">
                    </div>
                    <hr style="border: none; border-top: 1.5px solid var(--border); margin: 14px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 14px; font-weight: 700;">Total Bayar</span>
                        <span style="font-size: 22px; font-weight: 800; color: var(--navy planetary);" id="total-display">Rp {{ number_format($product->price * $product->min_qty, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        function fileChosen(input) {
            if (input.files.length > 0) {
                document.getElementById('upload-label').innerHTML = "✅ Berkas Siap: <span style='color:var(--green);'>" + input.files[0].name + "</span>";
            }
        }

        function recalcTotal(qty, price) {
            let finalAmt = qty * price;
            document.getElementById('form-total').value = finalAmt;
            document.getElementById('total-display').textContent = "Rp " + finalAmt.toLocaleString('id-ID');
        }
    </script>
@endsection