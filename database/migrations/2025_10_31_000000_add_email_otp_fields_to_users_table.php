<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hash del código, expiración, cuándo se envió, y conteo de intentos
            $table->string('email_verification_code_hash')->nullable()->after('remember_token');
            $table->timestamp('email_verification_expires_at')->nullable()->after('email_verification_code_hash');
            $table->timestamp('email_verification_code_sent_at')->nullable()->after('email_verification_expires_at');
            $table->unsignedSmallInteger('email_verification_attempts')->default(0)->after('email_verification_code_sent_at');
            // Asegúrate de que ya exista email_verified_at (propio de Laravel). Si no, descomenta:
            // $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verification_code_hash',
                'email_verification_expires_at',
                'email_verification_code_sent_at',
                'email_verification_attempts',
            ]);
        });
    }
};
