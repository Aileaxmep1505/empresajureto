<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public bool $isAdmin;

    public function __construct(Order $order, bool $isAdmin = false)
    {
        $this->order = $order->loadMissing('items');
        $this->isAdmin = $isAdmin;
    }

    public function build()
    {
        $subject = $this->isAdmin
            ? 'Nueva venta pagada - Pedido #' . str_pad((string) $this->order->id, 6, '0', STR_PAD_LEFT)
            : 'Confirmación de compra - Pedido #' . str_pad((string) $this->order->id, 6, '0', STR_PAD_LEFT);

        return $this
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($subject)
            ->view('emails.order_paid', [
                'order' => $this->order,
                'isAdmin' => $this->isAdmin,
            ]);
    }
}
