<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_versions', function (Blueprint $table): void {
            $table->date('retention_until')->nullable()->after('effective_date');
            $table->timestamp('superseded_at')->nullable()->after('status');
            $table->foreignId('superseded_by_id')->nullable()->after('superseded_at')->constrained('document_versions')->nullOnDelete();
            $table->timestamp('superseded_reviewed_at')->nullable()->after('superseded_by_id');
            $table->foreignId('superseded_reviewed_by_id')->nullable()->after('superseded_reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('superseded_review_notes')->nullable()->after('superseded_reviewed_by_id');
            $table->timestamp('pruned_at')->nullable()->after('change_summary');
            $table->foreignId('pruned_by_id')->nullable()->after('pruned_at')->constrained('users')->nullOnDelete();
            $table->text('prune_reason')->nullable()->after('pruned_by_id');

            $table->index(['status', 'retention_until']);
        });
    }

    public function down(): void
    {
        Schema::table('document_versions', function (Blueprint $table): void {
            $table->dropIndex(['status', 'retention_until']);
            $table->dropConstrainedForeignId('pruned_by_id');
            $table->dropColumn(['pruned_at', 'prune_reason']);
            $table->dropConstrainedForeignId('superseded_reviewed_by_id');
            $table->dropColumn(['superseded_reviewed_at', 'superseded_review_notes']);
            $table->dropConstrainedForeignId('superseded_by_id');
            $table->dropColumn(['retention_until', 'superseded_at']);
        });
    }
};
