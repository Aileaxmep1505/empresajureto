<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function test(Request $request)
    {
        Log::info('STRIPE_WEBHOOK_TEST_OK', [
            'ip' => $request->ip(),
            'host' => $request->getHost(),
            'url' => $request->fullUrl(),
            'has_secret' => filled(config('services.stripe.webhook_secret')),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Stripe webhook test OK',
            'time' => now()->toDateTimeString(),
            'host' => $request->getHost(),
            'has_webhook_secret' => filled(config('services.stripe.webhook_secret')),
        ]);
    }

    public function handle(Request $req)
    {
        Log::info('STRIPE_WEBHOOK_RECEIVED', [
            'ip' => $req->ip(),
            'has_signature' => $req->hasHeader('Stripe-Signature'),
            'content_length' => strlen($req->getContent()),
        ]);

        $payload = $req->getContent();
        $sig = $req->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            if (filled($secret)) {
                $event = Webhook::constructEvent($payload, $sig, $secret);
                $event = $event->toArray();
            } else {
                $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (\Throwable $e) {
            Log::warning('STRIPE_WEBHOOK_SIGNATURE_OR_JSON_ERROR', [
                'error' => $e->getMessage(),
            ]);

            return response('invalid', 400);
        }

        $type = (string)($event['type'] ?? '');
        $data = (array)($event['data']['object'] ?? []);

        Log::info('STRIPE_WEBHOOK_EVENT_PARSED', [
            'type' => $type,
            'object_id' => $data['id'] ?? null,
            'payment_intent' => $data['payment_intent'] ?? null,
            'charge' => $data['charge'] ?? null,
        ]);

        try {
            if ($type === 'checkout.session.completed') {
                $this->handleCheckoutSessionCompleted($data);
            } elseif ($type === 'payment_intent.succeeded') {
                $this->handlePaymentIntentSucceeded($data);
            } elseif ($type === 'payment_intent.payment_failed') {
                $this->handlePaymentIntentFailed($data);
            } elseif ($type === 'charge.refunded') {
                $this->handleChargeRefunded($data);
            } elseif (in_array($type, ['refund.created', 'refund.updated', 'refund.failed'], true)) {
                $this->handleRefundEvent($data, $type);
            } else {
                Log::info('STRIPE_WEBHOOK_IGNORED', ['type' => $type]);
            }
        } catch (\Throwable $e) {
            Log::error('STRIPE_WEBHOOK_HANDLE_ERROR', [
                'type' => $type,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response('error', 500);
        }

        return response('ok', 200);
    }

    private function handleCheckoutSessionCompleted(array $data): void
    {
        $sessionId = $data['id'] ?? null;
        $paymentIntent = $data['payment_intent'] ?? null;
        $clientReferenceId = $data['client_reference_id'] ?? null;

        $order = Order::query()
            ->when($sessionId, fn ($q) => $q->where('stripe_session_id', $sessionId))
            ->when($clientReferenceId, fn ($q) => $q->orWhere('id', $clientReferenceId))
            ->first();

        if (!$order) {
            Log::warning('STRIPE_CHECKOUT_COMPLETED_ORDER_NOT_FOUND', [
                'session_id' => $sessionId,
                'client_reference_id' => $clientReferenceId,
                'payment_intent' => $paymentIntent,
            ]);
            return;
        }

        $update = [];
        $this->putOrderColumn($update, 'stripe_payment_intent', $paymentIntent);
        $this->putOrderColumn($update, 'stripe_customer_id', $data['customer'] ?? null);
        $this->putOrderColumn($update, 'updated_at', now());

        if (!empty($update)) {
            DB::table('orders')->where('id', $order->id)->update($update);
        }

        Log::info('STRIPE_CHECKOUT_COMPLETED_OK', [
            'order_id' => $order->id,
            'session_id' => $sessionId,
            'payment_intent' => $paymentIntent,
        ]);
    }

    private function handlePaymentIntentSucceeded(array $pi): void
    {
        $paymentIntentId = $pi['id'] ?? null;
        if (!$paymentIntentId) return;

        $order = Order::query()
            ->where('stripe_payment_intent', $paymentIntentId)
            ->orWhere('stripe_session_id', data_get($pi, 'metadata.session_id', ''))
            ->first();

        if (!$order) {
            Log::warning('STRIPE_PAYMENT_SUCCEEDED_ORDER_NOT_FOUND', [
                'payment_intent' => $paymentIntentId,
                'session_id' => data_get($pi, 'metadata.session_id'),
            ]);
            return;
        }

        $status = strtolower((string)($order->status ?? ''));
        if (!in_array($status, ['reembolsado', 'reembolso_parcial'], true)) {
            $update = [];
            $this->putOrderColumn($update, 'status', 'pagado');
            $this->putOrderColumn($update, 'paid_at', now());
            $this->putOrderColumn($update, 'stripe_payment_intent', $paymentIntentId);
            $this->putOrderColumn($update, 'stripe_charge_id', $pi['latest_charge'] ?? null);
            $this->putOrderColumn($update, 'updated_at', now());
            DB::table('orders')->where('id', $order->id)->update($update);
        }

        $this->insertPaymentSafe([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'currency' => strtoupper($pi['currency'] ?? 'MXN'),
            'amount' => ((int)($pi['amount_received'] ?? 0)) / 100,
            'status' => 'succeeded',
            'payment_intent_id' => $paymentIntentId,
            'charge_id' => $pi['latest_charge'] ?? null,
            'paid_at' => now(),
            'raw' => $pi,
        ]);
    }

    private function handlePaymentIntentFailed(array $pi): void
    {
        $paymentIntentId = $pi['id'] ?? null;
        if (!$paymentIntentId) return;

        $order = Order::query()->where('stripe_payment_intent', $paymentIntentId)->first();
        if (!$order) return;

        $status = strtolower((string)($order->status ?? ''));
        if (!in_array($status, ['reembolsado', 'reembolso_parcial'], true)) {
            $update = [];
            $this->putOrderColumn($update, 'status', 'failed');
            $this->putOrderColumn($update, 'updated_at', now());
            DB::table('orders')->where('id', $order->id)->update($update);
        }
    }

    private function handleChargeRefunded(array $charge): void
    {
        $paymentIntent = $charge['payment_intent'] ?? null;
        $chargeId = $charge['id'] ?? null;
        $amountRefunded = ((int)($charge['amount_refunded'] ?? 0)) / 100;
        $amountCaptured = ((int)($charge['amount_captured'] ?? $charge['amount'] ?? 0)) / 100;

        $fullyRefunded = !empty($charge['refunded']);
        if (!$fullyRefunded && $amountCaptured > 0 && $amountRefunded >= $amountCaptured) {
            $fullyRefunded = true;
        }

        $order = $this->findOrderForStripeRefund($paymentIntent, $chargeId);
        if (!$order) {
            Log::warning('STRIPE_CHARGE_REFUNDED_ORDER_NOT_FOUND', [
                'payment_intent' => $paymentIntent,
                'charge_id' => $chargeId,
                'amount_refunded' => $amountRefunded,
            ]);
            return;
        }

        $refundId = data_get($charge, 'refunds.data.0.id');
        $this->markOrderRefunded($order, [
            'refund_id' => $refundId,
            'refund_status' => $fullyRefunded ? 'succeeded' : 'partial',
            'refunded_amount' => $amountRefunded,
            'fully_refunded' => $fullyRefunded,
            'raw' => $charge,
        ]);
    }

    private function handleRefundEvent(array $refund, string $type): void
    {
        $refundId = $refund['id'] ?? null;
        $paymentIntent = $refund['payment_intent'] ?? null;
        $chargeId = $refund['charge'] ?? null;
        $status = $refund['status'] ?? null;

        if (!$paymentIntent && $chargeId) {
            $paymentIntent = $this->resolvePaymentIntentFromCharge($chargeId);
        }

        $order = $this->findOrderForStripeRefund($paymentIntent, $chargeId);
        if (!$order) {
            Log::warning('STRIPE_REFUND_ORDER_NOT_FOUND', [
                'type' => $type,
                'refund_id' => $refundId,
                'payment_intent' => $paymentIntent,
                'charge_id' => $chargeId,
            ]);
            return;
        }

        if ($type === 'refund.failed') {
            $this->updateRefundMetaOnly($order, [
                'refund_id' => $refundId,
                'refund_status' => $status ?: 'failed',
                'raw' => $refund,
            ]);
            return;
        }

        $amountRefunded = ((int)($refund['amount'] ?? 0)) / 100;
        $orderTotal = (float)($order->total ?? 0);
        $fullyRefunded = $orderTotal > 0 && $amountRefunded >= $orderTotal;

        $this->markOrderRefunded($order, [
            'refund_id' => $refundId,
            'refund_status' => $status ?: $type,
            'refunded_amount' => $amountRefunded,
            'fully_refunded' => $fullyRefunded,
            'raw' => $refund,
        ]);
    }

    private function findOrderForStripeRefund(?string $paymentIntent, ?string $chargeId): ?Order
    {
        if ($paymentIntent && Schema::hasColumn('orders', 'stripe_payment_intent')) {
            $order = Order::query()->where('stripe_payment_intent', $paymentIntent)->latest('id')->first();
            if ($order) return $order;
        }

        if ($chargeId && Schema::hasColumn('orders', 'stripe_charge_id')) {
            $order = Order::query()->where('stripe_charge_id', $chargeId)->latest('id')->first();
            if ($order) return $order;
        }

        foreach (['payments', 'order_payments'] as $table) {
            if ($chargeId && Schema::hasTable($table) && Schema::hasColumn($table, 'charge_id')) {
                $payment = DB::table($table)->where('charge_id', $chargeId)->latest('id')->first();
                if ($payment && !empty($payment->order_id)) return Order::find($payment->order_id);
            }

            if ($paymentIntent && Schema::hasTable($table) && Schema::hasColumn($table, 'payment_intent_id')) {
                $payment = DB::table($table)->where('payment_intent_id', $paymentIntent)->latest('id')->first();
                if ($payment && !empty($payment->order_id)) return Order::find($payment->order_id);
            }
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
        $update = [];
        $this->putOrderColumn($update, 'status', $newStatus);
        $this->putOrderColumn($update, 'stripe_refund_id', $data['refund_id'] ?? null);
        $this->putOrderColumn($update, 'stripe_refund_status', $data['refund_status'] ?? null);
        $this->putOrderColumn($update, 'refunded_amount', round($refundedAmount, 2));
        $this->putOrderColumn($update, 'refunded_at', now());
        $this->putOrderColumn($update, 'stripe_refund_raw', json_encode($data['raw'] ?? [], JSON_UNESCAPED_UNICODE));
        $this->putOrderColumn($update, 'updated_at', now());
        DB::table('orders')->where('id', $order->id)->update($update);

        Log::info('Orden marcada como reembolsada desde Stripe', [
            'order_id' => $order->id,
            'status' => $newStatus,
            'refunded_amount' => round($refundedAmount, 2),
            'refund_id' => $data['refund_id'] ?? null,
        ]);
    }

    private function updateRefundMetaOnly(Order $order, array $data): void
    {
        $update = [];
        $this->putOrderColumn($update, 'stripe_refund_id', $data['refund_id'] ?? null);
        $this->putOrderColumn($update, 'stripe_refund_status', $data['refund_status'] ?? null);
        $this->putOrderColumn($update, 'stripe_refund_raw', json_encode($data['raw'] ?? [], JSON_UNESCAPED_UNICODE));
        $this->putOrderColumn($update, 'updated_at', now());
        if (!empty($update)) DB::table('orders')->where('id', $order->id)->update($update);
    }

    private function insertPaymentSafe(array $data): void
    {
        try {
            $table = Schema::hasTable('payments') ? 'payments' : (Schema::hasTable('order_payments') ? 'order_payments' : null);
            if (!$table) return;
            $insert = [];
            foreach ($data as $column => $value) {
                if (!Schema::hasColumn($table, $column)) continue;
                $insert[$column] = ($column === 'raw' && is_array($value)) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
            }
            if (Schema::hasColumn($table, 'created_at')) $insert['created_at'] = now();
            if (Schema::hasColumn($table, 'updated_at')) $insert['updated_at'] = now();
            if (!empty($insert)) DB::table($table)->insert($insert);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar Payment desde webhook Stripe', ['error' => $e->getMessage()]);
        }
    }

    private function resolvePaymentIntentFromCharge(string $chargeId): ?string
    {
        try {
            $secret = config('services.stripe.secret');
            if (blank($secret) || !str_starts_with((string)$secret, 'sk_')) return null;
            $stripe = new StripeClient($secret);
            $charge = $stripe->charges->retrieve($chargeId, []);
            if (is_object($charge) && method_exists($charge, 'toArray')) $charge = $charge->toArray();
            return $charge['payment_intent'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('No se pudo resolver payment_intent desde charge', ['charge_id' => $chargeId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function putOrderColumn(array &$row, string $column, mixed $value): void
    {
        if ($value !== null && Schema::hasColumn('orders', $column)) {
            $row[$column] = $value;
        }
    }
}
