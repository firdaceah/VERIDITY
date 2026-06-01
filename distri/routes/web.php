<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
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

        Route::get('/checkout/{id}', function ($id) {
            if (auth()->user()->role !== 'reseller') return redirect()->route('admin.products.index');
            return app(OrderController::class)->checkout($id);
        })->name('distri.checkout');

        // Form aksi transaksi grosir
        Route::post('/order/store', [OrderController::class, 'storeOrder'])->name('distri.order.store');
        Route::get('/pesanan-saya', [OrderController::class, 'orderHistory'])->name('distri.orders');
        Route::delete('/order/delete/{id}', [OrderController::class, 'destroyOrder'])->name('distri.order.delete');
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
        Route::get('/products/{id}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{id}/update', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{id}/delete', [AdminProductController::class, 'destroy'])->name('products.destroy');
        Route::get('/veridity-validation', [AdminProductController::class, 'veridityOrders'])->name('products.veridity');
        Route::post('/veridity-validation/{id}/retry', [AdminProductController::class, 'retryVeridity'])->name('products.veridity.retry');
    });
});

// Jalur khusus bypass gambar produk dari folder terisolasi Windows
Route::get('/view-product-image/{filename}', function ($filename) {
    $path = storage_path('app/public/products/' . $filename);
    if (!file_exists($path)) abort(404);
    
    // Membaca file fisik dan mengalirkannya sebagai respon gambar yang sah
    return response()->file($path);
})->name('product.image.stream');
