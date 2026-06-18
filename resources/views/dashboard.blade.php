<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ISO-Forge</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-950 antialiased">
    <div class="flex min-h-screen flex-col lg:flex-row">
        <aside class="border-b border-zinc-200 bg-white lg:min-h-screen lg:w-64 lg:border-b-0 lg:border-r">
            <div class="flex items-center gap-3 px-5 py-5">
                <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-600 text-sm font-semibold text-white">IF</div>
                <div>
                    <div class="text-base font-semibold">ISO-Forge</div>
                    <div class="text-xs font-medium text-zinc-500">GRC Framework</div>
                </div>
            </div>
            <nav class="flex gap-1 overflow-x-auto px-3 pb-4 text-sm font-medium lg:block lg:space-y-1 lg:overflow-visible">
                <a class="block rounded-lg bg-zinc-950 px-3 py-2 text-white" href="#">Dashboard</a>
                <a class="block rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950" href="#documents">Documents</a>
                <a class="block rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950" href="#risks">Risks</a>
                <a class="block rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950" href="#capa">CAPA</a>
                <a class="block rounded-lg px-3 py-2 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950" href="#ledger">Ledger</a>
            </nav>
        </aside>

        <main class="flex-1">
            <header class="border-b border-zinc-200 bg-white">
                <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500">{{ now()->format('M j, Y') }}</p>
                        <h1 class="mt-1 text-2xl font-semibold tracking-normal">Compliance Operations</h1>
                    </div>
                    @if ($tenant)
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="rounded-lg border border-zinc-200 bg-white px-3 py-2 font-medium">{{ $tenant->name }}</span>
                            <span class="rounded-lg bg-emerald-100 px-3 py-2 font-medium text-emerald-800">{{ $tenant->industry }}</span>
                        </div>
                    @endif
                </div>
            </header>

            <div class="mx-auto max-w-7xl space-y-6 px-5 py-6">
                @if (! $tenant)
                    <section class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-900">
                        No tenant data is available yet.
                    </section>
                @else
                    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-lg border border-zinc-200 bg-white p-4">
                            <div class="text-sm font-medium text-zinc-500">Controlled Documents</div>
                            <div class="mt-3 text-3xl font-semibold">{{ $metrics['documents'] }}</div>
                        </div>
                        <div class="rounded-lg border border-zinc-200 bg-white p-4">
                            <div class="text-sm font-medium text-zinc-500">Pending Approvals</div>
                            <div class="mt-3 text-3xl font-semibold text-amber-700">{{ $metrics['pending_approvals'] }}</div>
                        </div>
                        <div class="rounded-lg border border-zinc-200 bg-white p-4">
                            <div class="text-sm font-medium text-zinc-500">High Risks</div>
                            <div class="mt-3 text-3xl font-semibold text-red-700">{{ $metrics['high_risks'] }}</div>
                        </div>
                        <div class="rounded-lg border border-zinc-200 bg-white p-4">
                            <div class="text-sm font-medium text-zinc-500">Open CAPA</div>
                            <div class="mt-3 text-3xl font-semibold text-sky-700">{{ $metrics['open_capas'] }}</div>
                        </div>
                    </section>

                    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
                        <div id="documents" class="rounded-lg border border-zinc-200 bg-white">
                            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3">
                                <h2 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Document Control</h2>
                                <span class="text-sm font-medium text-zinc-500">{{ $documents->count() }} records</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                    <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                        <tr>
                                            <th class="px-4 py-3">Number</th>
                                            <th class="px-4 py-3">Title</th>
                                            <th class="px-4 py-3">Owner</th>
                                            <th class="px-4 py-3">Version</th>
                                            <th class="px-4 py-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100">
                                        @foreach ($documents as $document)
                                            <tr>
                                                <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $document->document_number }}</td>
                                                <td class="min-w-64 px-4 py-3">{{ $document->title }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">{{ $document->owner?->name }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">{{ $document->currentVersion?->version_number ?? 'Draft' }}</td>
                                                <td class="whitespace-nowrap px-4 py-3">
                                                    <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $document->status === 'Approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $document->status }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="rounded-lg border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <h2 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Approval Queue</h2>
                            </div>
                            <div class="divide-y divide-zinc-100">
                                @forelse ($pendingApprovals as $approval)
                                    <div class="p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="font-medium">{{ $approval->documentVersion->document->document_number }}</div>
                                                <div class="mt-1 text-sm text-zinc-600">{{ $approval->documentVersion->document->title }}</div>
                                            </div>
                                            <span class="rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">{{ $approval->status }}</span>
                                        </div>
                                        <div class="mt-3 text-sm text-zinc-500">Approver: {{ $approval->approver->name }}</div>
                                    </div>
                                @empty
                                    <div class="p-4 text-sm text-zinc-500">No pending approvals.</div>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-6 xl:grid-cols-2">
                        <div id="risks" class="rounded-lg border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <h2 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Risk Register</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                    <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                        <tr>
                                            <th class="px-4 py-3">Risk</th>
                                            <th class="px-4 py-3">Owner</th>
                                            <th class="px-4 py-3">Score</th>
                                            <th class="px-4 py-3">Residual</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100">
                                        @foreach ($risks as $risk)
                                            <tr>
                                                <td class="min-w-72 px-4 py-3">
                                                    <div class="font-medium">{{ $risk->title }}</div>
                                                    <div class="mt-1 text-xs font-medium text-zinc-500">{{ $risk->status }}</div>
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">{{ $risk->owner?->name }}</td>
                                                <td class="px-4 py-3">
                                                    <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $risk->risk_score >= 15 ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">{{ $risk->risk_score }}</span>
                                                </td>
                                                <td class="px-4 py-3 text-zinc-600">{{ $risk->residual_score ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="capa" class="rounded-lg border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <h2 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">CAPA Workflow</h2>
                            </div>
                            <div class="divide-y divide-zinc-100">
                                @foreach ($correctiveActions as $action)
                                    <div class="p-4">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <div class="font-medium">{{ $action->title }}</div>
                                                <div class="mt-1 text-sm text-zinc-600">{{ $action->nonConformance?->reference }} · {{ $action->nonConformance?->iso_clause }}</div>
                                            </div>
                                            <span class="rounded-lg bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800">{{ $action->status }}</span>
                                        </div>
                                        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                                            <div>
                                                <div class="font-medium text-zinc-500">Assigned</div>
                                                <div class="mt-1">{{ $action->assignee?->name }}</div>
                                            </div>
                                            <div>
                                                <div class="font-medium text-zinc-500">Verifier</div>
                                                <div class="mt-1">{{ $action->verifier?->name }}</div>
                                            </div>
                                            <div>
                                                <div class="font-medium text-zinc-500">Due</div>
                                                <div class="mt-1">{{ $action->due_date?->format('M j') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
                        <div class="rounded-lg border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <h2 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Workflow Tasks</h2>
                            </div>
                            <div class="divide-y divide-zinc-100">
                                @foreach ($workflowTasks as $task)
                                    <div class="p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="font-medium">{{ $task->title }}</div>
                                                <div class="mt-1 text-sm text-zinc-600">{{ $task->assignee?->name }} · {{ $task->state }}</div>
                                            </div>
                                            <span class="rounded-lg px-2.5 py-1 text-xs font-semibold {{ $task->status === 'Open' ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700' }}">{{ $task->status }}</span>
                                        </div>
                                        <div class="mt-3 text-sm text-zinc-500">Due {{ $task->due_at?->format('M j, H:i') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div id="ledger" class="rounded-lg border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <h2 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Immutable Audit Ledger</h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                    <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                        <tr>
                                            <th class="px-4 py-3">Event</th>
                                            <th class="px-4 py-3">Actor</th>
                                            <th class="px-4 py-3">Hash</th>
                                            <th class="px-4 py-3">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100">
                                        @foreach ($auditLogs as $log)
                                            <tr>
                                                <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $log->event }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">{{ $log->user?->name }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-zinc-600">{{ \Illuminate\Support\Str::limit($log->entry_hash, 18, '') }}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">{{ $log->occurred_at?->diffForHumans() }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                @endif
            </div>
        </main>
    </div>
</body>
</html>
