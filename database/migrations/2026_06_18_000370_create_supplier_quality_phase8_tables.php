<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('supplier_code');
            $table->string('category');
            $table->string('contact_email')->nullable();
            $table->string('approval_status')->default('Pending')->index();
            $table->string('risk_level')->default('Medium')->index();
            $table->date('approved_until')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('risk_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'supplier_code']);
        });

        Schema::create('supplier_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('evaluation_date');
            $table->unsignedTinyInteger('score');
            $table->string('result')->default('Conditional')->index();
            $table->date('next_review_date')->nullable();
            $table->foreignId('evidence_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'result']);
        });

        Schema::create('supplier_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('certificate_type');
            $table->string('certificate_number');
            $table->date('issued_at')->nullable();
            $table->date('expires_at');
            $table->string('status')->default('Current')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'expires_at']);
        });

        Schema::create('equipment_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('asset_tag');
            $table->string('name');
            $table->string('location');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('calibration_interval_days')->default(365);
            $table->boolean('critical_to_food_safety')->default(false)->index();
            $table->date('last_calibrated_at')->nullable();
            $table->date('next_calibration_due_at')->nullable();
            $table->string('status')->default('Active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'asset_tag']);
            $table->index(['tenant_id', 'next_calibration_due_at']);
        });

        Schema::create('calibration_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('evidence_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('corrective_action_id')->nullable()->constrained()->nullOnDelete();
            $table->date('performed_at');
            $table->date('due_at');
            $table->string('result')->default('Pass')->index();
            $table->string('certificate_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'due_at']);
            $table->index(['tenant_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calibration_records');
        Schema::dropIfExists('equipment_assets');
        Schema::dropIfExists('supplier_certificates');
        Schema::dropIfExists('supplier_evaluations');
        Schema::dropIfExists('suppliers');
    }
};
