<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('vehicles', function (Blueprint $table) {
      // Si ya existen image_left / image_right, comenta estas 2 líneas
      if (!Schema::hasColumn('vehicles','image_left'))  $table->string('image_left')->nullable()->after('notes');
      if (!Schema::hasColumn('vehicles','image_right')) $table->string('image_right')->nullable()->after('image_left');

      // Vinculación con AgendaEvent
      if (!Schema::hasColumn('vehicles','agenda_verification_id')) {
        $table->unsignedBigInteger('agenda_verification_id')->nullable()->after('image_right');
        $table->unsignedBigInteger('agenda_service_id')->nullable()->after('agenda_verification_id');
        $table->unsignedBigInteger('agenda_tenencia_id')->nullable()->after('agenda_service_id');
        $table->unsignedBigInteger('agenda_circulation_id')->nullable()->after('agenda_tenencia_id');
        $table->unsignedBigInteger('agenda_insurance_id')->nullable()->after('agenda_circulation_id');
      }
    });
  }

  public function down(): void {
    Schema::table('vehicles', function (Blueprint $table) {
      if (Schema::hasColumn('vehicles','agenda_verification_id')) {
        $table->dropColumn([
          'agenda_verification_id','agenda_service_id','agenda_tenencia_id','agenda_circulation_id','agenda_insurance_id'
        ]);
      }
      // OJO: si ya existían estas columnas antes, no las borres en down
      // $table->dropColumn(['image_left','image_right']);
    });
  }
};
