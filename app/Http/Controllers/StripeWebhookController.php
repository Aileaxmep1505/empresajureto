<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Payment;

class StripeWebhookController extends Controller
{
    public function handle(Request $req)
    {
        $payload = $req->getContent();
        $sig     = $req->header('Stripe-Signature');

        $secret = config('services.stripe.webhook_secret'); // STRIPE_WEBHOOK_SECRET
        if (blank($secret)) { // si no hay secreta, aceptamos "best effort" (dev)
            $event = json_decode($payload, true);
        } else {
            try {
                $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
                $event = $event->toArray();
            } catch (\Throwable $e) {
                Log::warning('Stripe webhook signature error: '.$e->getMessage());
                return response('invalid', 400);
            }
        }

        $type = $event['type'] ?? '';
        $data = $event['data']['object'] ?? [];

        try {
            if ($type === 'checkout.session.completed') {
                // Marcar order_id desde client_reference_id o stripe_session_id
                $order = Order::query()
                    ->where('stripe_session_id', $data['id'] ?? '')
                    ->orWhere('id', $data['client_reference_id'] ?? 0)
                    ->first();

                if ($order) {
                    $order->stripe_payment_intent = $data['payment_intent'] ?? $order->stripe_payment_intent;
                    $order->stripe_customer_id    = $data['customer'] ?? $order->stripe_customer_id;
                    // status real se fija en payment_intent.succeeded (abajo)
                    $order->save();
                }
            }

            if ($type === 'payment_intent.succeeded') {
                $pi = $data;

                $order = Order::query()
                    ->where('stripe_payment_intent', $pi['id'])
                    ->orWhere('stripe_session_id', $pi['metadata']['session_id'] ?? '')
                    ->first();

                if ($order && $order->status !== 'paid') {
                    $order->status  = 'paid';
                    $order->paid_at = now();
                    $order->save();

                    // Registrar Payment
                    Payment::create([
                        'order_id'          => $order->id,
                        'provider'          => 'stripe',
                        'currency'          => strtoupper($pi['currency'] ?? 'mxn'),
                        'amount'            => ((int)$pi['amount_received'] ?? 0) / 100,
                        'status'            => 'succeeded',
                        'payment_intent_id' => $pi['id'] ?? null,
                        'charge_id'         => $pi['latest_charge'] ?? null,
                        'receipt_url'       => null,
                        'paid_at'           => now(),
                        'raw'               => $pi,
                    ]);
                }
            }

            if ($type === 'payment_intent.payment_failed') {
                $pi = $data;

                $order = Order::query()
                    ->where('stripe_payment_intent', $pi['id'])
                    ->first();

                if ($order && $order->status !== 'failed') {
                    $order->status = 'failed';
                    $order->save();

                    Payment::create([
                        'order_id'          => $order->id,
                        'provider'          => 'stripe',
                        'currency'          => strtoupper($pi['currency'] ?? 'mxn'),
                        'amount'            => ((int)$pi['amount'] ?? 0) / 100,
                        'status'            => 'failed',
                        'payment_intent_id' => $pi['id'] ?? null,
                        'failure_code'      => $pi['last_payment_error']['code'] ?? null,
                        'failure_message'   => $pi['last_payment_error']['message'] ?? null,
                        'raw'               => $pi,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Stripe webhook handle error: '.$e->getMessage(), ['type'=>$type]);
        }

        return response('ok', 200);
    }
}
