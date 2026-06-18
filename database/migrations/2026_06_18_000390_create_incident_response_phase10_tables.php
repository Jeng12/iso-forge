<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->string('title');
            $table->string('incident_type');
            $table->string('severity')->default('Minor')->index();
            $table->string('status')->default('Open')->index();
            $table->foreignId('reported_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_control_type')->nullable();
            $table->unsignedBigInteger('source_control_id')->nullable();
            $table->timestamp('detected_at');
            $table->text('description');
            $table->text('immediate_containment')->nullable();
            $table->foreignId('corrective_action_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'reference']);
            $table->index(['source_control_type', 'source_control_id']);
        });

        Schema::create('incident_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incident_report_id')->constrained()->cascadeOnDelete();
            $table->string('action_type');
            $table->text('description');
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('Open')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('emergency_response_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('scenario');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('related_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->unsignedSmallInteger('review_frequency_days')->default(365);
            $table->date('last_reviewed_at')->nullable();
            $table->date('next_review_due_at')->nullable();
            $table->json('response_steps')->nullable();
            $table->string('status')->default('Active')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'next_review_due_at']);
        });

        Schema::create('emergency_drills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('emergency_response_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facilitator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('scheduled_at')->nullable();
            $table->date('completed_at');
            $table->string('result')->default('Effective')->index();
            $table->unsignedSmallInteger('participants_count')->default(0);
            $table->unsignedTinyInteger('effectiveness_score')->nullable();
            $table->text('scenario_notes')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('corrective_action_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'completed_at']);
            $table->index(['tenant_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_drills');
        Schema::dropIfExists('emergency_response_plans');
        Schema::dropIfExists('incident_actions');
        Schema::dropIfExists('incident_reports');
    }
};
