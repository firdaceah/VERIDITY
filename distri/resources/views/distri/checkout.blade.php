@extends('layouts.app')
@section('title', 'Form Checkout Grosir')

@section('styles')
    <style>
        .co-layout { display: flex; min-height: calc(100vh - 60px); }
        .co-main { flex: 1; padding: 40px 52px; display: flex; flex-direction: column; gap: 22px; }
        .co-panel { width: 380px; background: var(--white); border-left: 1px solid var(--border); padding: 40px 32px; flex-shrink: 0; display: flex; flex-direction: column; gap: 22px; }
        .back-bar { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--navy); margin-bottom: 4px; }
        .co-card { background: var(--white); border: 1px solid var(--border); border-radius: 16px; padding: 24px 28px; }
        .co-card-title { font-size: 10px; font-weight: 700; letter-spacing: 1.3px; text-transform: uppercase; color: var(--muted); margin-bottom: 18px; }
        .rek-box { background: var(--navy); border-radius: 14px; padding: 20px 24px; color: #fff; }
        .payment-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
        .pay-card { border: 1.5px solid var(--border); border-radius: 14px; padding: 14px; cursor: pointer; background: var(--card); transition: .2s; }
        .pay-card.active { border-color: var(--accent); background: #EEF4FF; box-shadow: 0 8px 18px rgba(46, 124, 246, 0.08); }
        .channel-select { width: 100%; padding: 13px 14px; border: 1.5px solid var(--border); border-radius: 12px; font-family: inherit; color: var(--navy); background: #fff; }
        .upload-zone { border: 2px dashed var(--border); border-radius: 14px; padding: 48px 24px; text-align: center; cursor: pointer; background: var(--card); transition: .2s; }
        .upload-zone:hover { border-color: var(--accent); background: #EEF4FF; }
        .veridity-bar { background: #EEF4FF; border: 1px solid #B8D0EE; border-radius: 12px; padding: 14px 18px; display: flex; gap: 12px; align-items: flex-start; }
        .v-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--accent); margin-top: 4px; flex-shrink: 0; }
        .btn-full { width: 100%; background: var(--accent); color: #fff; font-size: 14px; font-weight: 700; padding: 15px; border-radius: 12px; border: none; cursor: pointer; transition: .15s; text-align: center; }
        .order-summary { background: var(--card); border: 1px solid var(--border); border-radius: 14px; padding: 20px; }
    </style>
@endsection

@section('content')
    <form action="{{ route('distri.order.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="total_amount" id="form-total" value="{{ $product->price * $product->min_qty }}">
        <input type="hidden" name="payment_method" id="payment-method">
        <input type="hidden" name="payment_channel" id="payment-channel">

        <div class="co-layout">
            <div class="co-main">
                <a href="{{ route('distri.catalog') }}" class="back-bar">
                    <div style="width: 34px; height: 34px; border-radius: 10px; background: #fff; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center;">&larr;</div>
                    <div style="font-size: 18px; font-weight: 800;">Kembali ke Katalog</div>
                </a>

                @if ($errors->any())
                    <div style="background: var(--red-bg); border: 1px solid var(--red-border); color: var(--red); border-radius: 12px; padding: 14px; font-size: 13px; font-weight: 700;">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="co-card">
                    <div class="co-card-title">Pilih Metode Pembayaran</div>
                    <div class="payment-grid">
                        @foreach ($paymentMethods as $methodKey => $method)
                            <div class="pay-card" data-method="{{ $methodKey }}" onclick="selectPaymentMethod('{{ $methodKey }}')">
                                <div style="font-size: 14px; font-weight: 800;">{{ $method['label'] }}</div>
                                <div style="font-size: 11px; color: var(--muted); margin-top: 4px;">
                                    {{ $method['requires_proof'] ? 'Perlu unggah bukti' : 'Tanpa bukti manual' }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div style="margin-top: 16px;">
                        <label style="font-size: 12px; font-weight: 800; display:block; margin-bottom:8px;">Channel Pembayaran</label>
                        <select id="payment-channel-select" class="channel-select" onchange="selectPaymentChannel(this.value)"></select>
                    </div>

                    <div class="rek-box" style="margin-top: 16px;">
                        <div style="font-size: 10px; opacity: 0.55; margin-bottom: 8px;">INSTRUKSI PEMBAYARAN</div>
                        <div id="payment-instruction" style="font-size: 14px; font-weight: 700; line-height: 1.7;"></div>
                    </div>
                </div>

                <div class="co-card" id="proof-card">
                    <div class="co-card-title">Upload Bukti Transfer / Nota Pembayaran</div>
                    <div class="upload-zone" onclick="document.getElementById('proof-file').click()">
                        <div style="font-size: 36px; font-weight: 800; margin-bottom: 12px; color: var(--accent);">IMG</div>
                        <div id="upload-label" style="font-size: 15px; font-weight: 700;">Klik di sini untuk mengunggah foto nota atau struk transfer</div>
                        <div style="font-size: 13px; color: var(--muted); margin-top: 4px;">Format: JPG, JPEG, PNG. Ukuran maksimal 10 MB</div>
                        <input type="file" name="proof_of_transfer" id="proof-file" accept="image/*" style="display: none;" onchange="fileChosen(this)" required>
                    </div>
                    <div class="veridity-bar" style="margin-top:16px;">
                        <div class="v-dot"></div>
                        <div style="font-size: 13px; line-height: 1.6;"><strong>Nota akan diperiksa otomatis oleh Veridity Engine.</strong> Sistem memverifikasi keaslian piksel struk transfer untuk mendeteksi manipulasi digital.</div>
                    </div>
                </div>

                <button type="submit" class="btn-full">Kirim Pesanan & Mulai Verifikasi Veridity &rarr;</button>
            </div>

            <div class="co-panel">
                <div style="font-size: 17px; font-weight: 800;">Ringkasan Pesanan</div>
                <div class="order-summary">
                    <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 20px;">
                        <div style="width: 50px; height: 50px; border-radius: 10px; overflow: hidden; background: #fff;">
                            @if ($product->image)
                                <img src="{{ asset('products/' . $product->image) }}" style="width:100%; height:100%; object-fit:cover;">
                            @else
                                <span style="font-size: 12px; color: var(--muted);">No image</span>
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
                        <span style="font-size: 22px; font-weight: 800; color: var(--navy);" id="total-display">Rp {{ number_format($product->price * $product->min_qty, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        const paymentMethods = @json($paymentMethods);

        function selectPaymentMethod(methodKey) {
            const method = paymentMethods[methodKey];
            document.getElementById('payment-method').value = methodKey;

            document.querySelectorAll('.pay-card').forEach((card) => {
                card.classList.toggle('active', card.dataset.method === methodKey);
            });

            const channelSelect = document.getElementById('payment-channel-select');
            channelSelect.innerHTML = '';

            Object.entries(method.channels).forEach(([channelKey, channel]) => {
                const option = document.createElement('option');
                option.value = channelKey;
                option.textContent = channel.label;
                channelSelect.appendChild(option);
            });

            selectPaymentChannel(channelSelect.value);
        }

        function selectPaymentChannel(channelKey) {
            const methodKey = document.getElementById('payment-method').value;
            const method = paymentMethods[methodKey];
            const channel = method.channels[channelKey];
            const proofInput = document.getElementById('proof-file');

            document.getElementById('payment-channel').value = channelKey;
            document.getElementById('payment-instruction').textContent = channel.instruction;
            document.getElementById('proof-card').style.display = method.requires_proof ? 'block' : 'none';
            proofInput.required = method.requires_proof;

            if (! method.requires_proof) {
                proofInput.value = '';
                document.getElementById('upload-label').textContent = 'Bukti pembayaran tidak diperlukan untuk metode ini';
            }
        }

        function fileChosen(input) {
            if (input.files.length > 0) {
                document.getElementById('upload-label').innerHTML = "Berkas siap: <span style='color:var(--green);'>" + input.files[0].name + "</span>";
            }
        }

        function recalcTotal(qty, price) {
            let finalAmt = qty * price;
            document.getElementById('form-total').value = finalAmt;
            document.getElementById('total-display').textContent = "Rp " + finalAmt.toLocaleString('id-ID');
        }

        document.addEventListener('DOMContentLoaded', function () {
            selectPaymentMethod(Object.keys(paymentMethods)[0]);
        });
    </script>
@endsection
