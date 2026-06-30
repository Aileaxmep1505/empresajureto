<?php

/*
|--------------------------------------------------------------------------
| Dónde mandar correo de compra pagada
|--------------------------------------------------------------------------
| Pega esto justo después de crear/actualizar la orden como pagada.
| Ejemplo: en CheckoutController@success, webhook de Stripe, o donde confirmes el pago.
*/

use App\Mail\OrderPaidMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

try {
    $order->loadMissing('items', 'user');

    $customerEmail = $order->customer_email
        ?? $order->user?->email
        ?? auth()->user()?->email;

    if ($customerEmail) {
        Mail::to($customerEmail)->send(new OrderPaidMail($order));
    }

    /*
     * Opcional: correo interno a ventas/admin.
     * Cambia el correo por el real.
     */
    $adminEmail = config('mail.admin_address', env('MAIL_ADMIN_ADDRESS'));

    if ($adminEmail) {
        Mail::to($adminEmail)->send(new OrderPaidMail($order, true));
    }
} catch (\Throwable $e) {
    Log::warning('No se pudo enviar correo de confirmación de compra', [
        'order_id' => $order->id ?? null,
        'error' => $e->getMessage(),
    ]);
}
