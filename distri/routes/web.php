<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;

// ─── 1. JALUR GUEST (Belum Login) ───
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// ─── JALUR PROTEKSI UTAMA (Wajib Login) ───x
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ─── 2. SEGMEN HAK AKSES RESELLER ───
    // Kita bungkus pake Route::group biasa, lalu logic role-nya ditaruh di dalam method controller atau dicek langsung di sini
    Route::group([], function () {

        // PENTING: Kita filter lewat sistem pengecekan role saat halaman diakses
        Route::get('/', function () {
            if (auth()->user()->role !== 'reseller') return redirect()->route('admin.products.index');
            return app(OrderController::class)->landing();
        })->name('distri.landing');

        Route::get('/katalog', function () {
            if (auth()->user()->role !== 'reseller') return redirect()->route('admin.products.index');
            return app(OrderController::class)->catalog();
        })->name('distri.catalog');

        Route::get('/produk/{id}', function ($id) {
            if (auth()->user()->role !== 'reseller') return redirect()->route('admin.products.index');
            return app(OrderController::class)->productDetail($id);
        })->name('distri.product.show');

        Route::get('/checkout/{id}', function ($id) {
            if (auth()->user()->role !== 'reseller') return redirect()->route('admin.products.index');
            return app(OrderController::class)->checkout($id);
        })->name('distri.checkout');

        // Form aksi transaksi grosir
        Route::post('/order/store', [OrderController::class, 'storeOrder'])->name('distri.order.store');
        Route::get('/pesanan-saya', [OrderController::class, 'orderHistory'])->name('distri.orders');
        Route::get('/pesanan-saya/{id}', [OrderController::class, 'showOrder'])->name('distri.order.show');
        Route::delete('/order/delete/{id}', [OrderController::class, 'destroyOrder'])->name('distri.order.delete');
        Route::get('/profile', [OrderController::class, 'profile'])->name('distri.profile');
        Route::get('/profile/edit', [OrderController::class, 'editProfile'])->name('distri.profile.edit');
        Route::put('/profile', [OrderController::class, 'updateProfile'])->name('distri.profile.update');
        Route::post('/profile/addresses', [OrderController::class, 'storeAddress'])->name('distri.addresses.store');
        Route::get('/voucher-saya', [OrderController::class, 'vouchers'])->name('distri.vouchers');
        Route::get('/voucher-saya/{code}/pakai', [OrderController::class, 'useVoucher'])->name('distri.vouchers.use');
        Route::get('/keranjang', [CartController::class, 'index'])->name('distri.cart');
        Route::post('/keranjang', [CartController::class, 'add'])->name('distri.cart.add');
        Route::post('/keranjang/checkout-selected', [CartController::class, 'checkoutSelected'])->name('distri.cart.checkout-selected');
        Route::patch('/keranjang/{id}', [CartController::class, 'update'])->name('distri.cart.update');
        Route::delete('/keranjang/{id}', [CartController::class, 'destroy'])->name('distri.cart.delete');
    });

    // ─── 3. SEGMEN HAK AKSES ADMIN ───
    Route::prefix('admin')->name('admin.')->group(function () {

        // Proteksi menyeluruh: jika bukan admin, langsung lempar error 403
        Route::get('/products', function () {
            if (auth()->user()->role !== 'admin') abort(403, 'Akses Ditolak!');
            return app(AdminProductController::class)->index();
        })->name('products.index');

        Route::get('/products/create', function () {
            if (auth()->user()->role !== 'admin') abort(403, 'Akses Ditolak!');
            return app(AdminProductController::class)->create();
        })->name('products.create');

        Route::post('/products/store', [AdminProductController::class, 'store'])->name('products.store');
        Route::post('/products/sync-dummy', [AdminProductController::class, 'syncDummyProducts'])->name('products.sync-dummy');
        Route::get('/products/{id}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{id}/update', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{id}/delete', [AdminProductController::class, 'destroy'])->name('products.destroy');
        Route::get('/stores', [AdminProductController::class, 'stores'])->name('stores.index');
        Route::get('/orders', [AdminProductController::class, 'orders'])->name('orders.index');
        Route::patch('/orders/{id}/status', [AdminProductController::class, 'updateOrderStatus'])->name('orders.update-status');
        Route::get('/veridity-validation', [AdminProductController::class, 'veridityOrders'])->name('products.veridity');
        Route::get('/veridity-validation/{id}', [AdminProductController::class, 'showVeridityOrder'])->name('products.veridity.show');
        Route::post('/veridity-validation/{id}/retry', [AdminProductController::class, 'retryVeridity'])->name('products.veridity.retry');
        Route::post('/veridity-validation/{id}/manual-accept', [AdminProductController::class, 'manualAccept'])->name('products.veridity.manual-accept');
        Route::post('/veridity-validation/{id}/manual-reject', [AdminProductController::class, 'manualReject'])->name('products.veridity.manual-reject');
    });
});

// Jalur khusus bypass gambar produk dari folder terisolasi Windows
Route::get('/view-product-image/{filename}', function ($filename) {
    $path = storage_path('app/public/products/' . $filename);
    if (!file_exists($path)) abort(404);
    
    // Membaca file fisik dan mengalirkannya sebagai respon gambar yang sah
    return response()->file($path);
})->name('product.image.stream');
