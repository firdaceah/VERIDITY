@extends('layouts.app')
@section('title', 'Keranjang')

@section('styles')
    <style>
        .cart-wrap { max-width:1050px; margin:36px auto; padding:0 24px; display:grid; grid-template-columns:1fr 330px; gap:22px; }
        .card { background:#fff; border:1px solid var(--border); border-radius:18px; padding:22px; }
        .item { display:grid; grid-template-columns:34px 80px 1fr auto; gap:16px; align-items:center; padding:16px 0; border-bottom:1px solid var(--border); }
        .item:last-child { border-bottom:0; }
        .thumb { width:80px; height:80px; border-radius:14px; overflow:hidden; background:var(--card); }
        .thumb img { width:100%; height:100%; object-fit:cover; }
        .btn { border:0; border-radius:12px; padding:12px 16px; background:var(--accent); color:#fff; font-weight:800; cursor:pointer; text-decoration:none; text-align:center; }
        .qty { width:72px; border:1px solid var(--border); border-radius:10px; padding:9px; text-align:center; }
        .cart-check { width:20px; height:20px; accent-color:var(--accent); }
        .voucher-select { width:100%; border:1px solid var(--border); border-radius:12px; padding:11px 12px; color:var(--navy); font-family:inherit; margin-top:10px; }
        .voucher-list { display:grid; gap:8px; margin-top:10px; }
        .voucher-option { border:1.5px solid var(--border); border-radius:12px; padding:10px; background:var(--card); color:var(--navy); font-family:inherit; text-align:left; cursor:pointer; }
        .voucher-option.active { border-color:var(--accent); background:#EEF4FF; }
    </style>
@endsection

@section('content')
    @php $total = $items->sum(fn($item) => $item->price * $item->quantity); @endphp
    <div class="cart-wrap">
        <div class="card">
            <h2 style="font-family:'Fraunces',serif; font-size:30px;">Keranjang Belanja</h2>
            <p style="font-size:13px; color:var(--muted); margin-top:4px;">Pilih produk yang ingin masuk ke checkout.</p>

            @if (session('success'))
                <div style="background:var(--green-bg); color:var(--green); border:1px solid var(--green-border); padding:12px; border-radius:12px; margin-top:16px; font-weight:800;">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div style="background:var(--red-bg); color:var(--red); border:1px solid var(--red-border); padding:12px; border-radius:12px; margin-top:16px; font-weight:800;">{{ session('error') }}</div>
            @endif

            @if ($items->isNotEmpty())
                <form id="selected-checkout-form" method="POST" action="{{ route('distri.cart.checkout-selected') }}">
                    @csrf
                </form>
            @endif

            @forelse ($items as $item)
                <div class="item">
                    <label style="display:flex; align-items:center; justify-content:center;">
                        <input class="cart-check" form="selected-checkout-form" type="checkbox" name="cart_item_ids[]" value="{{ $item->id }}" data-subtotal="{{ $item->price * $item->quantity }}" data-quantity="{{ $item->quantity }}" checked>
                    </label>
                    <div class="thumb">
                        @if ($item->image_url)
                            <img src="{{ $item->image_url }}" alt="{{ $item->name }}">
                        @elseif ($item->image)
                            <img src="{{ asset('products/' . $item->image) }}" alt="{{ $item->name }}">
                        @endif
                    </div>
                    <div>
                        <div style="font-weight:900;">{{ $item->name }}</div>
                        <div style="font-size:12px; color:var(--muted); margin-top:4px;">{{ $item->category_name ?? 'Minimarket' }} - Rp {{ number_format($item->price, 0, ',', '.') }}</div>
                        @if (($item->discount_percentage ?? 0) > 0)
                            <div style="font-size:12px; color:var(--red); font-weight:800; margin-top:4px;">Diskon {{ number_format($item->discount_percentage, 0) }}% dari Rp {{ number_format($item->original_price, 0, ',', '.') }}</div>
                        @endif
                        <div style="font-size:13px; font-weight:900; color:var(--accent); margin-top:8px;">Subtotal Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</div>
                    </div>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <form method="POST" action="{{ route('distri.cart.update', $item->id) }}">
                            @csrf
                            @method('PATCH')
                            <input class="qty" type="number" name="quantity" min="1" value="{{ $item->quantity }}" onchange="this.form.submit()">
                        </form>
                        <form method="POST" action="{{ route('distri.cart.delete', $item->id) }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn" style="background:var(--red);" type="submit">Hapus</button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="padding:42px 0; color:var(--muted);">Keranjang masih kosong.</div>
            @endforelse
        </div>

        <div class="card" style="height:max-content;">
            <div style="font-size:17px; font-weight:900;">Ringkasan</div>
            <div style="margin-top:16px;">
                <label style="font-size:12px; color:var(--muted); font-weight:900; text-transform:uppercase;">Voucher</label>
                <select class="voucher-select" id="cart-voucher-code" name="voucher_code" form="selected-checkout-form">
                    <option value="">Tanpa voucher</option>
                    @foreach ($vouchers as $voucher)
                        <option value="{{ $voucher->code }}" data-type="{{ $voucher->type }}" data-value="{{ $voucher->value }}" data-minimum="{{ $voucher->minimum_order }}" @selected(($selectedVoucherCode ?? '') === $voucher->code)>
                            {{ $voucher->code }} - {{ $voucher->name }}
                        </option>
                    @endforeach
                </select>
                <div class="voucher-list">
                    <button type="button" class="voucher-option {{ empty($selectedVoucherCode) ? 'active' : '' }}" data-code="" onclick="pickCartVoucher(this)">
                        <strong>Tanpa voucher</strong>
                        <div style="font-size:12px; color:var(--muted); margin-top:4px;">Checkout tanpa potongan.</div>
                    </button>
                    @foreach ($vouchers as $voucher)
                        <button type="button" class="voucher-option {{ ($selectedVoucherCode ?? '') === $voucher->code ? 'active' : '' }}" data-code="{{ $voucher->code }}" onclick="pickCartVoucher(this)">
                            <strong>{{ $voucher->code }}</strong>
                            <div style="font-size:12px; color:var(--muted); margin-top:4px;">Minimal Rp {{ number_format($voucher->minimum_order, 0, ',', '.') }}</div>
                        </button>
                    @endforeach
                </div>
                <a href="{{ route('distri.vouchers', ['checkout' => 'cart']) }}" style="display:block; margin-top:10px; color:var(--accent); font-size:12px; font-weight:900; text-decoration:none;">Lihat Voucher Saya</a>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:18px; font-size:14px;">
                <span>Item dipilih</span>
                <strong id="selected-items">{{ $items->sum('quantity') }}</strong>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:12px; font-size:14px;">
                <span>Subtotal</span>
                <strong id="selected-subtotal">Rp {{ number_format($total, 0, ',', '.') }}</strong>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:12px; font-size:14px;">
                <span>Potongan voucher</span>
                <strong id="selected-discount" style="color:var(--green);">- Rp 0</strong>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:12px; font-size:14px;">
                <span>Total setelah voucher</span>
                <strong id="selected-total" style="color:var(--accent); font-size:22px;">Rp {{ number_format($total, 0, ',', '.') }}</strong>
            </div>
            <button class="btn" form="selected-checkout-form" type="submit" style="width:100%; margin-top:20px; display:block; {{ $items->isEmpty() ? 'pointer-events:none; opacity:.45;' : '' }}">Checkout</button>
            <a href="{{ route('distri.catalog') }}" style="display:block; margin-top:14px; text-align:center; color:var(--accent); font-weight:800; text-decoration:none;">Belanja lagi</a>
        </div>
    </div>

    <script>
        function formatRupiah(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(value)));
        }

        function updateSelectedCart() {
            let total = 0;
            let quantity = 0;
            document.querySelectorAll('.cart-check:checked').forEach((check) => {
                total += Number(check.dataset.subtotal || 0);
                quantity += Number(check.dataset.quantity || 0);
            });
            const discount = selectedCartDiscount(total);
            document.getElementById('selected-subtotal').textContent = formatRupiah(total);
            document.getElementById('selected-discount').textContent = '- ' + formatRupiah(discount);
            document.getElementById('selected-total').textContent = formatRupiah(total - discount);
            document.getElementById('selected-items').textContent = quantity;
        }

        function selectedCartDiscount(subtotal) {
            const select = document.getElementById('cart-voucher-code');
            if (!select) {
                return 0;
            }

            const selected = select.options[select.selectedIndex];
            if (!selected || !selected.value || subtotal < Number(selected.dataset.minimum || 0)) {
                return 0;
            }

            const value = Number(selected.dataset.value || 0);
            const discount = selected.dataset.type === 'percent' ? subtotal * (value / 100) : value;
            return Math.min(discount, subtotal);
        }

        function syncCartVoucherCards(code) {
            document.querySelectorAll('.voucher-option').forEach((item) => {
                item.classList.toggle('active', (item.dataset.code || '') === (code || ''));
            });
        }

        function pickCartVoucher(button) {
            const code = button.dataset.code || '';
            document.getElementById('cart-voucher-code').value = code;
            syncCartVoucherCards(code);
            updateSelectedCart();
        }

        document.querySelectorAll('.cart-check').forEach((check) => check.addEventListener('change', updateSelectedCart));
        document.getElementById('cart-voucher-code')?.addEventListener('change', function () {
            syncCartVoucherCards(this.value);
            updateSelectedCart();
        });
        updateSelectedCart();
    </script>
@endsection
