<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('iso_clause')->default('ISO 9001:2015 6.2');
            $table->decimal('baseline_value', 12, 2)->nullable();
            $table->decimal('target_value', 12, 2);
            $table->decimal('current_value', 12, 2)->nullable();
            $table->string('unit')->default('%');
            $table->string('measurement_method');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('Active')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('audit_type')->default('Internal');
            $table->string('iso_standard')->default('ISO 9001:2015');
            $table->text('scope');
            $table->foreignId('lead_auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('scheduled_date');
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('Planned')->index();
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'scheduled_date']);
        });

        Schema::create('audit_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('non_conformance_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference');
            $table->string('iso_clause');
            $table->string('finding_type')->default('Observation')->index();
            $table->string('severity')->default('Minor')->index();
            $table->text('description');
            $table->text('evidence')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('Open')->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'reference']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('management_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('review_date');
            $table->foreignId('chair_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('inputs')->nullable();
            $table->json('decisions')->nullable();
            $table->json('actions')->nullable();
            $table->string('status')->default('Planned')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'review_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_reviews');
        Schema::dropIfExists('audit_findings');
        Schema::dropIfExists('audits');
        Schema::dropIfExists('quality_objectives');
    }
};
