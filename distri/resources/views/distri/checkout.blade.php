@extends('layouts.app')
@section('title', 'Checkout')

@section('styles')
    <style>
        .co-wrap { max-width:1180px; margin:34px auto; padding:0 24px 48px; display:grid; grid-template-columns:1fr 370px; gap:22px; }
        .card { background:#fff; border:1px solid var(--border); border-radius:18px; padding:24px; }
        .payment-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; }
        .pay-card { border:1.5px solid var(--border); border-radius:14px; padding:14px; cursor:pointer; background:var(--card); transition:.2s; }
        .pay-card.active { border-color:var(--accent); background:#EEF4FF; box-shadow:0 8px 18px rgba(46,124,246,.08); }
        .channel-select { width:100%; padding:13px 14px; border:1.5px solid var(--border); border-radius:12px; font-family:inherit; color:var(--navy); background:#fff; }
        .rek-box { background:var(--navy); border-radius:14px; padding:18px 20px; color:#fff; margin-top:16px; }
        .upload-zone { border:2px dashed var(--border); border-radius:14px; padding:42px 22px; text-align:center; cursor:pointer; background:var(--card); transition:.2s; }
        .upload-zone:hover { border-color:var(--accent); background:#EEF4FF; }
        .btn { width:100%; background:var(--accent); color:#fff; font-size:14px; font-weight:800; padding:15px; border-radius:12px; border:none; cursor:pointer; text-align:center; }
        .summary-item { display:flex; justify-content:space-between; gap:14px; padding:12px 0; border-bottom:1px solid var(--border); font-size:13px; }
        .summary-item:last-child { border-bottom:0; }
    </style>
@endsection

@section('content')
    <form action="{{ route('distri.order.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="product_id" value="{{ $items->first()->product_id }}">
        <input type="hidden" name="total_amount" value="{{ $totalAmount }}">
        <input type="hidden" name="payment_method" id="payment-method">
        <input type="hidden" name="payment_channel" id="payment-channel">

        <div class="co-wrap">
            <div style="display:grid; gap:18px;">
                <a href="{{ route('distri.cart') }}" style="text-decoration:none; color:var(--accent); font-weight:900; font-size:13px;">&larr; Kembali ke keranjang</a>

                @if ($errors->any())
                    <div style="background:var(--red-bg); border:1px solid var(--red-border); color:var(--red); border-radius:12px; padding:14px; font-size:13px; font-weight:800;">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="card">
                    <div style="font-size:11px; color:var(--muted); font-weight:900; letter-spacing:1px; text-transform:uppercase;">Alamat Penerima</div>
                    <h2 style="font-family:'Fraunces',serif; font-size:28px; margin:4px 0 18px;">Pilih alamat pengiriman</h2>
                    @forelse ($addresses as $address)
                        <label style="display:block; background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px; margin-bottom:10px; cursor:pointer;">
                            <input type="radio" name="shipping_address_id" value="{{ $address->id }}" @checked($loop->first) required>
                            <strong>{{ $address->label }} - {{ $address->recipient_name }}</strong>
                            <div style="font-size:12px; color:var(--muted); line-height:1.6; margin-top:4px;">{{ $address->phone }} · {{ $address->address_line }}, {{ $address->city }} {{ $address->postal_code }}</div>
                        </label>
                    @empty
                        <div style="background:var(--yellow-bg); border:1px solid var(--yellow-border); color:var(--yellow); padding:14px; border-radius:12px; font-size:13px; font-weight:800;">
                            Belum ada alamat. Tambahkan alamat di halaman Profile sebelum checkout.
                        </div>
                    @endforelse
                </div>

                <div class="card">
                    <div style="font-size:11px; color:var(--muted); font-weight:900; letter-spacing:1px; text-transform:uppercase;">Metode Pembayaran</div>
                    <h2 style="font-family:'Fraunces',serif; font-size:28px; margin:4px 0 18px;">Pilih cara bayar</h2>
                    <div class="payment-grid">
                        @foreach ($paymentMethods as $methodKey => $method)
                            <div class="pay-card" data-method="{{ $methodKey }}" onclick="selectPaymentMethod('{{ $methodKey }}')">
                                <div style="font-size:14px; font-weight:900;">{{ $method['label'] }}</div>
                                <div style="font-size:11px; color:var(--muted); margin-top:4px;">{{ $method['requires_proof'] ? 'Perlu unggah bukti' : 'Tanpa bukti manual' }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top:16px;">
                        <label style="font-size:12px; font-weight:900; display:block; margin-bottom:8px;">Channel Pembayaran</label>
                        <select id="payment-channel-select" class="channel-select" onchange="selectPaymentChannel(this.value)"></select>
                    </div>
                    <div class="rek-box">
                        <div style="font-size:10px; opacity:.55; margin-bottom:8px;">INSTRUKSI PEMBAYARAN</div>
                        <div id="payment-instruction" style="font-size:14px; font-weight:800; line-height:1.7;"></div>
                    </div>
                </div>

                <div class="card" id="proof-card">
                    <div style="font-size:11px; color:var(--muted); font-weight:900; letter-spacing:1px; text-transform:uppercase; margin-bottom:16px;">Bukti Pembayaran</div>
                    <div class="upload-zone" onclick="document.getElementById('proof-file').click()">
                        <div style="font-size:36px; font-weight:900; margin-bottom:12px; color:var(--accent);">IMG</div>
                        <div id="upload-label" style="font-size:15px; font-weight:800;">Unggah foto nota atau struk transfer</div>
                        <div style="font-size:13px; color:var(--muted); margin-top:4px;">JPG, JPEG, PNG. Maksimal 10 MB.</div>
                        <input type="file" name="proof_of_transfer" id="proof-file" accept="image/*" style="display:none;" onchange="fileChosen(this)" required>
                    </div>
                </div>

                <button type="submit" class="btn">Kirim Pesanan & Verifikasi VERIDITY</button>
            </div>

            <div class="card" style="height:max-content;">
                <h3 style="font-size:18px; font-weight:900;">Ringkasan Belanja</h3>
                <div style="margin-top:14px;">
                    @foreach ($items as $item)
                        <div class="summary-item">
                            <div>
                                <strong>{{ $item->name }}</strong>
                                <div style="color:var(--muted); margin-top:3px;">{{ $item->quantity }} x Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                            </div>
                            <strong>Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</strong>
                        </div>
                    @endforeach
                </div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:18px;">
                    <span style="font-weight:900;">Total Bayar</span>
                    <span style="font-size:24px; font-weight:900; color:var(--accent);">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                </div>
                <div style="margin-top:18px;">
                    <label style="font-size:12px; color:var(--muted); font-weight:900; text-transform:uppercase;">Gunakan Voucher</label>
                    <select class="channel-select" name="voucher_code" style="margin-top:8px;">
                        <option value="">Tanpa voucher</option>
                        @foreach ($vouchers as $voucher)
                            <option value="{{ $voucher->code }}">{{ $voucher->code }} - {{ $voucher->name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('distri.vouchers') }}" style="display:block; color:var(--accent); text-decoration:none; font-size:12px; font-weight:900; margin-top:8px;">Lihat Voucher Saya</a>
                </div>
                <p style="font-size:12px; color:var(--muted); line-height:1.6; margin-top:16px;">Bukti pembayaran akan dianalisis oleh VERIDITY untuk memeriksa manipulasi gambar dan kecocokan nominal/rekening.</p>
            </div>
        </div>
    </form>

    <script>
        const paymentMethods = @json($paymentMethods);

        function selectPaymentMethod(methodKey) {
            const method = paymentMethods[methodKey];
            document.getElementById('payment-method').value = methodKey;
            document.querySelectorAll('.pay-card').forEach((card) => card.classList.toggle('active', card.dataset.method === methodKey));

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
        }

        function fileChosen(input) {
            if (input.files.length > 0) {
                document.getElementById('upload-label').innerHTML = "Berkas siap: <span style='color:var(--green);'>" + input.files[0].name + "</span>";
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            selectPaymentMethod(Object.keys(paymentMethods)[0]);
        });
    </script>
@endsection
