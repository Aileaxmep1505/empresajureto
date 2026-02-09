<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Quitar FK + columna expense_category_id (si existe)
        if (Schema::hasColumn('expenses', 'expense_category_id')) {
            // Intentar eliminar foreign key de forma segura (puede variar el nombre)
            try {
                Schema::table('expenses', function (Blueprint $table) {
                    // Si existe FK estándar, esto funciona:
                    $table->dropForeign(['expense_category_id']);
                });
            } catch (\Throwable $e) {
                // Fallback: intenta detectar y dropear constraint por SQL (MySQL)
                try {
                    $dbName = DB::getDatabaseName();
                    $rows = DB::select("
                        SELECT CONSTRAINT_NAME
                        FROM information_schema.KEY_COLUMN_USAGE
                        WHERE TABLE_SCHEMA = ?
                          AND TABLE_NAME = 'expenses'
                          AND COLUMN_NAME = 'expense_category_id'
                          AND REFERENCED_TABLE_NAME IS NOT NULL
                    ", [$dbName]);

                    foreach ($rows as $r) {
                        $name = $r->CONSTRAINT_NAME ?? null;
                        if ($name) {
                            DB::statement("ALTER TABLE `expenses` DROP FOREIGN KEY `{$name}`");
                        }
                    }
                } catch (\Throwable $e2) {
                    // Si aun así no se puede, continuamos; al dropear columna puede fallar si FK sigue
                    // En ese caso te dirá el nombre exacto en el error y lo quitas manual una vez.
                }
            }

            // Ahora sí dropea la columna
            Schema::table('expenses', function (Blueprint $table) {
                if (Schema::hasColumn('expenses', 'expense_category_id')) {
                    $table->dropColumn('expense_category_id');
                }
            });
        }

        // 2) Agregar campos que faltan para tu nueva lógica
        Schema::table('expenses', function (Blueprint $table) {
            // Identificar si es gasto o movimiento
            if (!Schema::hasColumn('expenses', 'entry_kind')) {
                $table->string('entry_kind', 20)->nullable()->index(); // gasto | movimiento
            }

            // Tipo de gasto (si entry_kind=gasto)
            if (!Schema::hasColumn('expenses', 'expense_type')) {
                $table->string('expense_type', 20)->nullable()->index(); // general | vehiculo | nomina
            }

            // Categorías fijas (no dependes de tabla categories)
            if (!Schema::hasColumn('expenses', 'vehicle_category')) {
                $table->string('vehicle_category', 80)->nullable()->index(); // gasolina, casetas...
            }
            if (!Schema::hasColumn('expenses', 'payroll_category')) {
                $table->string('payroll_category', 80)->nullable()->index(); // bono, pago_quincenal...
            }
            if (!Schema::hasColumn('expenses', 'payroll_period')) {
                $table->string('payroll_period', 80)->nullable()->index(); // 2026-01-Q1, etc
            }

            // Para movimientos tipo "transactions": quién entrega y quién recibe (IDs)
            if (!Schema::hasColumn('expenses', 'manager_id')) {
                $table->unsignedBigInteger('manager_id')->nullable()->index(); // admin que entrega
            }
            if (!Schema::hasColumn('expenses', 'counterparty_id')) {
                $table->unsignedBigInteger('counterparty_id')->nullable()->index(); // usuario que recibe (o tú)
            }

            // Switch "es para mí"
            if (!Schema::hasColumn('expenses', 'movement_self_receive')) {
                $table->boolean('movement_self_receive')->default(false);
            }

            // Modo movimiento: directo o qr
            if (!Schema::hasColumn('expenses', 'movement_mode')) {
                $table->string('movement_mode', 20)->nullable()->index(); // direct | qr
            }

            // Flujo QR (similar a CashTransaction)
            if (!Schema::hasColumn('expenses', 'qr_token')) {
                $table->uuid('qr_token')->nullable()->unique();
            }
            if (!Schema::hasColumn('expenses', 'qr_expires_at')) {
                $table->timestamp('qr_expires_at')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'acknowledged_at')) {
                $table->timestamp('acknowledged_at')->nullable();
            }

            // Notas:
            // - Ya tienes nip_approved_at, nip_approved_by
            // - Ya tienes counterparty_signature_path y manager_signature_path
            //   (sirven para firma del receptor y firma admin, igual que tu controller de transactions)
        });

        // 3) (Opcional) Si ya no usarás payroll_period_id, puedes dejarlo o eliminarlo.
        //    Yo NO lo borro aquí para no romper datos históricos.
        //    Si sí lo quieres borrar, dime y te dejo migración segura para dropear FK + columna.
    }

    public function down(): void
    {
        // Revertir agregados (y NO recreo expense_category_id automáticamente porque puede requerir FK/tabla)
        Schema::table('expenses', function (Blueprint $table) {
            foreach ([
                'entry_kind','expense_type',
                'vehicle_category','payroll_category','payroll_period',
                'manager_id','counterparty_id',
                'movement_self_receive','movement_mode',
                'qr_token','qr_expires_at','acknowledged_at'
            ] as $col) {
                if (Schema::hasColumn('expenses', $col)) {
                    // OJO: si hay índices/unique, primero los quitamos donde aplica
                    if ($col === 'qr_token') {
                        try { $table->dropUnique(['qr_token']); } catch (\Throwable $e) {}
                    }
                    try { $table->dropColumn($col); } catch (\Throwable $e) {}
                }
            }
        });
    }
};
