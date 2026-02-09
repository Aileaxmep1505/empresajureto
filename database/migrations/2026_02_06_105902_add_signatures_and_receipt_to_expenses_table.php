<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('expenses', function (Blueprint $table) {

      // Elegimos una columna "segura" para after() si existe
      $after = null;
      foreach (['attachment_size','attachment_mime','attachment_name','attachment_path','tags','status','payment_method','currency','amount','description'] as $col) {
        if (Schema::hasColumn('expenses', $col)) { $after = $col; break; }
      }

      // helper local para agregar con after seguro
      $addAfter = function($colName, $callback) use ($table, $after) {
        if ($after) {
          // Blueprint soporta after() en columnas (MySQL)
          $callback()->after($after);
        } else {
          $callback();
        }
      };

      if (!Schema::hasColumn('expenses','manager_signature_path')) {
        $addAfter('manager_signature_path', fn() => $table->string('manager_signature_path')->nullable());
      }

      if (!Schema::hasColumn('expenses','counterparty_signature_path')) {
        $addAfter('counterparty_signature_path', fn() => $table->string('counterparty_signature_path')->nullable());
      }

      if (!Schema::hasColumn('expenses','nip_approved_by')) {
        $addAfter('nip_approved_by', fn() => $table->unsignedBigInteger('nip_approved_by')->nullable());
      }

      if (!Schema::hasColumn('expenses','nip_approved_at')) {
        $addAfter('nip_approved_at', fn() => $table->timestamp('nip_approved_at')->nullable());
      }

      if (!Schema::hasColumn('expenses','pdf_receipt_path')) {
        $addAfter('pdf_receipt_path', fn() => $table->string('pdf_receipt_path')->nullable());
      }

      if (!Schema::hasColumn('expenses','performed_at')) {
        $addAfter('performed_at', fn() => $table->timestamp('performed_at')->nullable());
      }

      // FK (solo si users existe)
      if (Schema::hasTable('users') && Schema::hasColumn('expenses','nip_approved_by')) {
        // evitar duplicar fk si ya existe (try/catch)
        try {
          $table->foreign('nip_approved_by')->references('id')->on('users')->nullOnDelete();
        } catch (\Throwable $e) {}
      }
    });
  }

  public function down(): void
  {
    Schema::table('expenses', function (Blueprint $table) {
      if (Schema::hasColumn('expenses','nip_approved_by')) {
        try { $table->dropForeign(['nip_approved_by']); } catch (\Throwable $e) {}
      }

      foreach ([
        'performed_at',
        'pdf_receipt_path',
        'nip_approved_at',
        'nip_approved_by',
        'counterparty_signature_path',
        'manager_signature_path',
      ] as $col) {
        if (Schema::hasColumn('expenses', $col)) {
          $table->dropColumn($col);
        }
      }
    });
  }
};
