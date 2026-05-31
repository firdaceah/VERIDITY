<?php

return [
    'analysis_statuses' => [
        'success' => [
            'label' => 'Aman',
            'meaning' => 'File terlihat asli atau aman',
            'ui_color' => 'success',
        ],
        'warning' => [
            'label' => 'Mencurigakan',
            'meaning' => 'File mencurigakan, campuran, atau membutuhkan review',
            'ui_color' => 'warning',
        ],
        'danger' => [
            'label' => 'Berbahaya',
            'meaning' => 'File sangat berbahaya, deepfake, atau AI generated kuat',
            'ui_color' => 'danger',
        ],
        'error' => [
            'label' => 'Gagal',
            'meaning' => 'Analisis gagal atau service tidak tersedia',
            'ui_color' => 'error',
        ],
    ],

    'distri_payment_methods' => [
        'bank_transfer' => [
            'label' => 'Transfer Bank',
            'channels' => ['BCA', 'BNI', 'BRI', 'Mandiri'],
            'requires_proof' => true,
        ],
        'virtual_account' => [
            'label' => 'Virtual Account',
            'channels' => ['BCA VA', 'BNI VA', 'BRI VA', 'Mandiri VA'],
            'requires_proof' => true,
        ],
        'e_wallet' => [
            'label' => 'E-Wallet',
            'channels' => ['DANA', 'OVO', 'GoPay', 'ShopeePay'],
            'requires_proof' => true,
        ],
        'qris' => [
            'label' => 'QRIS',
            'channels' => ['QRIS Static Demo'],
            'requires_proof' => true,
        ],
        'cod' => [
            'label' => 'Bayar di Tempat',
            'channels' => ['COD'],
            'requires_proof' => false,
        ],
    ],
];

