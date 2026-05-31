<?php

test('phase zero exposes shared analysis status contract', function () {
    $statuses = config('veridity.analysis_statuses');

    expect($statuses)->toHaveKeys(['success', 'warning', 'danger', 'error']);
    expect($statuses['success']['meaning'])->toBe('File terlihat asli atau aman');
    expect($statuses['warning']['meaning'])->toBe('File mencurigakan, campuran, atau membutuhkan review');
    expect($statuses['danger']['meaning'])->toBe('File sangat berbahaya, deepfake, atau AI generated kuat');
    expect($statuses['error']['meaning'])->toBe('Analisis gagal atau service tidak tersedia');
});

test('phase zero exposes distributor payment method contract', function () {
    $paymentMethods = config('veridity.distri_payment_methods');

    expect($paymentMethods)->toHaveKeys(['bank_transfer', 'virtual_account', 'e_wallet', 'qris', 'cod']);
    expect($paymentMethods['bank_transfer']['requires_proof'])->toBeTrue();
    expect($paymentMethods['qris']['requires_proof'])->toBeTrue();
    expect($paymentMethods['cod']['requires_proof'])->toBeFalse();
    expect($paymentMethods['e_wallet']['channels'])->toContain('DANA', 'OVO', 'GoPay', 'ShopeePay');
});

