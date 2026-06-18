<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('title');
            $table->string('iso_clause')->nullable();
            $table->string('delivery_method')->default('Classroom');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('refresher_interval_days')->nullable();
            $table->string('status')->default('Active')->index();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('competency_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_program_id')->constrained()->cascadeOnDelete();
            $table->string('competency_area');
            $table->string('required_level')->default('Qualified');
            $table->string('assessment_method')->default('Supervisor verification');
            $table->unsignedSmallInteger('due_within_days')->default(30);
            $table->boolean('is_mandatory')->default(true)->index();
            $table->timestamps();

            $table->unique(['role_id', 'training_program_id', 'competency_area']);
        });

        Schema::create('training_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('required_for_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->date('due_date');
            $table->string('status')->default('Assigned')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'due_date']);
        });

        Schema::create('training_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('training_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('evidence_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('corrective_action_id')->nullable()->constrained()->nullOnDelete();
            $table->date('completed_at');
            $table->decimal('score', 5, 2)->nullable();
            $table->string('result')->default('Pass')->index();
            $table->string('competency_status')->default('Competent')->index();
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'completed_at']);
            $table->index(['tenant_id', 'competency_status']);
        });

        Schema::create('awareness_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('acknowledged_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at');
            $table->string('status')->default('Acknowledged')->index();
            $table->text('statement')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'user_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('awareness_acknowledgements');
        Schema::dropIfExists('training_records');
        Schema::dropIfExists('training_assignments');
        Schema::dropIfExists('competency_requirements');
        Schema::dropIfExists('training_programs');
    }
};
