<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Order;
use App\Models\Payment;

class StripeWebhookController extends Controller
{
    public function handle(Request $req)
    {
        $payload = $req->getContent();
        $sig     = $req->header('Stripe-Signature');

        $secret = config('services.stripe.webhook_secret');

        if (blank($secret)) {
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
                $this->handleCheckoutSessionCompleted($data);
            }

            if ($type === 'payment_intent.succeeded') {
                $this->handlePaymentIntentSucceeded($data);
            }

            if ($type === 'payment_intent.payment_failed') {
                $this->handlePaymentIntentFailed($data);
            }

            if ($type === 'charge.refunded') {
                $this->handleChargeRefunded($data);
            }

            if (in_array($type, ['refund.created', 'refund.updated', 'refund.failed'], true)) {
                $this->handleRefundEvent($data, $type);
            }
        } catch (\Throwable $e) {
            Log::error('Stripe webhook handle error: '.$e->getMessage(), [
                'type' => $type,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        return response('ok', 200);
    }

    private function handleCheckoutSessionCompleted(array $data): void
    {
        $order = Order::query()
            ->where('stripe_session_id', $data['id'] ?? '')
            ->orWhere('id', $data['client_reference_id'] ?? 0)
            ->first();

        if (!$order) {
            Log::warning('checkout.session.completed sin orden encontrada', [
                'stripe_session_id' => $data['id'] ?? null,
                'client_reference_id' => $data['client_reference_id'] ?? null,
            ]);
            return;
        }

        $updates = [];

        if (Schema::hasColumn('orders', 'stripe_payment_intent')) {
            $updates['stripe_payment_intent'] = $data['payment_intent'] ?? $order->stripe_payment_intent ?? null;
        }

        if (Schema::hasColumn('orders', 'stripe_customer_id')) {
            $updates['stripe_customer_id'] = $data['customer'] ?? $order->stripe_customer_id ?? null;
        }

        if (!empty($updates)) {
            $updates['updated_at'] = now();
            DB::table('orders')->where('id', $order->id)->update($updates);
        }
    }

    private function handlePaymentIntentSucceeded(array $pi): void
    {
        $paymentIntentId = $pi['id'] ?? null;
        if (!$paymentIntentId) return;

        $order = Order::query()
            ->where('stripe_payment_intent', $paymentIntentId)
            ->orWhere('stripe_session_id', $pi['metadata']['session_id'] ?? '')
            ->first();

        if (!$order) {
            Log::warning('payment_intent.succeeded sin orden encontrada', [
                'payment_intent' => $paymentIntentId,
            ]);
            return;
        }

        $currentStatus = strtolower((string)($order->status ?? ''));

        if (!in_array($currentStatus, ['paid', 'pagado', 'reembolsado', 'reembolso_parcial'], true)) {
            $updates = ['updated_at' => now()];

            if (Schema::hasColumn('orders', 'status')) {
                $updates['status'] = 'pagado';
            }

            if (Schema::hasColumn('orders', 'paid_at')) {
                $updates['paid_at'] = now();
            }

            if (Schema::hasColumn('orders', 'stripe_payment_intent')) {
                $updates['stripe_payment_intent'] = $paymentIntentId;
            }

            if (Schema::hasColumn('orders', 'stripe_charge_id')) {
                $updates['stripe_charge_id'] = $pi['latest_charge'] ?? null;
            }

            DB::table('orders')->where('id', $order->id)->update($updates);
        }

        $this->createPaymentSafe([
            'order_id'          => $order->id,
            'provider'          => 'stripe',
            'currency'          => strtoupper($pi['currency'] ?? 'MXN'),
            'amount'            => ((int)($pi['amount_received'] ?? 0)) / 100,
            'status'            => 'succeeded',
            'payment_intent_id' => $paymentIntentId,
            'charge_id'         => $pi['latest_charge'] ?? null,
            'receipt_url'       => null,
            'paid_at'           => now(),
            'raw'               => $pi,
        ]);
    }

    private function handlePaymentIntentFailed(array $pi): void
    {
        $paymentIntentId = $pi['id'] ?? null;
        if (!$paymentIntentId) return;

        $order = Order::query()
            ->where('stripe_payment_intent', $paymentIntentId)
            ->first();

        if (!$order) return;

        $currentStatus = strtolower((string)($order->status ?? ''));

        if (!in_array($currentStatus, ['failed', 'fallido', 'reembolsado', 'reembolso_parcial'], true)) {
            $updates = ['updated_at' => now()];

            if (Schema::hasColumn('orders', 'status')) {
                $updates['status'] = 'failed';
            }

            DB::table('orders')->where('id', $order->id)->update($updates);
        }

        $this->createPaymentSafe([
            'order_id'          => $order->id,
            'provider'          => 'stripe',
            'currency'          => strtoupper($pi['currency'] ?? 'MXN'),
            'amount'            => ((int)($pi['amount'] ?? 0)) / 100,
            'status'            => 'failed',
            'payment_intent_id' => $paymentIntentId,
            'failure_code'      => $pi['last_payment_error']['code'] ?? null,
            'failure_message'   => $pi['last_payment_error']['message'] ?? null,
            'raw'               => $pi,
        ]);
    }

    private function handleChargeRefunded(array $charge): void
    {
        $paymentIntent = $charge['payment_intent'] ?? null;
        $chargeId = $charge['id'] ?? null;

        $amountRefunded = ((int)($charge['amount_refunded'] ?? 0)) / 100;
        $amountCaptured = ((int)($charge['amount_captured'] ?? $charge['amount'] ?? 0)) / 100;

        $isFullyRefunded = !empty($charge['refunded'])
            || ($amountCaptured > 0 && $amountRefunded >= $amountCaptured);

        $order = $this->findOrderForRefund($paymentIntent, $chargeId);

        if (!$order) {
            Log::warning('charge.refunded recibido pero no se encontró orden', [
                'payment_intent' => $paymentIntent,
                'charge_id' => $chargeId,
                'amount_refunded' => $amountRefunded,
            ]);
            return;
        }

        $refundId = $charge['refunds']['data'][0]['id'] ?? null;

        $this->markOrderRefunded($order, [
            'refund_id'       => $refundId,
            'refund_status'   => $isFullyRefunded ? 'succeeded' : 'partial',
            'refunded_amount' => $amountRefunded,
            'fully_refunded'  => $isFullyRefunded,
            'raw'             => $charge,
        ]);

        $this->createPaymentSafe([
            'order_id'          => $order->id,
            'provider'          => 'stripe',
            'currency'          => strtoupper($charge['currency'] ?? 'MXN'),
            'amount'            => $amountRefunded,
            'status'            => $isFullyRefunded ? 'refunded' : 'partial_refund',
            'payment_intent_id' => $paymentIntent,
            'charge_id'         => $chargeId,
            'refund_id'         => $refundId,
            'raw'               => $charge,
        ]);
    }

    private function handleRefundEvent(array $refund, string $type): void
    {
        $refundId = $refund['id'] ?? null;
        $paymentIntent = $refund['payment_intent'] ?? null;
        $chargeId = $refund['charge'] ?? null;
        $status = $refund['status'] ?? null;

        if ($type === 'refund.failed') {
            $order = $this->findOrderForRefund($paymentIntent, $chargeId);

            if ($order) {
                $this->updateOrderRefundMetaOnly($order, [
                    'refund_id' => $refundId,
                    'refund_status' => $status ?: 'failed',
                    'raw' => $refund,
                ]);
            }

            Log::warning('Stripe refund.failed recibido', [
                'order_id' => $order->id ?? null,
                'refund_id' => $refundId,
                'payment_intent' => $paymentIntent,
                'charge_id' => $chargeId,
            ]);

            return;
        }

        if (!$paymentIntent && $chargeId) {
            $paymentIntent = $this->resolvePaymentIntentFromCharge($chargeId);
        }

        $order = $this->findOrderForRefund($paymentIntent, $chargeId);

        if (!$order) {
            Log::warning('refund.* recibido pero no se encontró orden', [
                'type' => $type,
                'refund_id' => $refundId,
                'payment_intent' => $paymentIntent,
                'charge_id' => $chargeId,
            ]);
            return;
        }

        $amountRefunded = ((int)($refund['amount'] ?? 0)) / 100;
        $orderTotal = (float)($order->total ?? 0);
        $fullyRefunded = $orderTotal > 0 && $amountRefunded >= $orderTotal;

        $this->markOrderRefunded($order, [
            'refund_id'       => $refundId,
            'refund_status'   => $status ?: $type,
            'refunded_amount' => $amountRefunded,
            'fully_refunded'  => $fullyRefunded,
            'raw'             => $refund,
        ]);

        $this->createPaymentSafe([
            'order_id'          => $order->id,
            'provider'          => 'stripe',
            'currency'          => strtoupper($refund['currency'] ?? 'MXN'),
            'amount'            => $amountRefunded,
            'status'            => $fullyRefunded ? 'refunded' : 'partial_refund',
            'payment_intent_id' => $paymentIntent,
            'charge_id'         => $chargeId,
            'refund_id'         => $refundId,
            'raw'               => $refund,
        ]);
    }

    private function findOrderForRefund(?string $paymentIntent, ?string $chargeId): ?Order
    {
        if ($paymentIntent && Schema::hasColumn('orders', 'stripe_payment_intent')) {
            $order = Order::query()->where('stripe_payment_intent', $paymentIntent)->latest('id')->first();
            if ($order) return $order;
        }

        if ($chargeId && Schema::hasColumn('orders', 'stripe_charge_id')) {
            $order = Order::query()->where('stripe_charge_id', $chargeId)->latest('id')->first();
            if ($order) return $order;
        }

        if ($chargeId && Schema::hasTable('payments') && Schema::hasColumn('payments', 'charge_id')) {
            $payment = DB::table('payments')->where('charge_id', $chargeId)->latest('id')->first();
            if ($payment && !empty($payment->order_id)) return Order::find($payment->order_id);
        }

        if ($paymentIntent && Schema::hasTable('payments') && Schema::hasColumn('payments', 'payment_intent_id')) {
            $payment = DB::table('payments')->where('payment_intent_id', $paymentIntent)->latest('id')->first();
            if ($payment && !empty($payment->order_id)) return Order::find($payment->order_id);
        }

        return null;
    }

    private function markOrderRefunded(Order $order, array $data): void
    {
        $currentRefunded = (float)($order->refunded_amount ?? 0);
        $incomingRefunded = (float)($data['refunded_amount'] ?? 0);
        $refundedAmount = max($currentRefunded, $incomingRefunded);

        $orderTotal = (float)($order->total ?? 0);
        $fullyRefunded = (bool)($data['fully_refunded'] ?? false);

        if ($orderTotal > 0 && $refundedAmount >= $orderTotal) {
            $fullyRefunded = true;
        }

        $newStatus = $fullyRefunded ? 'reembolsado' : 'reembolso_parcial';

        $updates = ['updated_at' => now()];

        if (Schema::hasColumn('orders', 'status')) $updates['status'] = $newStatus;
        if (Schema::hasColumn('orders', 'stripe_refund_id')) $updates['stripe_refund_id'] = $data['refund_id'] ?? null;
        if (Schema::hasColumn('orders', 'stripe_refund_status')) $updates['stripe_refund_status'] = $data['refund_status'] ?? null;
        if (Schema::hasColumn('orders', 'refunded_amount')) $updates['refunded_amount'] = round($refundedAmount, 2);
        if (Schema::hasColumn('orders', 'refunded_at')) $updates['refunded_at'] = now();
        if (Schema::hasColumn('orders', 'stripe_refund_raw')) $updates['stripe_refund_raw'] = json_encode($data['raw'] ?? [], JSON_UNESCAPED_UNICODE);

        DB::table('orders')->where('id', $order->id)->update($updates);

        Log::info('Orden marcada como reembolsada desde Stripe', [
            'order_id' => $order->id,
            'status' => $newStatus,
            'refunded_amount' => round($refundedAmount, 2),
            'refund_id' => $data['refund_id'] ?? null,
        ]);
    }

    private function updateOrderRefundMetaOnly(Order $order, array $data): void
    {
        $updates = ['updated_at' => now()];

        if (Schema::hasColumn('orders', 'stripe_refund_id')) $updates['stripe_refund_id'] = $data['refund_id'] ?? null;
        if (Schema::hasColumn('orders', 'stripe_refund_status')) $updates['stripe_refund_status'] = $data['refund_status'] ?? null;
        if (Schema::hasColumn('orders', 'stripe_refund_raw')) $updates['stripe_refund_raw'] = json_encode($data['raw'] ?? [], JSON_UNESCAPED_UNICODE);

        DB::table('orders')->where('id', $order->id)->update($updates);
    }

    private function createPaymentSafe(array $data): void
    {
        try {
            if (!Schema::hasTable('payments')) return;

            $insert = [];

            foreach ($data as $column => $value) {
                if (!Schema::hasColumn('payments', $column)) continue;

                $insert[$column] = ($column === 'raw' && is_array($value))
                    ? json_encode($value, JSON_UNESCAPED_UNICODE)
                    : $value;
            }

            if (Schema::hasColumn('payments', 'created_at')) $insert['created_at'] = now();
            if (Schema::hasColumn('payments', 'updated_at')) $insert['updated_at'] = now();

            if (!empty($insert)) DB::table('payments')->insert($insert);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar Payment desde webhook Stripe', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolvePaymentIntentFromCharge(string $chargeId): ?string
    {
        try {
            $secret = config('services.stripe.secret');

            if (blank($secret) || !str_starts_with((string)$secret, 'sk_')) {
                return null;
            }

            $stripe = new \Stripe\StripeClient($secret);
            $charge = $stripe->charges->retrieve($chargeId, []);

            if (is_object($charge) && method_exists($charge, 'toArray')) {
                $charge = $charge->toArray();
            }

            return $charge['payment_intent'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('No se pudo resolver payment_intent desde charge', [
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
