<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('documents', function (Blueprint $table) {
      $table->string('ficticio_file_path')->nullable()->after('file_path');
      $table->string('ficticio_filename')->nullable()->after('ficticio_file_path');
      $table->string('ficticio_mime_type')->nullable()->after('ficticio_filename');
      $table->unsignedBigInteger('ficticio_uploaded_by')->nullable()->after('ficticio_mime_type');

      $table->index('ficticio_uploaded_by');
    });
  }

  public function down(): void
  {
    Schema::table('documents', function (Blueprint $table) {
      $table->dropIndex(['ficticio_uploaded_by']);
      $table->dropColumn([
        'ficticio_file_path',
        'ficticio_filename',
        'ficticio_mime_type',
        'ficticio_uploaded_by',
      ]);
    });
  }
};
