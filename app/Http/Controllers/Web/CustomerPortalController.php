<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CustomerPortalController extends Controller
{
    public function welcome()
    {
        $customer = Auth::guard('customer')->user();
        $cart     = session('cart', []);

        // Usa tu Blade que pegaste (puedes guardarlo como: resources/views/web/customer/welcome.blade.php)
        return view('web.customer.welcome', compact('customer', 'cart'));
    }
}
