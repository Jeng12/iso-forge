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
                    <button data-tab="analytics" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">Analytics</button>
                    <button data-tab="review-packets" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">Packets</button>
                    <button data-tab="documents" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">Documents</button>
                    <button data-tab="risks" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">Risks</button>
                    <button data-tab="qms" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">QMS</button>
                    <button data-tab="fsms" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">FSMS</button>
                    <button data-tab="supplier-quality" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">Suppliers</button>
                    <button data-tab="training" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">Training</button>
                    <button data-tab="incident-response" class="tab-button block rounded-lg px-3 py-2 text-left text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 lg:w-full">Incidents</button>
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

                    <section data-panel="analytics" class="panel hidden space-y-6">
                        <div id="analytics-summary-grid" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"></div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Incident Trends</h3>
                                </div>
                                <div id="analytics-incident-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">CAPA Ageing</h3>
                                </div>
                                <div id="analytics-capa-list" class="divide-y divide-zinc-100"></div>
                            </section>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Training Competency</h3>
                                </div>
                                <div id="analytics-training-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Supplier Risk</h3>
                                </div>
                                <div id="analytics-supplier-list" class="divide-y divide-zinc-100"></div>
                            </section>
                        </div>
                    </section>

                    <section data-panel="review-packets" class="panel hidden space-y-6">
                        <div id="review-packet-summary-grid" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5"></div>

                        <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Management Review Packets</h3>
                                </div>
                                <div id="review-packets-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Packet Preview</h3>
                                </div>
                                <div id="review-packet-preview" class="divide-y divide-zinc-100"></div>
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
                                                <th class="px-4 py-3">File</th>
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
                                    <input name="file" type="file" class="form-input">
                                    <input name="file_path" placeholder="Existing file path" class="form-input">
                                    <input name="retention_until" type="date" class="form-input">
                                    <select name="owner_id" required class="form-input user-select"></select>
                                    <select name="approver_id" class="form-input user-select"></select>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <form id="document-edit-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">Edit Document</h3>
                                <div class="space-y-3">
                                    <select id="document-edit-select" name="document_id" required class="form-input"></select>
                                    <input name="title" required placeholder="Title" class="form-input">
                                    <input name="category" required placeholder="Category" class="form-input">
                                    <select name="owner_id" required class="form-input user-select"></select>
                                    <select name="status" class="form-input">
                                        <option>Draft</option>
                                        <option>Under Review</option>
                                        <option>Approved</option>
                                        <option>Retired</option>
                                    </select>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save</button>
                                </div>
                            </form>

                            <form id="document-version-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Version</h3>
                                <div class="space-y-3">
                                    <select id="document-version-document-select" name="document_id" required class="form-input"></select>
                                    <input name="version_number" required placeholder="Version" class="form-input">
                                    <input name="file" type="file" class="form-input">
                                    <input name="file_path" placeholder="Existing file path" class="form-input">
                                    <input name="retention_until" type="date" class="form-input">
                                    <textarea name="change_summary" placeholder="Change summary" class="form-input min-h-20"></textarea>
                                    <select name="approver_id" class="form-input user-select"></select>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create Version</button>
                                </div>
                            </form>
                        </div>

                        <section class="rounded-lg border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Document Approvals</h3>
                            </div>
                            <div id="approval-list" class="divide-y divide-zinc-100"></div>
                        </section>

                        <section class="rounded-lg border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Superseded Versions</h3>
                            </div>
                            <div id="document-retention-list" class="divide-y divide-zinc-100"></div>
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

                    <section data-panel="qms" class="panel hidden space-y-6">
                        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Quality Objectives</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                            <tr>
                                                <th class="px-4 py-3">Objective</th>
                                                <th class="px-4 py-3">Owner</th>
                                                <th class="px-4 py-3">Current</th>
                                                <th class="px-4 py-3">Target</th>
                                                <th class="px-4 py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="qms-objectives-body" class="divide-y divide-zinc-100"></tbody>
                                    </table>
                                </div>
                            </section>

                            <form id="objective-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Objective</h3>
                                <div class="space-y-3">
                                    <input name="title" required placeholder="Objective title" class="form-input">
                                    <input name="measurement_method" required placeholder="Measurement method" class="form-input">
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="baseline_value" type="number" step="0.01" placeholder="Baseline" class="form-input">
                                        <input name="target_value" required type="number" step="0.01" placeholder="Target" class="form-input">
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="current_value" type="number" step="0.01" placeholder="Current" class="form-input">
                                        <input name="unit" placeholder="Unit" value="%" class="form-input">
                                    </div>
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <input name="due_date" type="date" class="form-input">
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Audit Program</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                            <tr>
                                                <th class="px-4 py-3">Audit</th>
                                                <th class="px-4 py-3">Lead</th>
                                                <th class="px-4 py-3">Scheduled</th>
                                                <th class="px-4 py-3">Findings</th>
                                                <th class="px-4 py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="qms-audits-body" class="divide-y divide-zinc-100"></tbody>
                                    </table>
                                </div>
                            </section>

                            <form id="audit-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Audit</h3>
                                <div class="space-y-3">
                                    <input name="title" required placeholder="Audit title" class="form-input">
                                    <textarea name="scope" required placeholder="Scope" class="form-input min-h-24"></textarea>
                                    <select name="lead_auditor_id" class="form-input user-select"></select>
                                    <input name="scheduled_date" required type="date" class="form-input">
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <form id="objective-edit-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">Edit Objective</h3>
                                <div class="space-y-3">
                                    <select id="objective-edit-select" name="objective_id" required class="form-input"></select>
                                    <input name="title" required placeholder="Objective title" class="form-input">
                                    <input name="measurement_method" required placeholder="Measurement method" class="form-input">
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="target_value" required type="number" step="0.01" placeholder="Target" class="form-input">
                                        <input name="current_value" type="number" step="0.01" placeholder="Current" class="form-input">
                                    </div>
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="due_date" type="date" class="form-input">
                                        <select name="status" class="form-input">
                                            <option>Active</option>
                                            <option>At Risk</option>
                                            <option>Completed</option>
                                            <option>Retired</option>
                                        </select>
                                    </div>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save</button>
                                </div>
                            </form>

                            <form id="audit-edit-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">Edit Audit</h3>
                                <div class="space-y-3">
                                    <select id="audit-edit-select" name="audit_id" required class="form-input"></select>
                                    <input name="title" required placeholder="Audit title" class="form-input">
                                    <textarea name="scope" required placeholder="Scope" class="form-input min-h-20"></textarea>
                                    <select name="lead_auditor_id" class="form-input user-select"></select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="scheduled_date" required type="date" class="form-input">
                                        <select name="status" class="form-input">
                                            <option>Planned</option>
                                            <option>In Progress</option>
                                            <option>Completed</option>
                                            <option>Cancelled</option>
                                        </select>
                                    </div>
                                    <textarea name="summary" placeholder="Summary" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Audit Findings</h3>
                                </div>
                                <div id="qms-findings-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Management Reviews</h3>
                                </div>
                                <div id="qms-reviews-list" class="divide-y divide-zinc-100"></div>
                            </section>
                        </div>
                    </section>

                    <section data-panel="fsms" class="panel hidden space-y-6">
                        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">HACCP Plans</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                            <tr>
                                                <th class="px-4 py-3">Plan</th>
                                                <th class="px-4 py-3">Owner</th>
                                                <th class="px-4 py-3">Steps</th>
                                                <th class="px-4 py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="fsms-plans-body" class="divide-y divide-zinc-100"></tbody>
                                    </table>
                                </div>
                            </section>

                            <form id="haccp-plan-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New HACCP Plan</h3>
                                <div class="space-y-3">
                                    <input name="name" required placeholder="Plan name" class="form-input">
                                    <input name="product" required placeholder="Product" class="form-input">
                                    <textarea name="scope" required placeholder="Scope" class="form-input min-h-24"></textarea>
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <input name="effective_date" type="date" class="form-input">
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>

                        <form id="haccp-plan-edit-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">Edit HACCP Plan</h3>
                            <div class="grid gap-3 lg:grid-cols-3">
                                <select id="haccp-plan-edit-select" name="haccp_plan_id" required class="form-input"></select>
                                <input name="name" required placeholder="Plan name" class="form-input">
                                <input name="product" required placeholder="Product" class="form-input">
                                <textarea name="scope" required placeholder="Scope" class="form-input min-h-20 lg:col-span-2"></textarea>
                                <select name="owner_id" class="form-input user-select"></select>
                                <input name="effective_date" type="date" class="form-input">
                                <select name="status" class="form-input">
                                    <option>Draft</option>
                                    <option>Active</option>
                                    <option>Under Review</option>
                                    <option>Retired</option>
                                </select>
                                <button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save</button>
                            </div>
                        </form>

                        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Critical Control Points</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                            <tr>
                                                <th class="px-4 py-3">CCP</th>
                                                <th class="px-4 py-3">Limit</th>
                                                <th class="px-4 py-3">Frequency</th>
                                                <th class="px-4 py-3">Responsible</th>
                                            </tr>
                                        </thead>
                                        <tbody id="fsms-ccps-body" class="divide-y divide-zinc-100"></tbody>
                                    </table>
                                </div>
                            </section>

                            <form id="monitoring-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Monitoring Record</h3>
                                <div class="space-y-3">
                                    <select id="fsms-monitorable-select" name="monitorable_id" required class="form-input"></select>
                                    <select name="recorded_by_id" class="form-input user-select"></select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="measured_value" type="number" step="0.01" placeholder="Value" class="form-input">
                                        <input name="unit" placeholder="Unit" value="C" class="form-input">
                                    </div>
                                    <input name="result" required placeholder="Result" value="Pass" class="form-input">
                                    <input name="observed_at" type="datetime-local" class="form-input">
                                    <label class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700">
                                        <input name="is_deviation" type="checkbox" class="size-4 rounded border-zinc-300 text-emerald-700 focus:ring-emerald-600">
                                        Deviation
                                    </label>
                                    <textarea name="notes" placeholder="Notes" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Record</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Hazard Analysis</h3>
                                </div>
                                <div id="fsms-hazards-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Monitoring Records</h3>
                                </div>
                                <div id="fsms-monitoring-list" class="divide-y divide-zinc-100"></div>
                            </section>
                        </div>

                        <section class="rounded-lg border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Prerequisite Programs</h3>
                            </div>
                            <div id="fsms-prps-list" class="divide-y divide-zinc-100"></div>
                        </section>
                    </section>

                    <section data-panel="supplier-quality" class="panel hidden space-y-6">
                        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Approved Supplier List</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                            <tr>
                                                <th class="px-4 py-3">Supplier</th>
                                                <th class="px-4 py-3">Category</th>
                                                <th class="px-4 py-3">Risk</th>
                                                <th class="px-4 py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="supplier-quality-suppliers-body" class="divide-y divide-zinc-100"></tbody>
                                    </table>
                                </div>
                            </section>

                            <form id="supplier-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Supplier</h3>
                                <div class="space-y-3">
                                    <input name="name" required placeholder="Supplier name" class="form-input">
                                    <input name="supplier_code" required placeholder="Supplier code" class="form-input">
                                    <input name="category" required placeholder="Category" class="form-input">
                                    <input name="contact_email" type="email" placeholder="Contact email" class="form-input">
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Equipment Assets</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                            <tr>
                                                <th class="px-4 py-3">Asset</th>
                                                <th class="px-4 py-3">Location</th>
                                                <th class="px-4 py-3">Next Due</th>
                                                <th class="px-4 py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="supplier-quality-equipment-body" class="divide-y divide-zinc-100"></tbody>
                                    </table>
                                </div>
                            </section>

                            <form id="equipment-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Equipment</h3>
                                <div class="space-y-3">
                                    <input name="asset_tag" required placeholder="Asset tag" class="form-input">
                                    <input name="name" required placeholder="Equipment name" class="form-input">
                                    <input name="location" required placeholder="Location" class="form-input">
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <input name="calibration_interval_days" type="number" min="1" value="180" class="form-input">
                                    <label class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700">
                                        <input name="critical_to_food_safety" type="checkbox" class="size-4 rounded border-zinc-300 text-emerald-700 focus:ring-emerald-600">
                                        Food-safety critical
                                    </label>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <form id="supplier-edit-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">Edit Supplier</h3>
                                <div class="space-y-3">
                                    <select id="supplier-edit-select" name="supplier_id" required class="form-input"></select>
                                    <input name="name" required placeholder="Supplier name" class="form-input">
                                    <input name="category" required placeholder="Category" class="form-input">
                                    <input name="contact_email" type="email" placeholder="Contact email" class="form-input">
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <select name="approval_status" class="form-input">
                                            <option>Pending</option>
                                            <option>Approved</option>
                                            <option>Conditional</option>
                                            <option>Rejected</option>
                                        </select>
                                        <select name="risk_level" class="form-input">
                                            <option>Low</option>
                                            <option>Medium</option>
                                            <option>High</option>
                                        </select>
                                    </div>
                                    <input name="approved_until" type="date" class="form-input">
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save</button>
                                </div>
                            </form>

                            <form id="equipment-edit-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">Edit Equipment</h3>
                                <div class="space-y-3">
                                    <select id="equipment-edit-select" name="equipment_asset_id" required class="form-input"></select>
                                    <input name="asset_tag" required placeholder="Asset tag" class="form-input">
                                    <input name="name" required placeholder="Equipment name" class="form-input">
                                    <input name="location" required placeholder="Location" class="form-input">
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="calibration_interval_days" type="number" min="1" class="form-input">
                                        <select name="status" class="form-input">
                                            <option>Active</option>
                                            <option>Hold</option>
                                            <option>Retired</option>
                                        </select>
                                    </div>
                                    <label class="flex items-center gap-2 rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700">
                                        <input name="critical_to_food_safety" type="checkbox" class="size-4 rounded border-zinc-300 text-emerald-700 focus:ring-emerald-600">
                                        Food-safety critical
                                    </label>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Supplier Evaluations</h3>
                                </div>
                                <div id="supplier-quality-evaluations-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <form id="supplier-evaluation-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Evaluation</h3>
                                <div class="space-y-3">
                                    <select id="supplier-quality-supplier-select" name="supplier_id" required class="form-input"></select>
                                    <select name="evaluated_by_id" class="form-input user-select"></select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="score" required type="number" min="0" max="100" value="85" class="form-input">
                                        <select name="result" class="form-input">
                                            <option>Approved</option>
                                            <option>Conditional</option>
                                            <option>Rejected</option>
                                        </select>
                                    </div>
                                    <input name="evaluation_date" required type="date" class="form-input">
                                    <input name="next_review_date" type="date" class="form-input">
                                    <textarea name="notes" placeholder="Notes" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Record</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Calibration Records</h3>
                                </div>
                                <div id="supplier-quality-calibrations-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <form id="calibration-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Calibration</h3>
                                <div class="space-y-3">
                                    <select id="supplier-quality-equipment-select" name="equipment_asset_id" required class="form-input"></select>
                                    <select name="performed_by_id" class="form-input user-select"></select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="performed_at" required type="date" class="form-input">
                                        <input name="due_at" required type="date" class="form-input">
                                    </div>
                                    <select name="result" class="form-input">
                                        <option>Pass</option>
                                        <option>Adjusted</option>
                                        <option>Fail</option>
                                        <option>Overdue</option>
                                    </select>
                                    <input name="certificate_number" placeholder="Certificate number" class="form-input">
                                    <textarea name="notes" placeholder="Notes" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Record</button>
                                </div>
                            </form>
                        </div>

                        <section class="rounded-lg border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-200 px-4 py-3">
                                <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Supplier Certificates</h3>
                            </div>
                            <div id="supplier-quality-certificates-list" class="divide-y divide-zinc-100"></div>
                        </section>
                    </section>

                    <section data-panel="training" class="panel hidden space-y-6">
                        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Training Programs</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                            <tr>
                                                <th class="px-4 py-3">Program</th>
                                                <th class="px-4 py-3">Clause</th>
                                                <th class="px-4 py-3">Owner</th>
                                                <th class="px-4 py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="training-programs-body" class="divide-y divide-zinc-100"></tbody>
                                    </table>
                                </div>
                            </section>

                            <form id="training-program-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Program</h3>
                                <div class="space-y-3">
                                    <input name="code" required placeholder="Program code" class="form-input">
                                    <input name="title" required placeholder="Title" class="form-input">
                                    <input name="iso_clause" placeholder="ISO clause" class="form-input">
                                    <input name="delivery_method" placeholder="Delivery method" value="Classroom" class="form-input">
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <input name="refresher_interval_days" type="number" min="1" placeholder="Refresher days" class="form-input">
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Training Assignments</h3>
                                </div>
                                <div id="training-assignments-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <form id="training-assignment-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Assignment</h3>
                                <div class="space-y-3">
                                    <select id="training-program-select" name="training_program_id" required class="form-input"></select>
                                    <select name="user_id" class="form-input user-select"></select>
                                    <select id="training-role-select" name="required_for_role_id" class="form-input"></select>
                                    <input name="due_date" required type="date" class="form-input">
                                    <textarea name="notes" placeholder="Notes" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Assign</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <form id="training-program-edit-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">Edit Program</h3>
                                <div class="space-y-3">
                                    <select id="training-program-edit-select" name="training_program_id" required class="form-input"></select>
                                    <input name="code" required placeholder="Program code" class="form-input">
                                    <input name="title" required placeholder="Title" class="form-input">
                                    <input name="iso_clause" placeholder="ISO clause" class="form-input">
                                    <input name="delivery_method" placeholder="Delivery method" class="form-input">
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="refresher_interval_days" type="number" min="1" placeholder="Refresher days" class="form-input">
                                        <select name="status" class="form-input">
                                            <option>Active</option>
                                            <option>Draft</option>
                                            <option>Retired</option>
                                        </select>
                                    </div>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save</button>
                                </div>
                            </form>

                            <form id="training-assignment-edit-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">Edit Assignment</h3>
                                <div class="space-y-3">
                                    <select id="training-assignment-edit-select" name="training_assignment_id" required class="form-input"></select>
                                    <select name="user_id" class="form-input user-select"></select>
                                    <select id="training-assignment-edit-role-select" name="required_for_role_id" class="form-input"></select>
                                    <input name="due_date" required type="date" class="form-input">
                                    <select name="status" class="form-input">
                                        <option>Assigned</option>
                                        <option>Completed</option>
                                        <option>Needs Coaching</option>
                                        <option>Cancelled</option>
                                    </select>
                                    <textarea name="notes" placeholder="Notes" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Training Records</h3>
                                </div>
                                <div id="training-records-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <form id="training-record-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">Complete Training</h3>
                                <div class="space-y-3">
                                    <select id="training-assignment-select" name="training_assignment_id" required class="form-input"></select>
                                    <select name="trainer_id" class="form-input user-select"></select>
                                    <select id="training-evidence-document-select" name="evidence_document_id" class="form-input"></select>
                                    <input name="completed_at" required type="date" class="form-input">
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="score" type="number" min="0" max="100" step="0.01" placeholder="Score" class="form-input">
                                        <select name="result" class="form-input">
                                            <option>Pass</option>
                                            <option>Fail</option>
                                        </select>
                                    </div>
                                    <select name="competency_status" class="form-input">
                                        <option>Competent</option>
                                        <option>Needs Coaching</option>
                                    </select>
                                    <input name="expires_at" type="date" class="form-input">
                                    <textarea name="notes" placeholder="Notes" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Record</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Competency Requirements</h3>
                                </div>
                                <div id="training-requirements-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <form id="training-requirement-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Requirement</h3>
                                <div class="space-y-3">
                                    <select id="training-requirement-role-select" name="role_id" required class="form-input"></select>
                                    <select id="training-requirement-program-select" name="training_program_id" required class="form-input"></select>
                                    <input name="competency_area" required placeholder="Competency area" class="form-input">
                                    <input name="required_level" placeholder="Required level" value="Qualified" class="form-input">
                                    <input name="assessment_method" placeholder="Assessment method" value="Supervisor verification" class="form-input">
                                    <input name="due_within_days" type="number" min="1" value="30" class="form-input">
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Awareness Acknowledgements</h3>
                                </div>
                                <div id="training-awareness-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <form id="training-awareness-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Awareness</h3>
                                <div class="space-y-3">
                                    <select id="training-awareness-document-select" name="document_id" required class="form-input"></select>
                                    <select name="user_id" class="form-input user-select"></select>
                                    <input name="acknowledged_at" type="datetime-local" class="form-input">
                                    <textarea name="statement" placeholder="Statement" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Acknowledge</button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section data-panel="incident-response" class="panel hidden space-y-6">
                        <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Incident Reports</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                            <tr>
                                                <th class="px-4 py-3">Incident</th>
                                                <th class="px-4 py-3">Owner</th>
                                                <th class="px-4 py-3">Control</th>
                                                <th class="px-4 py-3">Severity</th>
                                                <th class="px-4 py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="incident-reports-body" class="divide-y divide-zinc-100"></tbody>
                                    </table>
                                </div>
                            </section>

                            <form id="incident-report-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Incident</h3>
                                <div class="space-y-3">
                                    <input name="reference" required placeholder="Incident reference" class="form-input">
                                    <input name="title" required placeholder="Title" class="form-input">
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="incident_type" required placeholder="Type" value="Food Safety" class="form-input">
                                        <select name="severity" class="form-input">
                                            <option>Minor</option>
                                            <option>Major</option>
                                            <option>Critical</option>
                                        </select>
                                    </div>
                                    <select name="reported_by_id" class="form-input user-select"></select>
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <select id="incident-source-control-select" name="source_control" class="form-input"></select>
                                    <input name="detected_at" type="datetime-local" class="form-input">
                                    <textarea name="description" required placeholder="Description" class="form-input min-h-20"></textarea>
                                    <textarea name="immediate_containment" placeholder="Immediate containment" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Record</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <form id="incident-report-edit-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">Edit Incident</h3>
                                <div class="space-y-3">
                                    <select id="incident-report-edit-select" name="incident_report_id" required class="form-input"></select>
                                    <input name="title" required placeholder="Title" class="form-input">
                                    <div class="grid grid-cols-2 gap-2">
                                        <select name="severity" class="form-input">
                                            <option>Minor</option>
                                            <option>Major</option>
                                            <option>Critical</option>
                                        </select>
                                        <select name="status" class="form-input">
                                            <option>Open</option>
                                            <option>Contained</option>
                                            <option>Closed</option>
                                        </select>
                                    </div>
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <input name="detected_at" type="datetime-local" class="form-input">
                                    <textarea name="description" required placeholder="Description" class="form-input min-h-20"></textarea>
                                    <textarea name="immediate_containment" placeholder="Immediate containment" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save</button>
                                </div>
                            </form>

                            <form id="emergency-plan-edit-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">Edit Emergency Plan</h3>
                                <div class="space-y-3">
                                    <select id="emergency-plan-edit-select" name="emergency_response_plan_id" required class="form-input"></select>
                                    <input name="name" required placeholder="Plan name" class="form-input">
                                    <textarea name="scenario" required placeholder="Scenario" class="form-input min-h-20"></textarea>
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="review_frequency_days" type="number" min="1" class="form-input">
                                        <select name="status" class="form-input">
                                            <option>Active</option>
                                            <option>Under Review</option>
                                            <option>Retired</option>
                                        </select>
                                    </div>
                                    <textarea name="response_steps" placeholder="Response steps, one per line" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Save</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Incident Actions</h3>
                                </div>
                                <div id="incident-actions-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <form id="incident-action-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Action</h3>
                                <div class="space-y-3">
                                    <select id="incident-report-select" name="incident_report_id" required class="form-input"></select>
                                    <input name="action_type" required placeholder="Action type" value="Containment" class="form-input">
                                    <textarea name="description" required placeholder="Action description" class="form-input min-h-20"></textarea>
                                    <select name="responsible_user_id" class="form-input user-select"></select>
                                    <input name="due_date" type="date" class="form-input">
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Emergency Plans</h3>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                                        <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-normal text-zinc-500">
                                            <tr>
                                                <th class="px-4 py-3">Plan</th>
                                                <th class="px-4 py-3">Owner</th>
                                                <th class="px-4 py-3">Next Review</th>
                                                <th class="px-4 py-3">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="emergency-plans-body" class="divide-y divide-zinc-100"></tbody>
                                    </table>
                                </div>
                            </section>

                            <form id="emergency-plan-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Emergency Plan</h3>
                                <div class="space-y-3">
                                    <input name="name" required placeholder="Plan name" class="form-input">
                                    <textarea name="scenario" required placeholder="Scenario" class="form-input min-h-24"></textarea>
                                    <select name="owner_id" class="form-input user-select"></select>
                                    <input name="review_frequency_days" type="number" min="1" value="365" class="form-input">
                                    <textarea name="response_steps" placeholder="Response steps, one per line" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Create</button>
                                </div>
                            </form>
                        </div>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <section class="rounded-lg border border-zinc-200 bg-white">
                                <div class="border-b border-zinc-200 px-4 py-3">
                                    <h3 class="text-sm font-semibold uppercase tracking-normal text-zinc-600">Emergency Drills</h3>
                                </div>
                                <div id="emergency-drills-list" class="divide-y divide-zinc-100"></div>
                            </section>

                            <form id="emergency-drill-form" class="rounded-lg border border-zinc-200 bg-white p-4">
                                <h3 class="mb-4 text-sm font-semibold uppercase tracking-normal text-zinc-600">New Drill</h3>
                                <div class="space-y-3">
                                    <select id="emergency-plan-select" name="emergency_response_plan_id" required class="form-input"></select>
                                    <select name="facilitator_id" class="form-input user-select"></select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="scheduled_at" type="date" class="form-input">
                                        <input name="completed_at" required type="date" class="form-input">
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <select name="result" class="form-input">
                                            <option>Effective</option>
                                            <option>Needs Improvement</option>
                                            <option>Failed</option>
                                        </select>
                                        <input name="participants_count" type="number" min="0" value="3" class="form-input">
                                    </div>
                                    <input name="effectiveness_score" type="number" min="0" max="100" placeholder="Effectiveness score" class="form-input">
                                    <textarea name="scenario_notes" placeholder="Scenario notes" class="form-input min-h-20"></textarea>
                                    <textarea name="notes" placeholder="Notes" class="form-input min-h-20"></textarea>
                                    <button class="w-full rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Record</button>
                                </div>
                            </form>
                        </div>
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
