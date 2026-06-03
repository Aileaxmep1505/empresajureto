<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('propuesta_aclaracion_preguntas')) {
            Schema::create('propuesta_aclaracion_preguntas', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('propuesta_comercial_id')->nullable();
                $table->unsignedBigInteger('propuesta_comercial_item_id')->nullable();

                $table->unsignedInteger('sort')->default(0);

                $table->string('tipo')->default('aclaracion');
                $table->string('estado')->default('borrador');

                $table->text('texto_usuario')->nullable();
                $table->longText('pregunta_generada')->nullable();

                $table->string('producto_solicitado')->nullable();
                $table->string('producto_sugerido')->nullable();
                $table->string('sku_sugerido')->nullable();
                $table->string('marca_sugerida')->nullable();
                $table->decimal('precio_sugerido', 14, 2)->nullable();

                $table->text('justificacion')->nullable();
                $table->json('fuentes')->nullable();
                $table->json('meta')->nullable();

                $table->timestamps();

                $table->index('propuesta_comercial_id', 'pa_preg_prop_idx');
                $table->index('propuesta_comercial_item_id', 'pa_preg_item_idx');

                $table->foreign('propuesta_comercial_id', 'pa_preg_prop_fk')
                    ->references('id')
                    ->on('propuestas_comerciales')
                    ->cascadeOnDelete();

                $table->foreign('propuesta_comercial_item_id', 'pa_preg_item_fk')
                    ->references('id')
                    ->on('propuesta_comercial_items')
                    ->nullOnDelete();
            });

            return;
        }

        Schema::table('propuesta_aclaracion_preguntas', function (Blueprint $table) {
            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'propuesta_comercial_id')) {
                $table->unsignedBigInteger('propuesta_comercial_id')->nullable();
                $table->index('propuesta_comercial_id', 'pa_preg_prop_idx');
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'propuesta_comercial_item_id')) {
                $table->unsignedBigInteger('propuesta_comercial_item_id')->nullable();
                $table->index('propuesta_comercial_item_id', 'pa_preg_item_idx');
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'sort')) {
                $table->unsignedInteger('sort')->default(0);
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'tipo')) {
                $table->string('tipo')->default('aclaracion');
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'estado')) {
                $table->string('estado')->default('borrador');
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'texto_usuario')) {
                $table->text('texto_usuario')->nullable();
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'pregunta_generada')) {
                $table->longText('pregunta_generada')->nullable();
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'producto_solicitado')) {
                $table->string('producto_solicitado')->nullable();
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'producto_sugerido')) {
                $table->string('producto_sugerido')->nullable();
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'sku_sugerido')) {
                $table->string('sku_sugerido')->nullable();
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'marca_sugerida')) {
                $table->string('marca_sugerida')->nullable();
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'precio_sugerido')) {
                $table->decimal('precio_sugerido', 14, 2)->nullable();
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'justificacion')) {
                $table->text('justificacion')->nullable();
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'fuentes')) {
                $table->json('fuentes')->nullable();
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'meta')) {
                $table->json('meta')->nullable();
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (!Schema::hasColumn('propuesta_aclaracion_preguntas', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        $this->addForeignIfMissing(
            'propuesta_aclaracion_preguntas',
            'pa_preg_prop_fk',
            'propuesta_comercial_id',
            'propuestas_comerciales'
        );

        $this->addForeignIfMissing(
            'propuesta_aclaracion_preguntas',
            'pa_preg_item_fk',
            'propuesta_comercial_item_id',
            'propuesta_comercial_items',
            true
        );
    }

    public function down(): void
    {
        //
    }

    private function addForeignIfMissing(
        string $table,
        string $constraint,
        string $column,
        string $referencesTable,
        bool $setNull = false
    ): void {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();

        if ($exists) {
            return;
        }

        Schema::table($table, function (Blueprint $tableBlueprint) use ($constraint, $column, $referencesTable, $setNull) {
            $foreign = $tableBlueprint->foreign($column, $constraint)
                ->references('id')
                ->on($referencesTable);

            if ($setNull) {
                $foreign->nullOnDelete();
            } else {
                $foreign->cascadeOnDelete();
            }
        });
    }
};