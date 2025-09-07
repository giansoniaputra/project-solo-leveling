<?php

use App\Http\Controllers\ModalController;
use Illuminate\Support\Facades\Route;

Route::get('/render-modal-otp', function () {
    return response()->json(['data' => ModalController::modalInputKode()]);
});
