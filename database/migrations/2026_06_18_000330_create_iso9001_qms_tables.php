<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->default('Quality');
            $table->unsignedTinyInteger('likelihood');
            $table->unsignedTinyInteger('severity');
            $table->unsignedSmallInteger('risk_score')->index();
            $table->unsignedTinyInteger('residual_likelihood')->nullable();
            $table->unsignedTinyInteger('residual_severity')->nullable();
            $table->unsignedSmallInteger('residual_score')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('treatment_plan')->nullable();
            $table->string('status')->default('Identified')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('non_conformances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->string('source');
            $table->text('description');
            $table->string('iso_clause')->nullable();
            $table->string('severity')->default('Minor')->index();
            $table->string('status')->default('Open')->index();
            $table->date('detected_at');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('root_cause')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'reference']);
        });

        Schema::create('corrective_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('non_conformance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('risk_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('effectiveness_verified_at')->nullable();
            $table->string('status')->default('Open')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corrective_actions');
        Schema::dropIfExists('non_conformances');
        Schema::dropIfExists('risks');
    }
};
