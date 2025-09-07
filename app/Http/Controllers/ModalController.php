<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ModalController extends Controller
{
    public static function modalInputKode()
    {
        $view = View::make('content-modal.modal-otp')->render();
        $view2 = View::make('content-modal.modal-otp-glitch')->render();
        return [$view, $view2];
    }
}
