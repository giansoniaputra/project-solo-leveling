<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ModalController;
use Laragear\WebAuthn\Http\Routes as WebAuthnRoutes;
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
// Route WebAuthn
WebAuthnRoutes::register();
Route::get('/login', function () {
    return view('auth.initial-login');
});
Route::post('/login', [AuthController::class, 'authentication']);



Route::get('/auth', function () {
    return view('auth.login');
})->middleware('guest')->name('login');
Route::middleware('auth')->group(function () {
    Route::get('/auth/register', function () {
        return view('auth.register');
    });
    Route::get('/', function () {
        return view('welcome');
    });
    Route::get('/home', function () {
        return view('welcome');
    });
});

Route::get('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/auth'); // Or wherever you want to redirect after logout
});
