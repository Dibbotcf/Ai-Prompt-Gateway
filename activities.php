<?php
/**
 * AI Prompt Security Gateway — Activity History
 */
$pageTitle = 'Activity History';
require_once __DIR__ . '/includes/header.php';
?>

<div class="glass-card mb-4">
    <div class="d-flex flex-wrap gap-3 align-items-center">
        <select class="form-select-custom" id="filterStatus" onchange="loadActivities()">
            <option value="">All Status</option>
            <option value="closed" selected>Closed</option>
            <option value="open">Open</option>
        </select>
        <input type="text" class="form-control-custom" id="searchActivities" placeholder="Search activities..." oninput="loadActivities()" style="flex:1;min-width:200px">
    </div>
</div>

<div id="activitiesContainer">
    <div class="glass-card">
        <div class="empty-state">
            <div class="spinner-custom mx-auto"></div>
            <p class="mt-3">Loading activities...</p>
        </div>
    </div>
</div>

<!-- Activity Detail Modal -->
<div class="modal fade" id="activityDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-folder-open" style="color:var(--accent-primary)"></i> <span id="modalActivityName">Activity</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="activityDetailBody" style="max-height:70vh;overflow-y:auto">
                <div class="spinner-custom mx-auto my-4"></div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', loadActivities);

async function loadActivities() {
    const container = document.getElementById('activitiesContainer');
    const status = document.getElementById('filterStatus').value;
    const search = document.getElementById('searchActivities').value;

    try {
        const data = await api.get('api/activities.php', { status: status });
        if (!data.success) throw new Error(data.message);

        let activities = data.activities;
        if (search) {
            activities = activities.filter(a => a.name.toLowerCase().includes(search.toLowerCase()));
        }

        if (activities.length === 0) {
            container.innerHTML = `
                <div class="glass-card">
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h4>No activities found</h4>
                        <p>Go to the <a href="testbench.php" style="color:var(--accent-primary)">Test Bench</a> to start an activity.</p>
                    </div>
                </div>`;
            return;
        }

        container.innerHTML = `<div class="row g-3">
            ${activities.map(a => {
                const statusColor = a.status === 'open' ? 'var(--success)' : 'var(--text-muted)';
                const riskColor = getRiskColor(parseFloat(a.avg_risk_score));
                const userModelNames = {custom:'Custom',chatgpt:'ChatGPT',gemini:'Gemini',claude:'Claude',copilot:'Copilot',other:'Other'};
                const destModelNames = {simulated:'Simulated',gemini:'Gemini',openai:'OpenAI',groq:'Groq'};

                return `
                <div class="col-lg-4 col-md-6">
                    <div class="glass-card activity-card" style="cursor:pointer" onclick="viewActivity(${a.id})">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4 style="font-size:15px;font-weight:700;margin:0">${escapeHtml(a.name)}</h4>
                                <span style="font-size:11px;color:var(--text-muted)">${formatDate(a.created_at)}</span>
                            </div>
                            <span style="font-size:10px;font-weight:600;padding:4px 10px;border-radius:12px;background:${a.status === 'open' ? 'var(--success-bg)' : 'rgba(255,255,255,0.05)'};color:${statusColor};text-transform:uppercase">
                                ${a.status}
                            </span>
                        </div>

                        <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px">
                            ${userModelNames[a.user_model] || a.user_model} → ${destModelNames[a.destination_model] || a.destination_model}
                        </div>

                        <div class="d-flex gap-3" style="font-size:13px">
                            <div><span class="mono" style="font-weight:700;color:var(--text-primary)">${a.total_prompts}</span> <span style="color:var(--text-muted);font-size:11px">prompts</span></div>
                            <div><span class="mono" style="font-weight:700;color:var(--danger)">${a.blocked_prompts}</span> <span style="color:var(--text-muted);font-size:11px">blocked</span></div>
                            <div><span class="mono" style="font-weight:700;color:${riskColor}">${parseFloat(a.avg_risk_score).toFixed(0)}</span> <span style="color:var(--text-muted);font-size:11px">avg risk</span></div>
                        </div>

                        ${a.description ? `<div style="font-size:11px;color:var(--text-muted);margin-top:8px;font-style:italic">${escapeHtml(a.description)}</div>` : ''}
                    </div>
                </div>`;
            }).join('')}
        </div>`;

    } catch(e) {
        container.innerHTML = `<div class="glass-card"><div class="empty-state">
            <i class="fas fa-exclamation-circle" style="color:var(--danger)"></i>
            <h4>Failed to load activities</h4>
            <p>${e.message}</p>
        </div></div>`;
    }
}

async function viewActivity(id) {
    const body = document.getElementById('activityDetailBody');
    body.innerHTML = '<div class="spinner-custom mx-auto my-4"></div>';
    document.getElementById('modalActivityName').textContent = 'Loading...';
    new bootstrap.Modal(document.getElementById('activityDetailModal')).show();

    try {
        const data = await api.get(`api/activities.php?id=${id}`);
        if (!data.success) throw new Error(data.message);
        const a = data.activity;
        document.getElementById('modalActivityName').textContent = a.name;

        const prompts = a.prompts || [];

        body.innerHTML = `
            <div class="result-grid mb-4">
                <div class="result-item"><div class="result-item-label">Status</div><div class="result-item-value" style="color:${a.status === 'open' ? 'var(--success)' : 'var(--text-muted)'}">${a.status.toUpperCase()}</div></div>
                <div class="result-item"><div class="result-item-label">Total Prompts</div><div class="result-item-value mono">${a.total_prompts}</div></div>
                <div class="result-item"><div class="result-item-label">Blocked</div><div class="result-item-value mono" style="color:var(--danger)">${a.blocked_prompts}</div></div>
                <div class="result-item"><div class="result-item-label">Avg Risk</div><div class="result-item-value mono" style="color:${getRiskColor(parseFloat(a.avg_risk_score))}">${parseFloat(a.avg_risk_score).toFixed(1)}</div></div>
            </div>

            <h5 style="font-size:14px;font-weight:600;margin-bottom:12px"><i class="fas fa-comments" style="color:var(--accent-primary)"></i> Conversation Log</h5>

            ${prompts.length === 0 ? '<div style="color:var(--text-muted);text-align:center;padding:20px">No prompts in this activity</div>' : 
                prompts.map((p, i) => {
                    const gaugeColor = getRiskColor(p.risk_score);
                    return `
                    <div style="margin-bottom:16px;padding:16px;background:rgba(0,0,0,0.2);border-radius:12px;border:1px solid var(--bg-glass-border)">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span style="font-weight:600;font-size:13px">Prompt #${p.sequence_order}</span>
                            <div class="d-flex gap-2 align-items-center">
                                ${getVerdictBadge(p.verdict)}
                                <span class="mono" style="font-weight:700;color:${gaugeColor}">${p.risk_score}</span>
                            </div>
                        </div>
                        <div style="background:rgba(0,0,0,0.2);border-radius:8px;padding:12px;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text-secondary);margin-bottom:8px">${escapeHtml(p.prompt_text)}</div>
                        ${p.ai_response ? `
                            <div style="font-size:11px;font-weight:600;color:var(--accent-secondary);margin-bottom:4px">
                                <i class="fas fa-robot"></i> AI Response (${p.ai_model_used || 'unknown'})
                            </div>
                            <div style="background:rgba(99,102,241,0.05);border-radius:8px;padding:12px;font-size:13px;color:var(--text-secondary);white-space:pre-wrap">${escapeHtml(p.ai_response)}</div>
                        ` : ''}
                    </div>`;
                }).join('')
            }

            <div style="font-size:11px;color:var(--text-muted);margin-top:12px">
                Created: ${formatDate(a.created_at)} ${a.closed_at ? '&middot; Closed: ' + formatDate(a.closed_at) : ''} &middot; ID: ${a.id}
            </div>
        `;
    } catch(e) {
        body.innerHTML = '<div style="color:var(--danger);text-align:center;padding:20px">Failed to load activity details</div>';
    }
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
