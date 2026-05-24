@extends('layouts.app')
@section('title', 'Pesanan Saya')

@section('styles')
    <style>
        .orders-layout {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 24px;
        }

        .o-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 8px;
        }

        .b-checking {
            background: #FEF3C7;
            color: #92400E;
        }

        .b-verified {
            background: #DCFCE7;
            color: #15803D;
        }

        .b-rejected {
            background: #FEE2E2;
            color: #991B1B;
        }

        .cancel-btn {
            background: none;
            border: 1px solid #FCA5A5;
            color: var(--red);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
        }

        .cancel-btn:hover {
            background: #FEF2F2;
        }
    </style>
@endsection

@section('content')
    <div class="orders-layout">
        <div style="margin-bottom: 24px;">
            <h2 style="font-family: 'Fraunces', serif; font-size: 28px;">Pesanan Saya</h2>
            <p style="font-size: 13px; color: var(--muted);">Pantau status forensik bukti pembayaran digital Anda via Veridity Engine.</p>
        </div>

        @if (session('success'))
            <div style="background: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: 700; font-size: 14px;">
                {{ session('success') }}
            </div>
        @endif

        @if ($orders->isEmpty())
            <div style="text-align: center; padding: 48px; background: #fff; border-radius: 16px; border: 1px solid var(--border);">
                <span style="font-size: 48px;">📦</span>
                <div style="font-weight: 700; margin-top: 12px;">Belum Ada Riwayat Transaksi</div>
                <p style="font-size: 13px; color: var(--muted); margin-top: 4px;">Silakan menuju katalog untuk mulai memesan barang grosir.</p>
            </div>
        @else
            @foreach ($orders as $order)
                <div class="o-card">
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <div style="width: 60px; height: 60px; border-radius: 12px; overflow: hidden; background: #f9f9f9; border: 1px solid var(--border);">
                            @if ($order->product_image)
                                <img src="{{ asset('products/' . $order->product_image) }}" style="width:100%; height:100%; object-fit:cover;">
                            @else
                                📦
                            @endif
                        </div>
                        <div>
                            <div style="font-size: 11px; color: var(--muted); letter-spacing: 0.5px;">KODE: {{ $order->order_id_string }}</div>
                            <div style="font-size: 16px; font-weight: 700; margin: 2px 0;">{{ $order->product_name }}</div>
                            
                            <div style="font-size: 13px; color: var(--navy2);">
                                Kuantitas: <strong>{{ $order->quantity }} {{ $order->unit ?? 'unit' }}</strong> · Total: <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 16px;">
                        <span class="badge b-{{ $order->veridity_status }}">
                            ● {{ strtoupper($order->veridity_status) }}
                        </span>

                        @if ($order->veridity_status === 'checking')
                            {{-- DIUBAH: Menggunakan class 'cancel-order-form' dan membuang onsubmit bawaan --}}
                            <form action="{{ route('distri.order.delete', $order->id) }}" method="POST" class="cancel-order-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="cancel-btn btn-trigger-cancel">Batalkan</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Script Interaktif Pembatalan Pesanan dengan SweetAlert2 --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cancelButtons = document.querySelectorAll('.btn-trigger-cancel');
            
            cancelButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const form = this.closest('.cancel-order-form');
                    
                    Swal.fire({
                        title: 'Batalkan Pesanan?',
                        text: "Data antrean pesanan grosir ini akan dihapus permanen dari sistem!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#991B1B', // Warna merah tombol batalkan
                        cancelButtonColor: '#637899',  // Warna muted bawaan distri
                        confirmButtonText: 'Ya, Batalkan!',
                        cancelButtonText: 'Kembali',
                        background: '#FFFFFF',
                        customClass: {
                            popup: 'animated fadeIn'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit(); // Kirim form hapus rill ke Oracle server jika klik OK
                        }
                    });
                });
            });
        });
    </script>
@endsection