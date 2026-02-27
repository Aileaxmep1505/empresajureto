<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {

            // ===== Campos base que te faltan en hosting =====
            if (!Schema::hasColumn('tickets', 'description')) {
                $table->longText('description')->nullable();
            }

            if (!Schema::hasColumn('tickets', 'area')) {
                $table->string('area', 60)->nullable()->index();
            }

            if (!Schema::hasColumn('tickets', 'assignee_id')) {
                $table->unsignedBigInteger('assignee_id')->nullable()->index();
            }

            // ===== Timestamps de cierre/cancelación =====
            if (!Schema::hasColumn('tickets', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->index();
            }

            if (!Schema::hasColumn('tickets', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->index();
            }

            // ===== Campos de scoring =====
            if (!Schema::hasColumn('tickets', 'impact')) {
                $table->unsignedTinyInteger('impact')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'urgency')) {
                $table->unsignedTinyInteger('urgency')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'effort')) {
                $table->unsignedTinyInteger('effort')->nullable();
            }
            if (!Schema::hasColumn('tickets', 'score')) {
                $table->integer('score')->nullable()->index();
            }

            // ===== Opcional: si quieres FK (solo si tu BD está limpia) =====
            // if (Schema::hasTable('users')) {
            //     $table->foreign('assignee_id')->references('id')->on('users')->nullOnDelete();
            // }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            foreach ([
                'description',
                'area',
                'assignee_id',
                'completed_at',
                'cancelled_at',
                'impact',
                'urgency',
                'effort',
                'score',
            ] as $col) {
                if (Schema::hasColumn('tickets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};