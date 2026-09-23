<?php

namespace App\Support;

/**
 * Catálogo de permisos del sistema (estilo Obsidiana), montado sobre Spatie.
 *
 * El catálogo vive en código, no en la base, porque un permiso solo significa
 * algo si hay código que lo revisa (@can en vistas, ->can en rutas). Lo que sí
 * vive en la base (tablas de Spatie) es la ASIGNACIÓN: qué rol tiene cuál
 * permiso. Se sincroniza con `php artisan permisos:sincronizar`.
 *
 * El rol "admin" no necesita permisos: siempre puede todo (ver AppServiceProvider,
 * Gate::before).
 */
class CatalogoPermisos
{
    /**
     * Permisos agrupados por módulo, como se ven en la pantalla de roles.
     *
     * @return array<string, array{titulo: string, descripcion: string, permisos: array<string, string>}>
     */
    public static function grupos(): array
    {
        return [
            'catalogo' => [
                'titulo' => 'Productos / Catálogo',
                'descripcion' => 'El catálogo público y su sincronización.',
                'permisos' => [
                    'catalogo.ver'      => 'Ver el catálogo de productos',
                    'catalogo.crear'    => 'Crear productos',
                    'catalogo.editar'   => 'Editar productos',
                    'catalogo.eliminar' => 'Eliminar productos',
                    'catalogo.stock'    => 'Ajustar existencias',
                    'catalogo.publicar' => 'Publicar/sincronizar en Mercado Libre y Amazon',
                    'catalogo.analiticas' => 'Ver analíticas del inventario',
                ],
            ],

            'wms' => [
                'titulo' => 'Almacén (WMS)',
                'descripcion' => 'Recepciones, surtido, conteos y movimientos.',
                'permisos' => [
                    'wms.ver'        => 'Entrar al almacén',
                    'wms.recibir'    => 'Registrar recepciones',
                    'wms.mover'      => 'Mover stock y ubicaciones',
                    'wms.picking'    => 'Surtir pedidos (picking)',
                    'wms.conteos'    => 'Hacer conteos',
                    'wms.escanear'   => 'Usar el escáner',
                    'wms.analiticas' => 'Ver analíticas y mapa del almacén',
                ],
            ],

            'cotizaciones' => [
                'titulo' => 'Cotizaciones',
                'descripcion' => 'Propuestas comerciales antes de la venta.',
                'permisos' => [
                    'cotizaciones.ver'      => 'Ver cotizaciones',
                    'cotizaciones.crear'    => 'Crear cotizaciones',
                    'cotizaciones.editar'   => 'Editar cotizaciones',
                    'cotizaciones.eliminar' => 'Eliminar cotizaciones',
                ],
            ],

            'pedidos' => [
                'titulo' => 'Pedidos web',
                'descripcion' => 'Los pedidos de la tienda en línea.',
                'permisos' => [
                    'pedidos.ver'      => 'Ver pedidos web',
                    'pedidos.gestionar' => 'Cambiar estado y gestionar pedidos',
                ],
            ],

            'clientes' => [
                'titulo' => 'Clientes',
                'descripcion' => 'El directorio de clientes.',
                'permisos' => [
                    'clientes.ver'      => 'Ver clientes',
                    'clientes.crear'    => 'Registrar clientes',
                    'clientes.editar'   => 'Editar clientes',
                    'clientes.eliminar' => 'Eliminar clientes',
                ],
            ],

            'proveedores' => [
                'titulo' => 'Proveedores',
                'descripcion' => 'El directorio de proveedores.',
                'permisos' => [
                    'proveedores.ver'      => 'Ver proveedores',
                    'proveedores.gestionar' => 'Crear y editar proveedores',
                ],
            ],

            'licitaciones' => [
                'titulo' => 'Licitaciones',
                'descripcion' => 'Centro de control y tablero de licitaciones.',
                'permisos' => [
                    'licitaciones.ver'      => 'Ver licitaciones',
                    'licitaciones.gestionar' => 'Crear y trabajar licitaciones',
                ],
            ],

            'contabilidad' => [
                'titulo' => 'Contabilidad',
                'descripcion' => 'Gastos, cuentas por pagar/cobrar y reportes.',
                'permisos' => [
                    'contabilidad.ver'      => 'Ver contabilidad',
                    'contabilidad.gestionar' => 'Registrar gastos, pagos y ajustes',
                ],
            ],

            'agenda' => [
                'titulo' => 'Agenda',
                'descripcion' => 'Calendario y eventos.',
                'permisos' => [
                    'agenda.ver'      => 'Ver la agenda',
                    'agenda.gestionar' => 'Crear y editar eventos',
                ],
            ],

            'tickets' => [
                'titulo' => 'Tickets',
                'descripcion' => 'Mesa de ayuda interna.',
                'permisos' => [
                    'tickets.ver'      => 'Ver tickets',
                    'tickets.crear'    => 'Crear tickets',
                    'tickets.gestionar' => 'Atender y cerrar tickets',
                ],
            ],

            'administracion' => [
                'titulo' => 'Administración',
                'descripcion' => 'Quién entra al sistema y qué puede hacer.',
                'permisos' => [
                    'usuarios.ver'     => 'Ver el panel de usuarios',
                    'usuarios.editar'  => 'Editar usuarios y asignarles roles',
                    'roles.gestionar'  => 'Crear roles y definir qué puede cada uno',
                    'actividad.ver'    => 'Ver la bitácora y analíticas de actividad',
                ],
            ],
        ];
    }

    /** Todas las llaves, aplanadas. */
    public static function llaves(): array
    {
        return collect(static::grupos())
            ->flatMap(fn (array $g) => array_keys($g['permisos']))
            ->all();
    }

    /** El texto de un permiso, para mostrarlo en pantalla. */
    public static function etiqueta(string $llave): string
    {
        foreach (static::grupos() as $grupo) {
            if (isset($grupo['permisos'][$llave])) {
                return $grupo['permisos'][$llave];
            }
        }

        return $llave;
    }

    /** ¿Existe este permiso? Evita guardar llaves inventadas. */
    public static function existe(string $llave): bool
    {
        return in_array($llave, static::llaves(), true);
    }
}
