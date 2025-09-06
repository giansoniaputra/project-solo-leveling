<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebAuthn\WebAuthnLoginController;
use App\Http\Controllers\WebAuthn\WebAuthnRegisterController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::middleware('guest')->group(function () {
    // Register dengan Face ID
    Route::get('/register/webauthn/options', [WebAuthnRegisterController::class, 'options']);
    Route::post('/register/webauthn', [WebAuthnRegisterController::class, 'create']);

    // Login otomatis Face ID
    Route::get('/auth', function () {
        return view('auth.login');
    })->name('login');
    Route::get('/login/webauthn/options', [WebAuthnLoginController::class, 'options']);
    Route::post('/login/webauthn', [WebAuthnLoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
});
