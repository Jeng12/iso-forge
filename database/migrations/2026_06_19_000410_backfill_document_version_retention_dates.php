<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_versions')
            ->whereNull('retention_until')
            ->orderBy('id')
            ->chunkById(100, function ($versions): void {
                foreach ($versions as $version) {
                    $basis = $version->effective_date
                        ?? $version->approval_date
                        ?? $version->review_date
                        ?? $version->created_at
                        ?? now();

                    DB::table('document_versions')
                        ->where('id', $version->id)
                        ->update([
                            'retention_until' => Carbon::parse($basis)->addYears(6)->toDateString(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('document_versions')->update(['retention_until' => null]);
    }
};
