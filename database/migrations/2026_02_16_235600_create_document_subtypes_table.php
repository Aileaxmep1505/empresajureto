<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('document_subtypes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('document_sections')->onDelete('cascade');
            $table->string('key')->nullable(); // opcional: 32d_sat, opinion_imss
            $table->string('name'); // 32D SAT
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('document_subtypes');
    }
};
