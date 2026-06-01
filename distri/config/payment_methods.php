<?php

return [
    'transfer_bank' => [
        'label' => 'Transfer Bank',
        'requires_proof' => true,
        'channels' => [
            'bca' => [
                'label' => 'BCA',
                'recipient_name' => 'PT Distri Nusantara Jaya',
                'recipient_account' => '123456789012',
                'instruction' => 'Transfer ke 1234 5678 9012 a.n. PT Distri Nusantara Jaya, lalu unggah bukti transfer.',
            ],
            'bni' => [
                'label' => 'BNI',
                'recipient_name' => 'PT Distri Nusantara Jaya',
                'recipient_account' => '880011223344',
                'instruction' => 'Transfer ke 8800 1122 3344 a.n. PT Distri Nusantara Jaya, lalu unggah bukti transfer.',
            ],
            'bri' => [
                'label' => 'BRI',
                'recipient_name' => 'PT Distri Nusantara Jaya',
                'recipient_account' => '009988776655',
                'instruction' => 'Transfer ke 0099 8877 6655 a.n. PT Distri Nusantara Jaya, lalu unggah bukti transfer.',
            ],
            'mandiri' => [
                'label' => 'Mandiri',
                'recipient_name' => 'PT Distri Nusantara Jaya',
                'recipient_account' => '141088992211',
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
                'recipient_name' => 'PT Distri Nusantara Jaya',
                'recipient_account' => '39012',
                'instruction' => 'Bayar ke VA simulasi 39012 + nomor order, lalu unggah bukti pembayaran.',
            ],
            'va_mandiri' => [
                'label' => 'VA Mandiri',
                'recipient_name' => 'PT Distri Nusantara Jaya',
                'recipient_account' => '88708',
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
                'recipient_name' => 'Distri Nusantara',
                'recipient_account' => '081234567890',
                'instruction' => 'Kirim ke nomor 0812-3456-7890 a.n. Distri Nusantara, lalu unggah screenshot pembayaran.',
            ],
            'ovo' => [
                'label' => 'OVO',
                'recipient_name' => 'Distri Nusantara',
                'recipient_account' => '081234567890',
                'instruction' => 'Kirim ke nomor 0812-3456-7890 a.n. Distri Nusantara, lalu unggah screenshot pembayaran.',
            ],
            'gopay' => [
                'label' => 'GoPay',
                'recipient_name' => 'Distri Nusantara',
                'recipient_account' => '081234567890',
                'instruction' => 'Kirim ke nomor 0812-3456-7890 a.n. Distri Nusantara, lalu unggah screenshot pembayaran.',
            ],
            'shopeepay' => [
                'label' => 'ShopeePay',
                'recipient_name' => 'Distri Nusantara',
                'recipient_account' => '081234567890',
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
                'recipient_name' => 'PT Distri Nusantara Jaya',
                'recipient_account' => 'QRIS-DISTRI-001',
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
                'recipient_name' => 'PT Distri Nusantara Jaya',
                'recipient_account' => 'COD',
                'instruction' => 'Pembayaran dilakukan saat barang diterima. Bukti pembayaran tidak perlu diunggah.',
            ],
        ],
    ],
];
