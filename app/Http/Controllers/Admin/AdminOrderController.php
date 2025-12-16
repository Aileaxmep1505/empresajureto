<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SkydropxProClient;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $req)
    {
        $q = trim((string)$req->get('q'));
        $status = (string)$req->get('status', '');
        $pay    = (string)$req->get('pay', '');
        $ship   = (string)$req->get('ship', '');

        $orders = Order::query()
            ->withCount('items')
            ->latest('created_at');

        if ($q !== '') {
            $orders->where(function($w) use ($q){
                $w->where('id', $q)
                  ->orWhere('customer_email','like',"%{$q}%")
                  ->orWhere('customer_name','like',"%{$q}%")
                  ->orWhere('shipping_code','like',"%{$q}%");
            });
        }

        if ($status !== '') {
            $orders->where('status', $status);
        }

        if ($pay !== '') {
            // paid/pending según tu status
            $orders->where('status', $pay);
        }

        if ($ship !== '') {
            $orders->where('shipment_status', $ship);
        }

        $orders = $orders->paginate(20)->withQueryString();

        return view('admin.orders.index', compact('orders','q','status','pay','ship'));
    }

    public function show(Order $order)
    {
        $order->load('items','payments');
        $rates = session('skydropx_rates_'.$order->id, []);
        return view('admin.orders.show', compact('order','rates'));
    }

    public function skydropxQuote(Request $req, Order $order, SkydropxProClient $skydropx)
    {
        if ($order->status !== 'paid') return back()->with('error','El pedido no está pagado.');

        $addr = (array) ($order->address_json ?? []);
        $zipTo = (string) ($addr['postal_code'] ?? '');

        if (!preg_match('/^\d{5}$/', $zipTo)) {
            return back()->with('error','El pedido no tiene CP válido en la dirección.');
        }

        // Paquete (defaults si aún no calculas pesos reales)
        $parcel = [
            'weight' => (float) $req->input('weight', 1),
            'length' => (float) $req->input('length', 20),
            'width'  => (float) $req->input('width',  15),
            'height' => (float) $req->input('height', 10),

            'to_name'        => $addr['contact_name'] ?? $order->customer_name ?? 'Cliente',
            'to_phone'       => $addr['phone'] ?? '0000000000',
            'to_line1'       => trim(($addr['street'] ?? '').' '.($addr['ext_number'] ?? '')),
            'to_area_level1' => $addr['state'] ?? null,
            'to_area_level2' => $addr['municipality'] ?? null,
            'to_area_level3' => $addr['colony'] ?? null,
        ];

        // 1) quote (para capturar quotation_id real)
        $raw = $skydropx->quote($zipTo, $parcel, null, 10);
        if (!($raw['ok'] ?? false)) {
            return back()->with('error','Skydropx quote falló: '.$raw['status']);
        }

        $qId = data_get($raw, 'json.id') ?: data_get($raw, 'json.data.id');
        if (!$qId) {
            return back()->with('error','Skydropx no devolvió quotation_id.');
        }

        // 2) normaliza rates ordenadas
        $options = $skydropx->quoteBest($zipTo, $parcel, null, 10);
        if (empty($options)) {
            return back()->with('error','No hay tarifas disponibles en Skydropx.');
        }

        $best = $options[0];

        $order->update([
            'skydropx_quotation_id' => (string)$qId,
            'skydropx_rate_id'      => (string)$best['id'],
            'shipping_name'         => $best['carrier'] ?? $order->shipping_name,
            'shipping_service'      => $best['service'] ?? $order->shipping_service,
            'shipment_status'       => 'quoted',
        ]);

        session()->flash('skydropx_rates_'.$order->id, $options);

        return back()->with('ok','Tarifas listas. Selecciona una y compra la guía.');
    }

    public function skydropxBuy(Request $req, Order $order, SkydropxProClient $skydropx)
    {
        if ($order->status !== 'paid') return back()->with('error','El pedido no está pagado.');

        $data = $req->validate([
            'rate_id' => ['required','string'],
        ]);

        if (!$order->skydropx_quotation_id) {
            return back()->with('error','Primero cotiza el envío.');
        }

        $rateId = (string)$data['rate_id'];

        $buy = $skydropx->buyLabel((string)$order->skydropx_quotation_id, $rateId);
        if (!($buy['ok'] ?? false)) {
            return back()->with('error','No se pudo comprar guía: '.$buy['status'].' '.substr((string)$buy['raw'],0,160));
        }

        // ✅ fallback paths (ajusta si tu JSON trae otros)
        $tracking = data_get($buy,'json.tracking_number')
            ?: data_get($buy,'json.tracking')
            ?: data_get($buy,'json.data.tracking_number')
            ?: data_get($buy,'json.data.tracking');

        $labelUrl = data_get($buy,'json.label_url')
            ?: data_get($buy,'json.label.pdf')
            ?: data_get($buy,'json.data.label_url')
            ?: data_get($buy,'json.data.label.pdf');

        $order->update([
            'skydropx_rate_id'    => $rateId,
            'shipping_code'       => $tracking ?: $order->shipping_code,
            'shipping_label_url'  => $labelUrl ?: $order->shipping_label_url,
            'shipment_status'     => 'labeled',
        ]);

        return back()->with('ok','Guía comprada. Ya puedes descargar el PDF.');
    }
}
