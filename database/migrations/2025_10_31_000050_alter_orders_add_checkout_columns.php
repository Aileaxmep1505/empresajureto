<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        $db = DB::getDatabaseName();
        return DB::table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    public function up(): void
    {
        // ====== Bloque 1: columnas principales con posicionamiento seguro ======
        Schema::table('orders', function (Blueprint $table) {
            // billing_profile_id con posición segura
            if (!Schema::hasColumn('orders', 'billing_profile_id')) {
                $col = $table->foreignId('billing_profile_id')->nullable();

                if (Schema::hasColumn('orders', 'user_id')) {
                    $col->after('user_id');
                } elseif (Schema::hasColumn('orders', 'customer_email')) {
                    $col->after('customer_email');
                }
            }

            // customer_name / customer_email
            if (!Schema::hasColumn('orders', 'customer_name')) {
                // si existe billing_profile_id, colocar después; si no, sin after()
                if (Schema::hasColumn('orders', 'billing_profile_id')) {
                    $table->string('customer_name', 190)->nullable()->after('billing_profile_id');
                } else {
                    $table->string('customer_name', 190)->nullable();
                }
            }

            if (!Schema::hasColumn('orders', 'customer_email')) {
                if (Schema::hasColumn('orders', 'customer_name')) {
                    $table->string('customer_email', 190)->nullable()->after('customer_name');
                } else {
                    $table->string('customer_email', 190)->nullable();
                }
            }

            // Totales
            if (!Schema::hasColumn('orders', 'shipping_amount')) {
                $col = $table->decimal('shipping_amount', 10, 2)->default(0);
                if (Schema::hasColumn('orders', 'subtotal')) {
                    $col->after('subtotal');
                }
            }
            if (!Schema::hasColumn('orders', 'tax')) {
                $col = $table->decimal('tax', 10, 2)->default(0);
                if (Schema::hasColumn('orders', 'shipping_amount')) {
                    $col->after('shipping_amount');
                }
            }
            if (!Schema::hasColumn('orders', 'currency')) {
                $col = $table->string('currency', 3)->default('MXN');
                if (Schema::hasColumn('orders', 'tax')) {
                    $col->after('tax');
                }
            }
            if (!Schema::hasColumn('orders', 'status')) {
                $col = $table->string('status', 32)->default('pending');
                if (Schema::hasColumn('orders', 'currency')) {
                    $col->after('currency');
                }
            }

            // Dirección y envío
            if (!Schema::hasColumn('orders', 'address_json')) {
                $col = $table->json('address_json')->nullable();
                if (Schema::hasColumn('orders', 'status')) {
                    $col->after('status');
                }
            }
            if (!Schema::hasColumn('orders', 'shipping_code')) {
                $col = $table->string('shipping_code', 80)->nullable();
                if (Schema::hasColumn('orders', 'address_json')) {
                    $col->after('address_json');
                }
            }
            if (!Schema::hasColumn('orders', 'shipping_name')) {
                $col = $table->string('shipping_name', 120)->nullable();
                if (Schema::hasColumn('orders', 'shipping_code')) {
                    $col->after('shipping_code');
                }
            }
            if (!Schema::hasColumn('orders', 'shipping_service')) {
                $col = $table->string('shipping_service', 120)->nullable();
                if (Schema::hasColumn('orders', 'shipping_name')) {
                    $col->after('shipping_name');
                }
            }
            if (!Schema::hasColumn('orders', 'shipping_eta')) {
                $col = $table->string('shipping_eta', 120)->nullable();
                if (Schema::hasColumn('orders', 'shipping_service')) {
                    $col->after('shipping_service');
                }
            }
            if (!Schema::hasColumn('orders', 'shipping_store_pays')) {
                $col = $table->boolean('shipping_store_pays')->default(false);
                if (Schema::hasColumn('orders', 'shipping_eta')) {
                    $col->after('shipping_eta');
                }
            }
            if (!Schema::hasColumn('orders', 'shipping_carrier_cost')) {
                $col = $table->decimal('shipping_carrier_cost', 10, 2)->default(0);
                if (Schema::hasColumn('orders', 'shipping_store_pays')) {
                    $col->after('shipping_store_pays');
                }
            }

            // Stripe / Factura
            if (!Schema::hasColumn('orders', 'stripe_session_id')) {
                $col = $table->string('stripe_session_id', 191)->nullable();
                if (Schema::hasColumn('orders', 'shipping_carrier_cost')) {
                    $col->after('shipping_carrier_cost');
                }
            }
            if (!Schema::hasColumn('orders', 'stripe_payment_intent')) {
                $col = $table->string('stripe_payment_intent', 191)->nullable();
                if (Schema::hasColumn('orders', 'stripe_session_id')) {
                    $col->after('stripe_session_id');
                }
            }
            if (!Schema::hasColumn('orders', 'invoice_id')) {
                $col = $table->string('invoice_id', 191)->nullable();
                if (Schema::hasColumn('orders', 'stripe_payment_intent')) {
                    $col->after('stripe_payment_intent');
                }
            }
        });

        // ====== Bloque 2: FK opcional a billing_profiles ======
        if (Schema::hasTable('billing_profiles') && Schema::hasColumn('orders', 'billing_profile_id')) {
            Schema::table('orders', function (Blueprint $table) {
                // Evita duplicar la FK si ya existe
                try {
                    $table->foreign('billing_profile_id')
                          ->references('id')->on('billing_profiles')
                          ->nullOnDelete();
                } catch (\Throwable $e) {
                    // ignora si ya existe
                }
            });
        }

        // ====== Bloque 3: índices seguros ======
        if (Schema::hasColumn('orders', 'user_id') && !$this->indexExists('orders', 'orders_user_id_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('user_id', 'orders_user_id_index');
            });
        }
        if (Schema::hasColumn('orders', 'stripe_session_id') && !$this->indexExists('orders', 'orders_stripe_session_id_index')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index('stripe_session_id', 'orders_stripe_session_id_index');
            });
        }
    }

    public function down(): void
    {
        // Quita FK si la agregamos
        if (Schema::hasColumn('orders', 'billing_profile_id')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropForeign(['billing_profile_id']);
                });
            } catch (\Throwable $e) {
                // si no existía, continuar
            }
        }

        // Drop columnas (MySQL elimina índices dependientes en cascada)
        Schema::table('orders', function (Blueprint $table) {
            $cols = [
                'billing_profile_id',
                'customer_name',
                'customer_email',
                'shipping_amount',
                'tax',
                'currency',
                'status',
                'address_json',
                'shipping_code',
                'shipping_name',
                'shipping_service',
                'shipping_eta',
                'shipping_store_pays',
                'shipping_carrier_cost',
                'stripe_session_id',
                'stripe_payment_intent',
                'invoice_id',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
