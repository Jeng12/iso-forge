<?php

use App\Models\AuditLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('iso-forge:verify-audit-chain', function (): int {
    $result = AuditLog::verifyChain();

    if ($result['valid']) {
        $this->info("Audit chain valid. Checked {$result['checked']} entries; legacy entries: {$result['legacy']}.");

        return 0;
    }

    $this->error("Audit chain invalid. Checked {$result['checked']} entries; legacy entries: {$result['legacy']}.");

    foreach ($result['errors'] as $error) {
        $this->line("- {$error}");
    }

    return 1;
})->purpose('Verify ISO-Forge audit log hash-chain integrity');
