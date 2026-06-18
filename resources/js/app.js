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
        qms: {
            objectives: [],
            audits: [],
            findings: [],
            management_reviews: [],
        },
        fsms: {
            haccp_plans: [],
            hazards: [],
            ccps: [],
            oprps: [],
            prps: [],
            monitoring_records: [],
        },
        supplierQuality: {
            suppliers: [],
            evaluations: [],
            certificates: [],
            equipment_assets: [],
            calibration_records: [],
        },
        training: {
            programs: [],
            requirements: [],
            assignments: [],
            records: [],
            awareness_acknowledgements: [],
            roles: [],
        },
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
        objectivesBody: document.getElementById('qms-objectives-body'),
        auditsBody: document.getElementById('qms-audits-body'),
        findingsList: document.getElementById('qms-findings-list'),
        reviewsList: document.getElementById('qms-reviews-list'),
        fsmsPlansBody: document.getElementById('fsms-plans-body'),
        fsmsCcpsBody: document.getElementById('fsms-ccps-body'),
        fsmsHazardsList: document.getElementById('fsms-hazards-list'),
        fsmsMonitoringList: document.getElementById('fsms-monitoring-list'),
        fsmsPrpsList: document.getElementById('fsms-prps-list'),
        fsmsMonitorableSelect: document.getElementById('fsms-monitorable-select'),
        supplierQualitySuppliersBody: document.getElementById('supplier-quality-suppliers-body'),
        supplierQualityEquipmentBody: document.getElementById('supplier-quality-equipment-body'),
        supplierQualityEvaluationsList: document.getElementById('supplier-quality-evaluations-list'),
        supplierQualityCalibrationsList: document.getElementById('supplier-quality-calibrations-list'),
        supplierQualityCertificatesList: document.getElementById('supplier-quality-certificates-list'),
        supplierQualitySupplierSelect: document.getElementById('supplier-quality-supplier-select'),
        supplierQualityEquipmentSelect: document.getElementById('supplier-quality-equipment-select'),
        trainingProgramsBody: document.getElementById('training-programs-body'),
        trainingAssignmentsList: document.getElementById('training-assignments-list'),
        trainingRecordsList: document.getElementById('training-records-list'),
        trainingRequirementsList: document.getElementById('training-requirements-list'),
        trainingAwarenessList: document.getElementById('training-awareness-list'),
        trainingProgramSelect: document.getElementById('training-program-select'),
        trainingRoleSelect: document.getElementById('training-role-select'),
        trainingAssignmentSelect: document.getElementById('training-assignment-select'),
        trainingEvidenceDocumentSelect: document.getElementById('training-evidence-document-select'),
        trainingRequirementRoleSelect: document.getElementById('training-requirement-role-select'),
        trainingRequirementProgramSelect: document.getElementById('training-requirement-program-select'),
        trainingAwarenessDocumentSelect: document.getElementById('training-awareness-document-select'),
        objectiveForm: document.getElementById('objective-form'),
        auditForm: document.getElementById('audit-form'),
        haccpPlanForm: document.getElementById('haccp-plan-form'),
        monitoringForm: document.getElementById('monitoring-form'),
        supplierForm: document.getElementById('supplier-form'),
        supplierEvaluationForm: document.getElementById('supplier-evaluation-form'),
        equipmentForm: document.getElementById('equipment-form'),
        calibrationForm: document.getElementById('calibration-form'),
        trainingProgramForm: document.getElementById('training-program-form'),
        trainingRequirementForm: document.getElementById('training-requirement-form'),
        trainingAssignmentForm: document.getElementById('training-assignment-form'),
        trainingRecordForm: document.getElementById('training-record-form'),
        trainingAwarenessForm: document.getElementById('training-awareness-form'),
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
        const color = status === 'Approved' || status === 'Completed' || status === 'Verified' || status === 'Active' || status === 'Pass' || status === 'Current' || status === 'Competent' || status === 'Acknowledged'
            ? 'bg-emerald-100 text-emerald-800'
            : status === 'Deviation' || status === 'Rejected' || status === 'Fail' || status === 'Overdue' || status === 'Expired' || status === 'Hold'
                ? 'bg-red-100 text-red-800'
            : status === 'Pending' || status === 'Waiting' || status === 'Under Review' || status === 'Conditional' || status === 'Expiring' || status === 'Adjusted' || status === 'Assigned' || status === 'Needs Coaching'
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
            ['QMS Objectives', metrics.quality_objectives ?? 0],
            ['Planned Audits', metrics.planned_audits ?? 0],
            ['Open Findings', metrics.open_findings ?? 0],
            ['HACCP Plans', metrics.haccp_plans ?? 0],
            ['Active CCPs', metrics.active_ccps ?? 0],
            ['FSMS Deviations', metrics.fsms_deviations ?? 0],
            ['Approved Suppliers', metrics.approved_suppliers ?? 0],
            ['Certs Expiring', metrics.supplier_certificates_expiring ?? 0],
            ['Critical Equipment', metrics.critical_equipment ?? 0],
            ['Calibrations Due', metrics.calibrations_due ?? 0],
            ['Training Programs', metrics.training_programs ?? 0],
            ['Open Training', metrics.open_training_assignments ?? 0],
            ['Competent Records', metrics.competent_records ?? 0],
            ['Awareness', metrics.awareness_acknowledgements ?? 0],
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

    const renderQms = () => {
        els.objectivesBody.innerHTML = (state.qms.objectives ?? []).map((objective) => `
            <tr>
                <td class="min-w-72 px-4 py-3">
                    <div class="font-medium">${escapeHtml(objective.title)}</div>
                    <div class="mt-1 text-xs font-medium text-zinc-500">${escapeHtml(objective.measurement_method)}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(objective.owner?.name)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(objective.current_value ?? '-')} ${escapeHtml(objective.unit)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(objective.target_value)} ${escapeHtml(objective.unit)}</td>
                <td class="whitespace-nowrap px-4 py-3">${renderStatusBadge(objective.status)}</td>
            </tr>
        `).join('');

        els.auditsBody.innerHTML = (state.qms.audits ?? []).map((audit) => `
            <tr>
                <td class="min-w-72 px-4 py-3">
                    <div class="font-medium">${escapeHtml(audit.title)}</div>
                    <div class="mt-1 text-xs font-medium text-zinc-500">${escapeHtml(audit.scope)}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(audit.lead_auditor?.name)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(audit.scheduled_date)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml((audit.findings ?? []).length)}</td>
                <td class="whitespace-nowrap px-4 py-3">${renderStatusBadge(audit.status)}</td>
            </tr>
        `).join('');

        els.findingsList.innerHTML = (state.qms.findings ?? []).map((finding) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(finding.reference)} · ${escapeHtml(finding.finding_type)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(finding.iso_clause)} · ${escapeHtml(finding.description)}</div>
                    </div>
                    ${renderStatusBadge(finding.status)}
                </div>
            </div>
        `).join('') || '<div class="p-4 text-sm text-zinc-500">No audit findings found.</div>';

        els.reviewsList.innerHTML = (state.qms.management_reviews ?? []).map((review) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(review.title)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(review.chair?.name)} · ${escapeHtml(review.review_date)}</div>
                    </div>
                    ${renderStatusBadge(review.status)}
                </div>
            </div>
        `).join('') || '<div class="p-4 text-sm text-zinc-500">No management reviews found.</div>';
    };

    const renderFsms = () => {
        els.fsmsPlansBody.innerHTML = (state.fsms.haccp_plans ?? []).map((plan) => `
            <tr>
                <td class="min-w-72 px-4 py-3">
                    <div class="font-medium">${escapeHtml(plan.name)}</div>
                    <div class="mt-1 text-xs font-medium text-zinc-500">${escapeHtml(plan.product)}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(plan.owner?.name)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml((plan.process_steps ?? []).length)}</td>
                <td class="whitespace-nowrap px-4 py-3">${renderStatusBadge(plan.status)}</td>
            </tr>
        `).join('');

        const controlOptions = [
            ...(state.fsms.ccps ?? []).map((ccp) => ({
                type: 'ccp',
                id: ccp.id,
                label: `CCP - ${ccp.name}`,
            })),
            ...(state.fsms.oprps ?? []).map((oprp) => ({
                type: 'oprp',
                id: oprp.id,
                label: `OPRP - ${oprp.name}`,
            })),
        ];

        els.fsmsMonitorableSelect.innerHTML = controlOptions.map((control) => {
            return `<option value="${control.type}:${control.id}">${escapeHtml(control.label)}</option>`;
        }).join('');

        els.fsmsCcpsBody.innerHTML = (state.fsms.ccps ?? []).map((ccp) => `
            <tr>
                <td class="min-w-72 px-4 py-3">
                    <div class="font-medium">${escapeHtml(ccp.name)}</div>
                    <div class="mt-1 text-xs font-medium text-zinc-500">${escapeHtml(ccp.hazard_analysis?.process_step?.haccp_plan?.name)}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(ccp.critical_limit)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(ccp.monitoring_frequency)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(ccp.responsible_user?.name)}</td>
            </tr>
        `).join('');

        els.fsmsHazardsList.innerHTML = (state.fsms.hazards ?? []).map((hazard) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(hazard.hazard_type)} - ${escapeHtml(hazard.control_type)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(hazard.hazard_description)}</div>
                        <div class="mt-1 text-xs font-medium text-zinc-500">${escapeHtml(hazard.process_step?.name)} - ${escapeHtml(hazard.process_step?.haccp_plan?.product)}</div>
                    </div>
                    ${renderStatusBadge(`Risk ${hazard.risk_score}`)}
                </div>
            </div>
        `).join('') || '<div class="p-4 text-sm text-zinc-500">No hazard analysis found.</div>';

        els.fsmsMonitoringList.innerHTML = (state.fsms.monitoring_records ?? []).map((record) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(record.monitorable?.name)} - ${escapeHtml(record.result)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(record.measured_value ?? '-')} ${escapeHtml(record.unit ?? '')} - ${escapeHtml(record.recorder?.name)}</div>
                        <div class="mt-1 text-xs font-medium text-zinc-500">${escapeHtml(record.observed_at)} ${record.corrective_action ? `- ${escapeHtml(record.corrective_action.title)}` : ''}</div>
                    </div>
                    ${renderStatusBadge(record.is_deviation ? 'Deviation' : 'Pass')}
                </div>
            </div>
        `).join('') || '<div class="p-4 text-sm text-zinc-500">No monitoring records found.</div>';

        els.fsmsPrpsList.innerHTML = (state.fsms.prps ?? []).map((program) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(program.name)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(program.category)} - ${escapeHtml(program.verification_frequency)}</div>
                    </div>
                    ${renderStatusBadge(program.status)}
                </div>
            </div>
        `).join('') || '<div class="p-4 text-sm text-zinc-500">No prerequisite programs found.</div>';
    };

    const renderSupplierQuality = () => {
        els.supplierQualitySuppliersBody.innerHTML = (state.supplierQuality.suppliers ?? []).map((supplier) => `
            <tr>
                <td class="min-w-72 px-4 py-3">
                    <div class="font-medium">${escapeHtml(supplier.name)}</div>
                    <div class="mt-1 text-xs font-medium text-zinc-500">${escapeHtml(supplier.supplier_code)} - ${escapeHtml(supplier.owner?.name)}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(supplier.category)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(supplier.risk_level)}</td>
                <td class="whitespace-nowrap px-4 py-3">${renderStatusBadge(supplier.approval_status)}</td>
            </tr>
        `).join('');

        els.supplierQualitySupplierSelect.innerHTML = (state.supplierQuality.suppliers ?? []).map((supplier) => {
            return `<option value="${supplier.id}">${escapeHtml(supplier.supplier_code)} - ${escapeHtml(supplier.name)}</option>`;
        }).join('');

        els.supplierQualityEquipmentBody.innerHTML = (state.supplierQuality.equipment_assets ?? []).map((asset) => `
            <tr>
                <td class="min-w-72 px-4 py-3">
                    <div class="font-medium">${escapeHtml(asset.asset_tag)}</div>
                    <div class="mt-1 text-xs font-medium text-zinc-500">${escapeHtml(asset.name)}${asset.critical_to_food_safety ? ' - Critical' : ''}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(asset.location)}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(asset.next_calibration_due_at ?? '-')}</td>
                <td class="whitespace-nowrap px-4 py-3">${renderStatusBadge(asset.status)}</td>
            </tr>
        `).join('');

        els.supplierQualityEquipmentSelect.innerHTML = (state.supplierQuality.equipment_assets ?? []).map((asset) => {
            return `<option value="${asset.id}">${escapeHtml(asset.asset_tag)} - ${escapeHtml(asset.name)}</option>`;
        }).join('');

        els.supplierQualityEvaluationsList.innerHTML = (state.supplierQuality.evaluations ?? []).map((evaluation) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(evaluation.supplier?.name)} - score ${escapeHtml(evaluation.score)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(evaluation.evaluator?.name)} - next review ${escapeHtml(evaluation.next_review_date ?? '-')}</div>
                    </div>
                    ${renderStatusBadge(evaluation.result)}
                </div>
            </div>
        `).join('') || '<div class="p-4 text-sm text-zinc-500">No supplier evaluations found.</div>';

        els.supplierQualityCalibrationsList.innerHTML = (state.supplierQuality.calibration_records ?? []).map((record) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(record.equipment_asset?.asset_tag)} - ${escapeHtml(record.equipment_asset?.name)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(record.performed_at)} - next due ${escapeHtml(record.due_at)}</div>
                        <div class="mt-1 text-xs font-medium text-zinc-500">${escapeHtml(record.performer?.name)}${record.corrective_action ? ` - ${escapeHtml(record.corrective_action.title)}` : ''}</div>
                    </div>
                    ${renderStatusBadge(record.result)}
                </div>
            </div>
        `).join('') || '<div class="p-4 text-sm text-zinc-500">No calibration records found.</div>';

        els.supplierQualityCertificatesList.innerHTML = (state.supplierQuality.certificates ?? []).map((certificate) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(certificate.supplier?.name)} - ${escapeHtml(certificate.certificate_type)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(certificate.certificate_number)} - expires ${escapeHtml(certificate.expires_at)}</div>
                    </div>
                    ${renderStatusBadge(certificate.status)}
                </div>
            </div>
        `).join('') || '<div class="p-4 text-sm text-zinc-500">No supplier certificates found.</div>';
    };

    const renderTraining = () => {
        const programOptions = (state.training.programs ?? []).map((program) => {
            return `<option value="${program.id}">${escapeHtml(program.code)} - ${escapeHtml(program.title)}</option>`;
        }).join('');
        const roleOptions = (state.training.roles ?? []).map((role) => {
            return `<option value="${role.id}">${escapeHtml(role.name)}</option>`;
        }).join('');
        const assignmentOptions = (state.training.assignments ?? []).map((assignment) => {
            return `<option value="${assignment.id}">${escapeHtml(assignment.user?.name)} - ${escapeHtml(assignment.training_program?.code)} - ${escapeHtml(assignment.status)}</option>`;
        }).join('');
        const documentOptions = state.documents.map((document) => {
            return `<option value="${document.id}">${escapeHtml(document.document_number)} - ${escapeHtml(document.title)}</option>`;
        }).join('');

        els.trainingProgramSelect.innerHTML = programOptions;
        els.trainingRequirementProgramSelect.innerHTML = programOptions;
        els.trainingRoleSelect.innerHTML = roleOptions;
        els.trainingRequirementRoleSelect.innerHTML = roleOptions;
        els.trainingAssignmentSelect.innerHTML = assignmentOptions;
        els.trainingEvidenceDocumentSelect.innerHTML = `<option value="">No evidence document</option>${documentOptions}`;
        els.trainingAwarenessDocumentSelect.innerHTML = documentOptions;

        els.trainingProgramsBody.innerHTML = (state.training.programs ?? []).map((program) => `
            <tr>
                <td class="min-w-72 px-4 py-3">
                    <div class="font-medium">${escapeHtml(program.code)}</div>
                    <div class="mt-1 text-xs font-medium text-zinc-500">${escapeHtml(program.title)}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(program.iso_clause ?? '-')}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600">${escapeHtml(program.owner?.name)}</td>
                <td class="whitespace-nowrap px-4 py-3">${renderStatusBadge(program.status)}</td>
            </tr>
        `).join('');

        els.trainingAssignmentsList.innerHTML = (state.training.assignments ?? []).map((assignment) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(assignment.user?.name)} - ${escapeHtml(assignment.training_program?.title)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(assignment.required_for_role?.name ?? 'Role optional')} - due ${escapeHtml(assignment.due_date)}</div>
                    </div>
                    ${renderStatusBadge(assignment.status)}
                </div>
            </div>
        `).join('') || '<div class="p-4 text-sm text-zinc-500">No training assignments found.</div>';

        els.trainingRecordsList.innerHTML = (state.training.records ?? []).map((record) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(record.user?.name)} - ${escapeHtml(record.training_program?.title)}</div>
                        <div class="mt-1 text-sm text-zinc-600">Score ${escapeHtml(record.score ?? '-')} - trainer ${escapeHtml(record.trainer?.name)}</div>
                        <div class="mt-1 text-xs font-medium text-zinc-500">${escapeHtml(record.completed_at)}${record.corrective_action ? ` - ${escapeHtml(record.corrective_action.title)}` : ''}</div>
                    </div>
                    ${renderStatusBadge(record.competency_status)}
                </div>
            </div>
        `).join('') || '<div class="p-4 text-sm text-zinc-500">No training records found.</div>';

        els.trainingRequirementsList.innerHTML = (state.training.requirements ?? []).map((requirement) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(requirement.role?.name)} - ${escapeHtml(requirement.competency_area)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(requirement.training_program?.title)} - ${escapeHtml(requirement.assessment_method)}</div>
                    </div>
                    ${renderStatusBadge(requirement.required_level)}
                </div>
            </div>
        `).join('') || '<div class="p-4 text-sm text-zinc-500">No competency requirements found.</div>';

        els.trainingAwarenessList.innerHTML = (state.training.awareness_acknowledgements ?? []).map((acknowledgement) => `
            <div class="p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="font-medium">${escapeHtml(acknowledgement.user?.name)} - ${escapeHtml(acknowledgement.document?.document_number)}</div>
                        <div class="mt-1 text-sm text-zinc-600">${escapeHtml(acknowledgement.document?.title)}</div>
                    </div>
                    ${renderStatusBadge(acknowledgement.status)}
                </div>
            </div>
        `).join('') || '<div class="p-4 text-sm text-zinc-500">No awareness acknowledgements found.</div>';
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
        renderQms();
        renderFsms();
        renderSupplierQuality();
        renderTraining();
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
            qms,
            fsms,
            supplierQuality,
            training,
            correctiveActions,
            tasks,
            auditLogs,
        ] = await Promise.all([
            api(tenantPath('/snapshot')),
            safeFetch(tenantPath('/users')),
            safeFetch(tenantPath('/documents')),
            safeFetch(tenantPath('/document-approvals')),
            safeFetch(tenantPath('/risks')),
            safeFetch(tenantPath('/qms'), { objectives: [], audits: [], findings: [], management_reviews: [] }),
            safeFetch(tenantPath('/fsms'), { haccp_plans: [], hazards: [], ccps: [], oprps: [], prps: [], monitoring_records: [] }),
            safeFetch(tenantPath('/supplier-quality'), { suppliers: [], evaluations: [], certificates: [], equipment_assets: [], calibration_records: [] }),
            safeFetch(tenantPath('/training'), { programs: [], requirements: [], assignments: [], records: [], awareness_acknowledgements: [], roles: [] }),
            safeFetch(tenantPath('/corrective-actions')),
            safeFetch(tenantPath('/workflow-tasks')),
            safeFetch(tenantPath('/audit-logs')),
        ]);

        state.snapshot = snapshot;
        state.users = users;
        state.documents = documents;
        state.approvals = approvals;
        state.risks = risks;
        state.qms = qms;
        state.fsms = fsms;
        state.supplierQuality = supplierQuality;
        state.training = training;
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

    els.objectiveForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.objectiveForm);

        const payload = {
            title: form.get('title'),
            measurement_method: form.get('measurement_method'),
            target_value: Number(form.get('target_value')),
            unit: form.get('unit') || '%',
            owner_id: Number(form.get('owner_id')),
            due_date: form.get('due_date') || null,
            status: 'Active',
        };

        if (form.get('baseline_value')) {
            payload.baseline_value = Number(form.get('baseline_value'));
        }

        if (form.get('current_value')) {
            payload.current_value = Number(form.get('current_value'));
        }

        try {
            await api(tenantPath('/qms/objectives'), {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            els.objectiveForm.reset();
            await loadWorkspace();
            showStatus('Quality objective created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.auditForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.auditForm);

        try {
            await api(tenantPath('/qms/audits'), {
                method: 'POST',
                body: JSON.stringify({
                    title: form.get('title'),
                    scope: form.get('scope'),
                    lead_auditor_id: Number(form.get('lead_auditor_id')),
                    scheduled_date: form.get('scheduled_date'),
                    status: 'Planned',
                }),
            });
            els.auditForm.reset();
            await loadWorkspace();
            showStatus('Audit created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.haccpPlanForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.haccpPlanForm);

        try {
            await api(tenantPath('/fsms/haccp-plans'), {
                method: 'POST',
                body: JSON.stringify({
                    name: form.get('name'),
                    product: form.get('product'),
                    scope: form.get('scope'),
                    owner_id: Number(form.get('owner_id')),
                    effective_date: form.get('effective_date') || null,
                    status: 'Draft',
                }),
            });
            els.haccpPlanForm.reset();
            await loadWorkspace();
            showStatus('HACCP plan created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.monitoringForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.monitoringForm);
        const [monitorableType, monitorableId] = String(form.get('monitorable_id') ?? '').split(':');
        const measuredValue = form.get('measured_value');
        const observedAt = form.get('observed_at')
            ? new Date(form.get('observed_at')).toISOString()
            : new Date().toISOString();

        const payload = {
            monitorable_type: monitorableType,
            monitorable_id: Number(monitorableId),
            recorded_by_id: Number(form.get('recorded_by_id')),
            unit: form.get('unit') || null,
            result: form.get('result'),
            is_deviation: form.get('is_deviation') === 'on',
            observed_at: observedAt,
            notes: form.get('notes') || null,
        };

        if (measuredValue !== '') {
            payload.measured_value = Number(measuredValue);
        }

        try {
            await api(tenantPath('/fsms/monitoring-records'), {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            els.monitoringForm.reset();
            await loadWorkspace();
            showStatus(payload.is_deviation ? 'Deviation CAPA opened.' : 'Monitoring record created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.supplierForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.supplierForm);

        try {
            await api(tenantPath('/supplier-quality/suppliers'), {
                method: 'POST',
                body: JSON.stringify({
                    name: form.get('name'),
                    supplier_code: form.get('supplier_code'),
                    category: form.get('category'),
                    contact_email: form.get('contact_email') || null,
                    owner_id: Number(form.get('owner_id')),
                    approval_status: 'Pending',
                    risk_level: 'Medium',
                }),
            });
            els.supplierForm.reset();
            await loadWorkspace();
            showStatus('Supplier created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.supplierEvaluationForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.supplierEvaluationForm);
        const supplierId = Number(form.get('supplier_id'));

        try {
            await api(tenantPath(`/supplier-quality/suppliers/${supplierId}/evaluations`), {
                method: 'POST',
                body: JSON.stringify({
                    evaluated_by_id: Number(form.get('evaluated_by_id')),
                    evaluation_date: form.get('evaluation_date'),
                    score: Number(form.get('score')),
                    result: form.get('result'),
                    next_review_date: form.get('next_review_date') || null,
                    notes: form.get('notes') || null,
                }),
            });
            els.supplierEvaluationForm.reset();
            await loadWorkspace();
            showStatus('Supplier evaluation recorded.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.equipmentForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.equipmentForm);

        try {
            await api(tenantPath('/supplier-quality/equipment'), {
                method: 'POST',
                body: JSON.stringify({
                    asset_tag: form.get('asset_tag'),
                    name: form.get('name'),
                    location: form.get('location'),
                    owner_id: Number(form.get('owner_id')),
                    calibration_interval_days: Number(form.get('calibration_interval_days')),
                    critical_to_food_safety: form.get('critical_to_food_safety') === 'on',
                    status: 'Active',
                }),
            });
            els.equipmentForm.reset();
            await loadWorkspace();
            showStatus('Equipment created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.calibrationForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.calibrationForm);
        const equipmentId = Number(form.get('equipment_asset_id'));
        const result = form.get('result');

        try {
            await api(tenantPath(`/supplier-quality/equipment/${equipmentId}/calibrations`), {
                method: 'POST',
                body: JSON.stringify({
                    performed_by_id: Number(form.get('performed_by_id')),
                    performed_at: form.get('performed_at'),
                    due_at: form.get('due_at'),
                    result,
                    certificate_number: form.get('certificate_number') || null,
                    notes: form.get('notes') || null,
                }),
            });
            els.calibrationForm.reset();
            await loadWorkspace();
            showStatus(result === 'Fail' || result === 'Overdue' ? 'Calibration CAPA opened.' : 'Calibration recorded.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.trainingProgramForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.trainingProgramForm);
        const refresherDays = form.get('refresher_interval_days');

        const payload = {
            code: form.get('code'),
            title: form.get('title'),
            iso_clause: form.get('iso_clause') || null,
            delivery_method: form.get('delivery_method') || 'Classroom',
            owner_id: Number(form.get('owner_id')),
            status: 'Active',
        };

        if (refresherDays !== '') {
            payload.refresher_interval_days = Number(refresherDays);
        }

        try {
            await api(tenantPath('/training/programs'), {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            els.trainingProgramForm.reset();
            await loadWorkspace();
            showStatus('Training program created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.trainingRequirementForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.trainingRequirementForm);

        try {
            await api(tenantPath('/training/requirements'), {
                method: 'POST',
                body: JSON.stringify({
                    role_id: Number(form.get('role_id')),
                    training_program_id: Number(form.get('training_program_id')),
                    competency_area: form.get('competency_area'),
                    required_level: form.get('required_level') || 'Qualified',
                    assessment_method: form.get('assessment_method') || 'Supervisor verification',
                    due_within_days: Number(form.get('due_within_days') || 30),
                    is_mandatory: true,
                }),
            });
            els.trainingRequirementForm.reset();
            await loadWorkspace();
            showStatus('Competency requirement created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.trainingAssignmentForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.trainingAssignmentForm);
        const programId = Number(form.get('training_program_id'));

        try {
            await api(tenantPath(`/training/programs/${programId}/assignments`), {
                method: 'POST',
                body: JSON.stringify({
                    user_id: Number(form.get('user_id')),
                    assigned_by_id: state.user?.id,
                    required_for_role_id: Number(form.get('required_for_role_id')),
                    due_date: form.get('due_date'),
                    notes: form.get('notes') || null,
                }),
            });
            els.trainingAssignmentForm.reset();
            await loadWorkspace();
            showStatus('Training assignment created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.trainingRecordForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.trainingRecordForm);
        const assignmentId = Number(form.get('training_assignment_id'));
        const score = form.get('score');
        const evidenceDocumentId = form.get('evidence_document_id');

        const payload = {
            trainer_id: Number(form.get('trainer_id')),
            completed_at: form.get('completed_at'),
            result: form.get('result'),
            competency_status: form.get('competency_status'),
            expires_at: form.get('expires_at') || null,
            notes: form.get('notes') || null,
        };

        if (score !== '') {
            payload.score = Number(score);
        }

        if (evidenceDocumentId) {
            payload.evidence_document_id = Number(evidenceDocumentId);
        }

        try {
            await api(tenantPath(`/training/assignments/${assignmentId}/records`), {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            els.trainingRecordForm.reset();
            await loadWorkspace();
            showStatus(payload.result === 'Fail' || payload.competency_status === 'Needs Coaching' ? 'Competency CAPA opened.' : 'Training record created.');
        } catch (error) {
            showStatus(error.message, 'error');
        }
    });

    els.trainingAwarenessForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(els.trainingAwarenessForm);
        const acknowledgedAt = form.get('acknowledged_at')
            ? new Date(form.get('acknowledged_at')).toISOString()
            : new Date().toISOString();

        try {
            await api(tenantPath('/training/awareness-acknowledgements'), {
                method: 'POST',
                body: JSON.stringify({
                    document_id: Number(form.get('document_id')),
                    user_id: Number(form.get('user_id')),
                    acknowledged_by_id: state.user?.id,
                    acknowledged_at: acknowledgedAt,
                    statement: form.get('statement') || null,
                }),
            });
            els.trainingAwarenessForm.reset();
            await loadWorkspace();
            showStatus('Awareness acknowledgement recorded.');
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
