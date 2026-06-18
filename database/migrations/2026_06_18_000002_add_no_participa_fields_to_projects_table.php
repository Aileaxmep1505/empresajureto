<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('no_participa_reason')->nullable()->after('workflow_status');
            $table->timestamp('no_participa_confirmed_at')->nullable()->after('no_participa_reason');
            $table->foreignId('no_participa_confirmed_by')->nullable()->after('no_participa_confirmed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('no_participa_confirmed_by');
            $table->dropColumn([
                'no_participa_reason',
                'no_participa_confirmed_at',
            ]);
        });
    }
};