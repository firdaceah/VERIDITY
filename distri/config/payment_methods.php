<?php

return [
    'transfer_bank' => [
        'label' => 'Transfer Bank',
        'requires_proof' => true,
        'channels' => [
            'bca' => [
                'label' => 'BCA',
                'instruction' => 'Transfer ke 1234 5678 9012 a.n. PT Distri Nusantara Jaya, lalu unggah bukti transfer.',
            ],
            'bni' => [
                'label' => 'BNI',
                'instruction' => 'Transfer ke 8800 1122 3344 a.n. PT Distri Nusantara Jaya, lalu unggah bukti transfer.',
            ],
            'bri' => [
                'label' => 'BRI',
                'instruction' => 'Transfer ke 0099 8877 6655 a.n. PT Distri Nusantara Jaya, lalu unggah bukti transfer.',
            ],
            'mandiri' => [
                'label' => 'Mandiri',
                'instruction' => 'Transfer ke 1410 8899 2211 a.n. PT Distri Nusantara Jaya, lalu unggah bukti transfer.',
            ],
        ],
    ],
    'virtual_account' => [
        'label' => 'Virtual Account',
        'requires_proof' => true,
        'channels' => [
            'va_bca' => [
                'label' => 'VA BCA',
                'instruction' => 'Bayar ke VA simulasi 39012 + nomor order, lalu unggah bukti pembayaran.',
            ],
            'va_mandiri' => [
                'label' => 'VA Mandiri',
                'instruction' => 'Bayar ke VA simulasi 88708 + nomor order, lalu unggah bukti pembayaran.',
            ],
        ],
    ],
    'e_wallet' => [
        'label' => 'E-Wallet',
        'requires_proof' => true,
        'channels' => [
            'dana' => [
                'label' => 'DANA',
                'instruction' => 'Kirim ke nomor 0812-3456-7890 a.n. Distri Nusantara, lalu unggah screenshot pembayaran.',
            ],
            'ovo' => [
                'label' => 'OVO',
                'instruction' => 'Kirim ke nomor 0812-3456-7890 a.n. Distri Nusantara, lalu unggah screenshot pembayaran.',
            ],
            'gopay' => [
                'label' => 'GoPay',
                'instruction' => 'Kirim ke nomor 0812-3456-7890 a.n. Distri Nusantara, lalu unggah screenshot pembayaran.',
            ],
            'shopeepay' => [
                'label' => 'ShopeePay',
                'instruction' => 'Kirim ke nomor 0812-3456-7890 a.n. Distri Nusantara, lalu unggah screenshot pembayaran.',
            ],
        ],
    ],
    'qris' => [
        'label' => 'QRIS',
        'requires_proof' => true,
        'channels' => [
            'qris_static' => [
                'label' => 'QRIS Simulasi',
                'instruction' => 'Scan QRIS simulasi pada layar checkout, lalu unggah screenshot transaksi berhasil.',
            ],
        ],
    ],
    'cod' => [
        'label' => 'Bayar di Tempat',
        'requires_proof' => false,
        'channels' => [
            'cod_standard' => [
                'label' => 'COD Standar',
                'instruction' => 'Pembayaran dilakukan saat barang diterima. Bukti pembayaran tidak perlu diunggah.',
            ],
        ],
    ],
];
