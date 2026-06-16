<?php

use App\Models\ProjectChecklistItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_checklist_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('source_document_id')
                ->nullable()
                ->constrained('project_documents')
                ->nullOnDelete();

            $table->string('source_item_id')->nullable();

            $table->text('requirement');
            $table->longText('description')->nullable();
            $table->longText('compliance_criteria')->nullable();

            $table->string('format')->nullable()->default('No aplica');
            $table->string('category')->nullable()->default('Legal-Administrativo');
            $table->string('applicability')->nullable()->default('Único');
            $table->boolean('mandatory')->default(true);

            $table->string('compliance_status', 40)
                ->default(ProjectChecklistItem::COMPLIANCE_SIN_REVISAR);

            $table->string('review_status', 40)
                ->default(ProjectChecklistItem::STATUS_PENDIENTE);

            $table->string('priority', 40)
                ->default(ProjectChecklistItem::PRIORITY_MEDIA);

            $table->date('due_date')->nullable();

            $table->foreignId('responsible_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('reviewer_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('source_name')->nullable();
            $table->unsignedInteger('source_page')->nullable();
            $table->longText('source_quote')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'position']);
            $table->index(['project_id', 'compliance_status']);
            $table->index(['project_id', 'review_status']);
            $table->index(['project_id', 'priority']);
            $table->index(['responsible_user_id']);
            $table->index(['reviewer_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_checklist_items');
    }
};