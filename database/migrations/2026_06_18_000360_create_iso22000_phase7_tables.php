<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haccp_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('product');
            $table->text('scope');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('effective_date')->nullable();
            $table->string('status')->default('Draft')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('process_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('haccp_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['haccp_plan_id', 'sequence']);
        });

        Schema::create('hazard_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('process_step_id')->constrained()->cascadeOnDelete();
            $table->string('hazard_type');
            $table->text('hazard_description');
            $table->unsignedTinyInteger('likelihood');
            $table->unsignedTinyInteger('severity');
            $table->unsignedSmallInteger('risk_score')->index();
            $table->text('control_measure');
            $table->string('control_type')->default('PRP')->index();
            $table->string('status')->default('Assessed')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'control_type']);
        });

        Schema::create('critical_control_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hazard_analysis_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('critical_limit');
            $table->string('monitoring_frequency');
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('corrective_action_procedure');
            $table->string('status')->default('Active')->index();
            $table->timestamps();
        });

        Schema::create('operational_prerequisite_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hazard_analysis_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('control_measure');
            $table->string('monitoring_frequency');
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
        });

        Schema::create('prerequisite_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verification_frequency');
            $table->string('status')->default('Active')->index();
            $table->timestamps();
        });

        Schema::create('monitoring_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('monitorable_type');
            $table->unsignedBigInteger('monitorable_id');
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('measured_value', 12, 2)->nullable();
            $table->string('unit')->nullable();
            $table->string('result')->default('Pass')->index();
            $table->boolean('is_deviation')->default(false)->index();
            $table->timestamp('observed_at');
            $table->text('notes')->nullable();
            $table->foreignId('corrective_action_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['monitorable_type', 'monitorable_id']);
            $table->index(['tenant_id', 'observed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_records');
        Schema::dropIfExists('prerequisite_programs');
        Schema::dropIfExists('operational_prerequisite_programs');
        Schema::dropIfExists('critical_control_points');
        Schema::dropIfExists('hazard_analyses');
        Schema::dropIfExists('process_steps');
        Schema::dropIfExists('haccp_plans');
    }
};
