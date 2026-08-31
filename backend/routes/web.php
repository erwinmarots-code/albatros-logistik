<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

// =============================================
// HALAMAN UTAMA – sekarang arahkan ke app.blade.php (Vue SPA)
// =============================================
Route::get('/', function () {
    return view('app');
});

// =============================================
// ROUTE TEST (untuk mengecek apakah web.php berfungsi)
// =============================================
Route::get('/test-web', function () {
    return response()->json([
        'message' => 'Web route works!',
        'status' => 'online'
    ]);
});

// =============================================
// ROUTE UNTUK MENANGANI REDIRECT LOGIN (jika terjadi)
// =============================================
Route::get('/login', function () {
    return response()->json([
        'message' => 'Unauthenticated. Please login.',
        'status' => 'unauthorized'
    ], 401);
})->name('login');

// =============================================
// 🔥 FALLBACK ROUTE – Semua halaman selain API akan diarahkan ke Vue SPA
// =============================================
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

// =============================================
// CATATAN PENTING
// =============================================
/*
|-----------------------------------------------------------------------
| 1. Semua route API (CRUD) sudah dipindahkan ke routes/api.php
| 2. Route /login ditambahkan untuk menangani redirect jika ada
|    middleware 'auth' yang mencoba redirect ke route login.
| 3. Route ini akan mengembalikan JSON 401, bukan halaman login.
| 4. Fallback route {any} akan menangani semua URL selain API,
|    sehingga Vue Router di frontend dapat menangani routing SPA.
| 5. Pastikan file resources/views/app.blade.php sudah ada.
|-----------------------------------------------------------------------
*/