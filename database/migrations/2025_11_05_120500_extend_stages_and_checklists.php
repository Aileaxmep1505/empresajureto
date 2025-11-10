<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Asegura tabla stages
        Schema::table('ticket_stages', function (Blueprint $t) {
            if (!Schema::hasColumn('ticket_stages', 'assignee_id')) {
                $t->unsignedBigInteger('assignee_id')->nullable()->after('name');
            }
            if (!Schema::hasColumn('ticket_stages', 'ai_prompt')) {
                $t->string('ai_prompt', 255)->nullable()->after('assignee_id');
            }
            if (!Schema::hasColumn('ticket_stages', 'ai_instructions')) {
                $t->text('ai_instructions')->nullable()->after('ai_prompt'); // resumen/mejorado por IA
            }
            if (!Schema::hasColumn('ticket_stages', 'requires_evidence')) {
                $t->boolean('requires_evidence')->default(false)->after('ai_instructions');
            }
            if (!Schema::hasColumn('ticket_stages', 'started_at')) {
                $t->timestamp('started_at')->nullable()->after('due_at');
            }
            if (!Schema::hasColumn('ticket_stages', 'finished_at')) {
                $t->timestamp('finished_at')->nullable()->after('started_at');
            }
        });

        // Tabla de checklists del stage
        if (!Schema::hasTable('ticket_checklists')) {
            Schema::create('ticket_checklists', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('ticket_id');
                $t->unsignedBigInteger('stage_id');
                $t->string('title', 180)->nullable();
                $t->text('instructions')->nullable(); // texto generado por IA para orientar
                $t->unsignedBigInteger('assigned_to')->nullable(); // redundante al assignee del stage
                $t->timestamps();

                $t->index(['ticket_id','stage_id']);
            });
        }

        // Items del checklist
        if (!Schema::hasTable('ticket_checklist_items')) {
            Schema::create('ticket_checklist_items', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('checklist_id');
                $t->string('text', 255);
                $t->boolean('is_done')->default(false);
                $t->timestamp('done_at')->nullable();
                $t->unsignedBigInteger('done_by')->nullable();
                $t->timestamps();

                $t->index(['checklist_id','is_done']);
            });
        }
    }

    public function down(): void
    {
        // No borramos datos críticos
        Schema::dropIfExists('ticket_checklist_items');
        Schema::dropIfExists('ticket_checklists');

        Schema::table('ticket_stages', function (Blueprint $t) {
            foreach (['assignee_id','ai_prompt','ai_instructions','requires_evidence','started_at','finished_at'] as $col) {
                if (Schema::hasColumn('ticket_stages', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
