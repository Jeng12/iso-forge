<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ISO-Forge Workspace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-950 antialiased">
    <div id="phase3-app" class="min-h-screen">
        <section id="login-screen" class="flex min-h-screen items-center justify-center px-4 py-8">
            <form id="login-form" class="w-full max-w-sm rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="mb-5">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-600 text-sm font-semibold text-white">IF</div>
                        <div>
                            <h1 class="text-lg font-semibold">ISO-Forge</h1>
                            <p class="text-sm text-zinc-500">Compliance workspace</p>
                        </div>
                    </div>
                </div>

                <label class="mb-3 block">
                    <span class="mb-1 block text-sm font-medium text-zinc-700">Email</span>
                    <input id="login-email" name="email" type="email" value="jojo@iso-forge.test" autocomplete="email" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
                </label>

                <label class="mb-4 block">
                    <span class="mb-1 block text-sm font-medium text-zinc-700">Password</span>
                    <input id="login-password" name="password" type="password" value="password" autocomplete="current-password" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100">
                </label>

                <button type="submit" class="w-full rounded-lg bg-zinc-950 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Sign in</button>
                <p id="login-error" class="mt-3 hidden rounded-lg bg-red-50 px-3 py-2 text-sm font-medium text-red-700"></p>
            </form>
        </section>

        <section id="workspace" class="hidden min-h-screen lg:flex">
            <aside class="border-b border-zinc-200 bg-white lg:min-h-screen lg:w-64 lg:border-b-0 lg:border-r">
                <div class="flex items-center gap-3 px-5 py-5">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-600 text-sm font-semibold text-white">IF</div>
                    <div>
                        <div class="text-base font-semibold">ISO-Forge</div>
                        <div id="tenant-label" class="text-xs font-medium text-zinc-500">Workspace</div>
                    </div>
                </div>
                <nav class="flex gap-1 overflow-x-auto px-3 pb-4 text-sm font-medium lg:block lg:space-y-1 lg:overflow-visible">
                    <button data-tab="overview" class="tab-button block rounded-lg bg-zinc-950 px-3 py-2 text-left text-white lg:w-full">Overview</button>
                    <button data-tab="documents" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">Documents</button>
                    <button data-tab="risks" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">Risks</button>
                    <button data-tab="capa" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">CAPA</button>
                    <button data-tab="audit" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">Audit</button>
                </nav>
            </aside>

            <main class="flex-1">
                <header class="border-b border-zinc-200 bg-white">
                    <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p id="user-label" class="text-sm font-medium text-zinc-500">Signed in</p>
                            <h2 class="text-2xl font-semibold tracking-normal">Compliance Operations</h2>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button id="refresh-button" type="button" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-50">Refresh</button>
                            <button id="logout-button" type="button" class="rounded-lg bg-zinc-950 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Logout</button>
                        </div>
                    </div>
                </header>

                <div class="mx-auto max-w-7xl space-y-6 px-5 py-6">
                    <div id="status-line" class="hidden rounded-lg border px-4 py-3 text-sm font-medium"></div>

                    <section data-panel="overview" class="panel space-y-6">
                        <div id="metric-grid" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5"></div>
                        <div class="grid gap-6 xl:grid-cols-2">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Approval Queue</h3>
                                </div>
                                <div id="overview-approvals" class="divide-y divide-zinc-100"></div>
                            </section>
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Workflow Tasks</h3>
                                </div>
                                <div id="overview-tasks" class="divide-y divide-zinc-100"></div>
                            </section>
                        </div>
                    </section>

                    <section data-panel="documents" class="panel hidden space-y-6">
                        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Controlled Documents</h3>
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
                                        <tbody id="documents-body" class="divide-y divide-zinc-100"></tbody>
                                    </table>
                                </div>
                            </section>

                            <form id="document-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Document</h3>
                                <div class="space-y-3">
                                    <input name="document_number" required placeholder="Document number" class="form-input">
                                    <input name="title" required placeholder="Title" class="form-input">
                                    <input name="category" required placeholder="Category" class="form-input">
                                    <input name="version_number" required placeholder="Version" class="form-input">
                                    <input name="file_path" required placeholder="File path" class="form-input">
                                    <select name="owner_id" required class="form-input user-select"></select>
                                    <select name="approver_id" class="form-input user-select"></select>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>

                        <section class="rounded-lg border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Document Approvals</h3>
                            </div>
                            <div id="approval-list" class="divide-y divide-zinc-100"></div>
                        </section>
                    </section>

                    <section data-panel="risks" class="panel hidden space-y-6">
                        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Risk Register</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                            <tr>
                                                <th class="px-4 py-3">Risk</th>
                                                <th class="px-4 py-3">Owner</th>
                                                <th class="px-4 py-3">Score</th>
                                                <th class="px-4 py-3">Residual</th>
                                                <th class="px-4 py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="risks-body" class="divide-y divide-zinc-100"></tbody>
                                    </table>
                                </div>
                            </section>

                            <form id="risk-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Risk</h3>
                                <div class="space-y-3">
                                    <input name="title" required placeholder="Risk title" class="form-input">
                                    <input name="category" placeholder="Category" class="form-input">
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="likelihood" required type="number" min="1" max="5" value="3" class="form-input">
                                        <input name="severity" required type="number" min="1" max="5" value="4" class="form-input">
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="residual_likelihood" type="number" min="1" max="5" placeholder="Residual L" class="form-input">
                                        <input name="residual_severity" type="number" min="1" max="5" placeholder="Residual S" class="form-input">
                                    </div>
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <textarea name="treatment_plan" placeholder="Treatment plan" class="form-input min-h-24"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section data-panel="capa" class="panel hidden space-y-6">
                        <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Corrective Actions</h3>
                                </div>
                                <div id="capa-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <form id="capa-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New CAPA</h3>
                                <div class="space-y-3">
                                    <input name="reference" required placeholder="NC reference" class="form-input">
                                    <input name="source" required placeholder="Source" class="form-input">
                                    <textarea name="description" required placeholder="Nonconformance description" class="form-input min-h-20"></textarea>
                                    <input name="iso_clause" placeholder="ISO clause" class="form-input">
                                    <select name="severity" class="form-input">
                                        <option>Minor</option>
                                        <option selected>Major</option>
                                        <option>Critical</option>
                                    </select>
                                    <input name="title" required placeholder="CAPA title" class="form-input">
                                    <textarea name="action_description" required placeholder="Action description" class="form-input min-h-20"></textarea>
                                    <select name="assigned_to_id" class="form-input user-select"></select>
                                    <select name="verified_by_id" class="form-input user-select"></select>
                                    <input name="due_date" type="date" class="form-input">
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>

                        <section class="rounded-lg border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Workflow Tasks</h3>
                            </div>
                            <div id="task-list" class="divide-y divide-zinc-100"></div>
                        </section>
                    </section>

                    <section data-panel="audit" class="panel hidden">
                        <section class="rounded-lg border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Immutable Audit Ledger</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                    <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                        <tr>
                                            <th class="px-4 py-3">Event</th>
                                            <th class="px-4 py-3">Actor</th>
                                            <th class="px-4 py-3">Entry Hash</th>
                                            <th class="px-4 py-3">Previous Hash</th>
                                        </tr>
                                    </thead>
                                    <tbody id="audit-body" class="divide-y divide-zinc-100"></tbody>
                                </table>
                            </div>
                        </section>
                    </section>
                </div>
            </main>
        </section>
    </div>
</body>
</html>
