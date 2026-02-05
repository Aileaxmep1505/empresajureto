<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('vehicle_events', function (Blueprint $table) {
      $table->id();
      $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();

      // verification|service|tenencia|circulation_card|repair|insurance|other
      $table->string('type', 40);
      $table->date('event_date')->nullable();

      $table->string('title')->nullable();
      $table->text('description')->nullable();

      // Si fue un gasto asociado, lo ligas aquí
      $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();

      $table->timestamps();

      $table->index(['vehicle_id', 'type']);
      $table->index(['type', 'event_date']);
    });
  }

  public function down(): void {
    Schema::dropIfExists('vehicle_events');
  }
};
