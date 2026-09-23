<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Accesos directos del tablero.
 *
 * Aquí viven los módulos agrupados tal como salen en el menú completo
 * (/panel/menu). Cada tarjeta de "Accesos" del tablero pinta uno de estos
 * grupos; la de "Acciones rápidas" pinta los botones de crear.
 *
 * Un acceso cuya ruta no existe se descarta solo, así el tablero no se
 * rompe si un módulo se quita o se renombra.
 */
class DashboardAccesos
{
    /**
     * Grupos de módulos con sus accesos, ya filtrados a rutas que existen.
     *
     * @return array<string, array{titulo: string, icono: string, items: array<int, array<string, mixed>>}>
     */
    public static function grupos(): array
    {
        $grupos = [
            'finanzas' => [
                'titulo' => 'Finanzas y Ventas',
                'icono' => 'payments',
                'items' => [
                    self::item('Cotizaciones', 'request_quote', 'propuestas-comerciales.index'),
                    self::item('Facturas', 'receipt_long', 'manual_invoices.index'),
                    self::item('Compras y Ventas', 'article', 'publications.index'),
                    self::item('Part. contable', 'monitoring', 'partcontable.index'),
                    self::item('Gastos', 'receipt', 'expenses.index'),
                ],
            ],
            'inventario' => [
                'titulo' => 'Inventario y Productos',
                'icono' => 'inventory_2',
                'items' => [
                    self::item('Productos', 'deployed_code', 'admin.catalog.index'),
                    self::item('Catálogo', 'view_in_ar', 'products.index'),
                    self::item('Fichas técnicas', 'list_alt', 'tech-sheets.index'),
                    self::item('Almacén', 'warehouse', 'admin.wms.home'),
                    self::item('Activos e Inventario', 'inventory_2', null, url('/internal-assets')),
                ],
            ],
            'operaciones' => [
                'titulo' => 'Operaciones',
                'icono' => 'local_shipping',
                'items' => [
                    self::item('Vehículos', 'local_shipping', 'vehicles.index'),
                    self::item('Logística', 'alt_route', 'routes.index'),
                    self::item('Agenda', 'calendar_month', 'agenda.calendar'),
                ],
            ],
            'clientes' => [
                'titulo' => 'Clientes y Comunicación',
                'icono' => 'groups',
                'items' => [
                    self::item('Clientes', 'groups', 'clients.index'),
                    self::item('Proveedores', 'domain', 'providers.index'),
                    self::item('WhatsApp', 'chat', 'admin.whatsapp.conversations', null, 'Nuevo'),
                    self::item('Help Desk', 'support_agent', 'admin.help.index'),
                    self::item('Correo', 'mail', 'mail.index'),
                    self::item('Mi Perfil', 'account_circle', 'profile.show'),
                ],
            ],
            'licitaciones' => [
                'titulo' => 'Licitaciones',
                'icono' => 'gavel',
                'items' => [
                    self::item('Centro de control', 'gavel', 'projects.control', null, 'Nuevo'),
                    self::item('Tablero de licitaciones', 'view_kanban', 'projects.index'),
                ],
            ],
            'tickets' => [
                'titulo' => 'Tickets',
                'icono' => 'confirmation_number',
                'items' => [
                    self::item('Tickets', 'confirmation_number', 'tickets.index'),
                    self::item('Mis tickets', 'person', 'tickets.my'),
                    self::item('Nuevo ticket', 'add_circle', 'tickets.create'),
                ],
            ],
            'admin' => [
                'titulo' => 'Administración y Control',
                'icono' => 'admin_panel_settings',
                'items' => [
                    self::item('Contabilidad', 'monitoring', 'accounting.dashboard', null, 'Nuevo'),
                    self::item('Documentación', 'folder_open', null, url('/confidential/vault/6')),
                    self::item('Documentación de altas', 'folder_managed', 'alta.docs.index'),
                    self::item('Usuarios', 'manage_accounts', 'admin.users.index'),
                    self::item('Pedidos web', 'shopping_bag', 'admin.orders.index'),
                    self::item('Menú completo', 'apps', 'dashboard.menu'),
                ],
            ],
            'manager' => [
                'titulo' => 'Accesos',
                'icono' => 'apps',
                'items' => [
                    self::item('Mi Perfil', 'account_circle', 'profile.show'),
                    self::item('Part. contable', 'monitoring', 'partcontable.index'),
                    self::item('Documentación de altas', 'description', 'alta.docs.index'),
                ],
            ],
        ];

        foreach ($grupos as &$grupo) {
            $grupo['items'] = array_values(array_filter($grupo['items']));
        }

        return $grupos;
    }

    /** Un grupo por su clave, o nulo si no existe. */
    public static function grupo(string $clave): ?array
    {
        return self::grupos()[$clave] ?? null;
    }

    /**
     * Botones de crear / ir a lo más usado. Entre más grande la tarjeta,
     * más botones.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function accionesRapidas(int $nivel = 1): array
    {
        $items = [
            self::item('Nueva cotización', 'add_circle', 'propuestas-comerciales.create'),
            self::item('Nuevo cliente', 'person_add', 'clients.create'),
            self::item('Nuevo producto', 'add_box', 'admin.catalog.create'),
            self::item('Nuevo ticket', 'add_task', 'tickets.create'),
        ];

        // En 2 columnas caben 3 botones por fila: 6 llenan dos filas justas.
        if ($nivel >= 2) {
            $items[] = self::item('Nueva factura', 'receipt_long', 'manual_invoices.create');
            $items[] = self::item('Nuevo gasto', 'receipt', 'expenses.create');
        }

        if ($nivel >= 3) {
            $items[] = self::item('Agenda', 'calendar_month', 'agenda.calendar');
            $items[] = self::item('WhatsApp', 'chat', 'admin.whatsapp.conversations');
            $items[] = self::item('Licitaciones', 'gavel', 'projects.index');
            $items[] = self::item('Pedidos web', 'shopping_bag', 'admin.orders.index');
            $items[] = self::item('Almacén', 'warehouse', 'admin.wms.home');
            $items[] = self::item('Menú completo', 'apps', 'dashboard.menu');
        }

        return array_values(array_filter($items));
    }

    /**
     * Arma un acceso. Si la ruta con nombre no existe se devuelve nulo y el
     * acceso se descarta.
     */
    private static function item(string $label, string $icon, ?string $route, ?string $url = null, ?string $badge = null): ?array
    {
        if ($route !== null) {
            if (! Route::has($route)) {
                return null;
            }

            $url = route($route);
        }

        if (! $url) {
            return null;
        }

        return ['label' => $label, 'icon' => $icon, 'url' => $url, 'badge' => $badge];
    }
}
