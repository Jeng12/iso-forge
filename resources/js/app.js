const root = document.getElementById('phase3-app');

if (root) {
    const state = {
        token: localStorage.getItem('isoForgeToken'),
        user: null,
        tenant: null,
        users: [],
        snapshot: null,
        documents: [],
        approvals: [],
        risks: [],
        correctiveActions: [],
        tasks: [],
        auditLogs: [],
    };

    const els = {
        loginScreen: document.getElementById('login-screen'),
        workspace: document.getElementById('workspace'),
        loginForm: document.getElementById('login-form'),
        loginError: document.getElementById('login-error'),
        statusLine: document.getElementById('status-line'),
        tenantLabel: document.getElementById('tenant-label'),
        userLabel: document.getElementById('user-label'),
        metricGrid: document.getElementById('metric-grid'),
        overviewApprovals: document.getElementById('overview-approvals'),
        overviewTasks: document.getElementById('overview-tasks'),
        documentsBody: document.getElementById('documents-body'),
        approvalList: document.getElementById('approval-list'),
        risksBody: document.getElementById('risks-body'),
        capaList: document.getElementById('capa-list'),
        taskList: document.getElementById('task-list'),
        auditBody: document.getElementById('audit-body'),
        documentForm: document.getElementById('document-form'),
        riskForm: document.getElementById('risk-form'),
        capaForm: document.getElementById('capa-form'),
    };

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const tenantPath = (path) => `/api/tenants/${state.tenant.slug}${path}`;

    const api = async (path, options = {}) => {
        const headers = {
            Accept: 'application/json',
            ...(state.token ? { Authorization: `Bearer ${state.token}` } : {}),
            ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            ...(options.headers ?? {}),
        };

        const response = await fetch(path, { ...options, headers });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const message = payload.message || Object.values(payload.errors ?? {})?.[0]?.[0] || 'Request failed.';
            throw new Error(message);
        }

        return payload;
    };

    const showStatus = (message, tone = 'success') => {
        els.statusLine.textContent = message;
        els.statusLine.className = `rounded-lg border px-4 py-3 text-sm font-medium ${
            tone === 'error'
                ? 'border-red-200 bg-red-50 text-red-700'
                : 'border-emerald-200 bg-emerald-50 text-emerald-800'
        }`;
        els.statusLine.classList.remove('hidden');
        window.clearTimeout(showStatus.timer);
        showStatus.timer = window.setTimeout(() => els.statusLine.classList.add('hidden'), 4500);
    };

    const showLogin = () => {
        els.loginScreen.classList.remove('hidden');
        els.workspace.classList.add('hidden');
    };

    const showWorkspace = () => {
        els.loginScreen.classList.add('hidden');
        els.workspace.classList.remove('hidden');
    };

    const hasPermission = (permission) => {
        return (state.user?.roles ?? []).some((role) => {
            return (role.permissions ?? []).some((item) => item.slug === permission);
        });
    };

    const renderStatusBadge = (status) => {
        const color = status === 'Approved' || status === 'Completed' || status === 'Verified'
            ? 'bg-emerald-100 text-emerald-800'
            : status === 'Pending' || status === 'Waiting' || status === 'Under Review'
                ? 'bg-amber-100 text-amber-800'
                : 'bg-sky-100 text-sky-800';

        return `<span class="rounded-lg px-2.5 py-1 text-xs font-semibold ${color}">${escapeHtml(status)}</span>`;
    };

    const fillUserSelects = () => {
        const options = state.users.map((user) => {
            return `<option value="${user.id}">${escapeHtml(user.name)}</option>`;
        }).join('');

        document.querySelectorAll('.user-select').forEach((select) => {
            select.innerHTML = options;
        });
    };

    const renderMetrics = () => {
        const metrics = state.snapshot?.metrics ?? {};
        const items = [
            ['Documents', metrics.documents ?? 0],
            ['Approved', metrics.approved_documents ?? 0],
            ['Open CAPA', metrics.open_capas ?? 0],
            ['High Risks', metrics.high_risks ?? 0],
            ['Audit Events', metrics.audit_events ?? 0],
        ];

        els.metricGrid.innerHTML = items.map(([label, value]) => `
            <div class="rounded-lg border border-zinc-200 bg-white p-4">
                <div class="text-sm font-medium text-zinc-500">${label}</div>
                <div class="mt-3 text-3xl font-semibold">${value}</div>
            </div>
        `).join('');
    };

    const renderDocuments = () => {
        els.documentsBody.innerHTML = state.documents.map((document) => `
            <tr>
                <td class="whitespace-nowrap px-4 py-3 font-medium">${escapeHtml(document.document_number)}</td>
                <td class="min-w-64 px-4 py-3">${escapeHtml(document.title)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(document.owner?.name)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(document.current_version?.version_number ?? 'Draft')}</td>
                <td class="whitespace-nowrap px-4 py-3">${renderStatusBadge(document.status)}</td>
            </tr>
        `).join('');

        const approvalRows = state.approvals.map((approval) => {
            const document = approval.document_version?.document ?? {};
            const button = approval.status === 'Pending' && hasPermission('document.approve')
                ? `<button data-approve-id="${approval.id}" class="rounded-lg bg-zinc-950 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Approve</button>`
                : '';

            return `
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(document.document_number)} · ${escapeHtml(document.title)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(approval.approver?.name)} · v${escapeHtml(approval.document_version?.version_number)}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        ${renderStatusBadge(approval.status)}
                        ${button}
                    </div>
                </div>
            `;
        }).join('');

        els.approvalList.innerHTML = approvalRows || '<div class="p-4 text-sm text-zinc-500">No approvals found.</div>';
        els.overviewApprovals.innerHTML = approvalRows || '<div class="p-4 text-sm text-zinc-500">No approvals found.</div>';
    };

    const renderRisks = () => {
        els.risksBody.innerHTML = state.risks.map((risk) => `
            <tr>
                <td class="min-w-72 px-4 py-3">
                    <div class="font-medium">${escapeHtml(risk.title)}</div>
                    <div class="mt-1 text-xs font-medium text-zinc-500">${escapeHtml(risk.category)}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(risk.owner?.name)}</td>
                <td class="px-4 py-3">${renderStatusBadge(risk.risk_score)}</td>
                <td class="px-4 py-3 text-zinc-600">${escapeHtml(risk.residual_score ?? '-')}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(risk.status)}</td>
            </tr>
        `).join('');
    };

    const renderCapa = () => {
        els.capaList.innerHTML = state.correctiveActions.map((action) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(action.title)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(action.non_conformance?.reference ?? '')} ${escapeHtml(action.non_conformance?.iso_clause ?? '')}</div>
                    </div>
                    ${renderStatusBadge(action.status)}
                </div>
                <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                    <div><div class="font-medium text-zinc-500">Assigned</div><div class="mt-1">${escapeHtml(action.assignee?.name)}</div></div>
                    <div><div class="font-medium text-zinc-500">Verifier</div><div class="mt-1">${escapeHtml(action.verifier?.name)}</div></div>
                    <div><div class="font-medium text-zinc-500">Due</div><div class="mt-1">${escapeHtml(action.due_date ?? '-')}</div></div>
                </div>
            </div>
        `).join('');
    };

    const renderTasks = () => {
        const taskRows = state.tasks.map((task) => {
            const canComplete = task.status !== 'Completed'
                && (task.assigned_to_id === state.user?.id || hasPermission('capa.close'));
            const button = canComplete
                ? `<button data-complete-task-id="${task.id}" class="rounded-lg bg-zinc-950 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-800">Complete</button>`
                : '';

            return `
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(task.title)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(task.assignee?.name)} · ${escapeHtml(task.state)} · ${escapeHtml(task.due_at ?? '-')}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        ${renderStatusBadge(task.status)}
                        ${button}
                    </div>
                </div>
            `;
        }).join('');

        els.taskList.innerHTML = taskRows || '<div class="p-4 text-sm text-zinc-500">No workflow tasks found.</div>';
        els.overviewTasks.innerHTML = taskRows || '<div class="p-4 text-sm text-zinc-500">No workflow tasks found.</div>';
    };

    const renderAudit = () => {
        els.auditBody.innerHTML = state.auditLogs.map((log) => `
            <tr>
                <td class="whitespace-nowrap px-4 py-3 font-medium">${escapeHtml(log.event)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(log.user?.name)}</td>
                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-zinc-600">${escapeHtml(log.entry_hash?.slice(0, 24))}</td>
                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-zinc-600">${escapeHtml(log.previous_hash?.slice(0, 24))}</td>
            </tr>
        `).join('');
    };

    const render = () => {
        els.tenantLabel.textContent = state.tenant?.name ?? 'Workspace';
        els.userLabel.textContent = `${state.user?.name ?? 'Signed in'} · ${state.user?.job_title ?? 'User'}`;
        fillUserSelects();
        renderMetrics();
        renderDocuments();
        renderRisks();
        renderCapa();
        renderTasks();
        renderAudit();
    };

    const safeFetch = async (path, fallback = []) => {
        try {
            const payload = await api(path);
            return payload.data ?? payload;
        } catch (error) {
            return fallback;
        }
    };

    const loadWorkspace = async () => {
        const profile = await api('/api/user');
        state.user = profile;
        state.tenant = profile.tenant;

        const [
            snapshot,
            users,
            documents,
            approvals,
            risks,
            correctiveActions,
            tasks,
            auditLogs,
        ] = await Promise.all([
            api(tenantPath('/snapshot')),
            safeFetch(tenantPath('/users')),
            safeFetch(tenantPath('/documents')),
            safeFetch(tenantPath('/document-approvals')),
            safeFetch(tenantPath('/risks')),
            safeFetch(tenantPath('/corrective-actions')),
            safeFetch(tenantPath('/workflow-tasks')),
            safeFetch(tenantPath('/audit-logs')),
        ]);

        state.snapshot = snapshot;
        state.users = users;
        state.documents = documents;
        state.approvals = approvals;
        state.risks = risks;
        state.correctiveActions = correctiveActions;
        state.tasks = tasks;
        state.auditLogs = auditLogs;

        showWorkspace();
        render();
    };

    els.loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        els.loginError.classList.add('hidden');

        try {
            const payload = await api('/api/auth/login', {
                method: 'POST',
                body: JSON.stringify({
                    email: document.getElementById('login-email').value,
                    password: document.getElementById('login-password').value,
                }),
            });

            state.token = payload.token;
            localStorage.setItem('isoForgeToken', state.token);
            await loadWorkspace();
        } catch (error) {
            els.loginError.textContent = error.message;
            els.loginError.classList.remove('hidden');
        }
    });

    document.getElementById('logout-button').addEventListener('click', async () => {
        try {
            await api('/api/auth/logout', { method: 'POST' });
        } catch (error) {
            // Token cleanup is local even if the server-side token was already removed.
        }

        localStorage.removeItem('isoForgeToken');
        state.token = null;
        state.user = null;
        state.tenant = null;
        showLogin();
    });

    document.getElementById('refresh-button').addEventListener('click', async () => {
        await loadWorkspace();
        showStatus('Workspace refreshed.');
    });

    document.querySelectorAll('.tab-button').forEach((button) => {
        button.addEventListener('click', () => {
            const tab = button.dataset.tab;
            document.querySelectorAll('.panel').forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.panel !== tab);
            });
            document.querySelectorAll('.tab-button').forEach((item) => {
                const active = item.dataset.tab === tab;
                item.classList.toggle('bg-zinc-950', active);
                item.classList.toggle('text-white', active);
                item.classList.toggle('text-zinc-600', !active);
            });
        });
    });

    els.documentForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.documentForm);
        const approverId = form.get('approver_id');

        try {
            await api(tenantPath('/documents'), {
                method: 'POST',
                body: JSON.stringify({
                    document_number: form.get('document_number'),
                    title: form.get('title'),
                    category: form.get('category'),
                    owner_id: Number(form.get('owner_id')),
                    version_number: form.get('version_number'),
                    file_path: form.get('file_path'),
                    mime_type: 'application/pdf',
                    approver_ids: approverId ? [Number(approverId)] : [],
                }),
            });
            els.documentForm.reset();
            await loadWorkspace();
            showStatus('Document created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.riskForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.riskForm);

        const payload = {
            title: form.get('title'),
            category: form.get('category') || 'Quality',
            likelihood: Number(form.get('likelihood')),
            severity: Number(form.get('severity')),
            owner_id: Number(form.get('owner_id')),
            treatment_plan: form.get('treatment_plan'),
            status: 'Treatment Planned',
        };

        if (form.get('residual_likelihood')) {
            payload.residual_likelihood = Number(form.get('residual_likelihood'));
        }

        if (form.get('residual_severity')) {
            payload.residual_severity = Number(form.get('residual_severity'));
        }

        try {
            await api(tenantPath('/risks'), {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            els.riskForm.reset();
            await loadWorkspace();
            showStatus('Risk created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.capaForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.capaForm);

        try {
            const nonConformance = await api(tenantPath('/non-conformances'), {
                method: 'POST',
                body: JSON.stringify({
                    reference: form.get('reference'),
                    source: form.get('source'),
                    description: form.get('description'),
                    iso_clause: form.get('iso_clause'),
                    severity: form.get('severity'),
                    detected_at: new Date().toISOString().slice(0, 10),
                    owner_id: Number(form.get('assigned_to_id')),
                }),
            });

            await api(tenantPath('/corrective-actions'), {
                method: 'POST',
                body: JSON.stringify({
                    non_conformance_id: nonConformance.data.id,
                    title: form.get('title'),
                    description: form.get('action_description'),
                    assigned_to_id: Number(form.get('assigned_to_id')),
                    verified_by_id: Number(form.get('verified_by_id')),
                    due_date: form.get('due_date') || null,
                }),
            });

            els.capaForm.reset();
            await loadWorkspace();
            showStatus('CAPA created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    document.addEventListener('click', async (event) => {
        const approveButton = event.target.closest('[data-approve-id]');
        const completeButton = event.target.closest('[data-complete-task-id]');

        if (approveButton) {
            try {
                await api(tenantPath(`/document-approvals/${approveButton.dataset.approveId}/approve`), {
                    method: 'POST',
                    body: JSON.stringify({ reason: 'Browser approval' }),
                });
                await loadWorkspace();
                showStatus('Document approved.');
            } catch (error) {
                showStatus(error.message, 'error');
            }
        }

        if (completeButton) {
            try {
                await api(tenantPath(`/workflow-tasks/${completeButton.dataset.completeTaskId}/complete`), {
                    method: 'POST',
                    body: JSON.stringify({ comments: 'Completed from workspace.' }),
                });
                await loadWorkspace();
                showStatus('Workflow task completed.');
            } catch (error) {
                showStatus(error.message, 'error');
            }
        }
    });

    if (state.token) {
        loadWorkspace().catch(() => {
            localStorage.removeItem('isoForgeToken');
            state.token = null;
            showLogin();
        });
    } else {
        showLogin();
    }
}
