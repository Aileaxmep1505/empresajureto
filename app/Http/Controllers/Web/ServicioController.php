<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class ServicioController extends Controller
{
    public function index()
    {
        $waPhone = preg_replace('/\D+/', '', env('WHATSAPP_PHONE','5215555555555'));

        $features = [
            [
              'title'   => 'Asesoría en equipamiento de oficina',
              'lead'    => 'Recomendamos cómputo, escritorios y periféricos según el tamaño y crecimiento.',
              'bullets' => ['Levantamiento y layout básico','Comparativas con TCO','Kits por área'],
              'service' => 'Asesoría en equipamiento',
              'img'     => asset('img/servicios/equipamiento.jpg'),
              'grad'    => 'g1',
            ],
            [
              'title'   => 'Mantenimiento básico de equipos',
              'lead'    => 'Instalación de software, antivirus y limpieza interna.',
              'bullets' => ['Paquetes por hora o por lote','Hardening de antivirus','Pruebas de salud'],
              'service' => 'Mantenimiento básico',
              'img'     => asset('img/servicios/mantenimiento.jpg'),
              'grad'    => 'g2',
            ],
            [
              'title'   => 'Impresoras y redes locales',
              'lead'    => 'Instalamos impresoras, Wi-Fi y redes LAN para oficinas o campus.',
              'bullets' => ['Colas de impresión','Segmentación y cobertura Wi-Fi','Capacitación'],
              'service' => 'Impresoras y redes',
              'img'     => asset('img/servicios/redes.jpg'),
              'grad'    => 'g3',
            ],
            [
              'title'   => 'Tienda para instituciones educativas',
              'lead'    => 'Convenios con listas escolares prearmadas y compras centralizadas.',
              'bullets' => ['Listas por grado','Códigos institucionales','Facturación consolidada'],
              'service' => 'Tienda institucional',
              'img'     => asset('img/servicios/escuelas.jpg'),
              'grad'    => 'g4',
            ],
            [
              'title'   => 'Venta por mayoreo',
              'lead'    => 'Precios preferenciales para compras grandes y reabastecimientos.',
              'bullets' => ['Descuentos por volumen','Logística por sucursal','Equivalentes'],
              'service' => 'Mayoreo',
              'img'     => asset('img/servicios/mayoreo.jpg'),
              'grad'    => 'g5',
            ],
        ];

        return view('web.servicios', compact('features', 'waPhone'));
    }
}
