<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_activities', function (Blueprint $table) {
            // ✅ Para que sea GLOBAL (todo el sistema)
            // Ruta (nombre de ruta si existe) + path real + método HTTP
            if (!Schema::hasColumn('user_activities', 'route')) {
                $table->string('route', 255)->nullable()->after('action')->index();
            }
            if (!Schema::hasColumn('user_activities', 'path')) {
                $table->string('path', 500)->nullable()->after('route')->index();
            }
            if (!Schema::hasColumn('user_activities', 'method')) {
                $table->string('method', 10)->nullable()->after('path')->index();
            }

            // Status code + duración del request
            if (!Schema::hasColumn('user_activities', 'status_code')) {
                $table->unsignedSmallInteger('status_code')->nullable()->after('method')->index();
            }
            if (!Schema::hasColumn('user_activities', 'duration_ms')) {
                $table->unsignedInteger('duration_ms')->nullable()->after('status_code');
            }

            // Referer / De dónde venía
            if (!Schema::hasColumn('user_activities', 'referer')) {
                $table->string('referer', 500)->nullable()->after('duration_ms');
            }

            // IDs para correlación / auditoría
            if (!Schema::hasColumn('user_activities', 'session_id')) {
                $table->string('session_id', 120)->nullable()->after('user_agent')->index();
            }
            if (!Schema::hasColumn('user_activities', 'request_id')) {
                $table->string('request_id', 80)->nullable()->after('session_id')->index();
            }

            // ✅ Target genérico (para TODO: tickets, ventas, users, docs, etc.)
            // Esto NO rompe lo que ya tienes (company_id/document_id se quedan).
            if (!Schema::hasColumn('user_activities', 'subject_type')) {
                $table->string('subject_type', 150)->nullable()->after('document_id')->index();
            }
            if (!Schema::hasColumn('user_activities', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type')->index();
            }

            // ✅ Hash encadenado (anti-manipulación)
            if (!Schema::hasColumn('user_activities', 'previous_hash')) {
                $table->char('previous_hash', 64)->nullable()->after('meta');
            }
            if (!Schema::hasColumn('user_activities', 'current_hash')) {
                $table->char('current_hash', 64)->nullable()->after('previous_hash')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_activities', function (Blueprint $table) {
            // Ojo: quitamos índices antes de columnas cuando aplica
            if (Schema::hasColumn('user_activities', 'current_hash')) {
                $table->dropIndex(['current_hash']);
                $table->dropColumn('current_hash');
            }
            if (Schema::hasColumn('user_activities', 'previous_hash')) {
                $table->dropColumn('previous_hash');
            }

            if (Schema::hasColumn('user_activities', 'subject_id')) {
                $table->dropIndex(['subject_id']);
                $table->dropColumn('subject_id');
            }
            if (Schema::hasColumn('user_activities', 'subject_type')) {
                $table->dropIndex(['subject_type']);
                $table->dropColumn('subject_type');
            }

            if (Schema::hasColumn('user_activities', 'request_id')) {
                $table->dropIndex(['request_id']);
                $table->dropColumn('request_id');
            }
            if (Schema::hasColumn('user_activities', 'session_id')) {
                $table->dropIndex(['session_id']);
                $table->dropColumn('session_id');
            }

            if (Schema::hasColumn('user_activities', 'referer')) {
                $table->dropColumn('referer');
            }
            if (Schema::hasColumn('user_activities', 'duration_ms')) {
                $table->dropColumn('duration_ms');
            }
            if (Schema::hasColumn('user_activities', 'status_code')) {
                $table->dropIndex(['status_code']);
                $table->dropColumn('status_code');
            }

            if (Schema::hasColumn('user_activities', 'method')) {
                $table->dropIndex(['method']);
                $table->dropColumn('method');
            }
            if (Schema::hasColumn('user_activities', 'path')) {
                $table->dropIndex(['path']);
                $table->dropColumn('path');
            }
            if (Schema::hasColumn('user_activities', 'route')) {
                $table->dropIndex(['route']);
                $table->dropColumn('route');
            }
        });
    }
};