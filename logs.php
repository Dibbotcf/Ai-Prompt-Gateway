<?php
/**
 * AI Prompt Security Gateway — Logs & Analytics
 */
$pageTitle = 'Logs & Analytics';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Filters -->
<div class="glass-card mb-4">
    <div class="d-flex flex-wrap gap-3 align-items-end">
        <div>
            <label style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;display:block">Verdict</label>
            <select class="form-select-custom" id="filterVerdict" onchange="loadLogs()">
                <option value="">All</option>
                <option value="safe">Safe</option>
                <option value="suspicious">Suspicious</option>
                <option value="blocked">Blocked</option>
            </select>
        </div>
        <div>
            <label style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;display:block">Category</label>
            <select class="form-select-custom" id="filterCat" onchange="loadLogs()">
                <option value="">All</option>
                <option value="jailbreak">Jailbreak</option>
                <option value="pii-exposure">PII Exposure</option>
                <option value="harmful-intent">Harmful Intent</option>
                <option value="system-override">System Override</option>
                <option value="social-engineering">Social Engineering</option>
            </select>
        </div>
        <div>
            <label style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;display:block">From</label>
            <input type="date" class="form-control-custom" id="filterDateFrom" onchange="loadLogs()">
        </div>
        <div>
            <label style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;display:block">To</label>
            <input type="date" class="form-control-custom" id="filterDateTo" onchange="loadLogs()">
        </div>
        <div style="flex:1;min-width:200px">
            <label style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;display:block">Search</label>
            <input type="text" class="form-control-custom w-100" id="filterSearch" placeholder="Search prompts..." oninput="debounceLoadLogs()">
        </div>
        <div>
            <button class="btn-glass" onclick="exportLogs()">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>
    </div>
</div>

<!-- Logs Table -->
<div class="glass-card">
    <div id="logs-container">
        <div class="empty-state">
            <div class="spinner-custom mx-auto"></div>
            <p class="mt-3">Loading logs...</p>
        </div>
    </div>
</div>

<!-- Log Detail Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-alt" style="color:var(--accent-primary)"></i> Log Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailBody">
                <div class="spinner-custom mx-auto"></div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
let currentPage = 0;
const PAGE_SIZE = 25;
let debounceTimer;

document.addEventListener('DOMContentLoaded', loadLogs);

function debounceLoadLogs() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadLogs, 300);
}

async function loadLogs(page = 0) {
    currentPage = page;
    const container = document.getElementById('logs-container');
    
    try {
        const data = await api.get('api/logs.php', {
            verdict: document.getElementById('filterVerdict').value,
            category: document.getElementById('filterCat').value,
            date_from: document.getElementById('filterDateFrom').value,
            date_to: document.getElementById('filterDateTo').value,
            search: document.getElementById('filterSearch').value,
            limit: PAGE_SIZE,
            offset: page * PAGE_SIZE,
        });

        if (!data.success) throw new Error(data.message);

        const logs = data.logs;
        const total = data.total;
        const totalPages = Math.ceil(total / PAGE_SIZE);

        if (logs.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-scroll"></i>
                    <h4>No logs found</h4>
                    <p>Try adjusting your filters or analyze some prompts in the Test Bench.</p>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div style="overflow-x:auto">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Prompt</th>
                        <th>Risk</th>
                        <th>Verdict</th>
                        <th>Categories</th>
                        <th>Rules</th>
                        <th>Source</th>
                        <th>Time</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    ${logs.map(l => `
                        <tr>
                            <td class="mono" style="font-size:11px;color:var(--text-muted)">${l.id}</td>
                            <td style="max-width:250px;color:var(--text-primary)" class="truncate" title="${escapeHtml(l.prompt_text)}">${escapeHtml(truncateText(l.prompt_text, 60))}</td>
                            <td>
                                <span class="mono" style="font-weight:700;color:${getRiskColor(l.risk_score)}">${l.risk_score}</span>
                            </td>
                            <td>${getVerdictBadge(l.verdict)}</td>
                            <td style="font-size:11px">${l.categories_matched ? l.categories_matched.split(',').map(c => `<span class="category-tag" style="background:rgba(99,102,241,0.1);color:var(--accent-primary)">${c}</span>`).join(' ') : '—'}</td>
                            <td class="mono">${l.matched_rules_count}</td>
                            <td style="font-size:11px">${l.source}</td>
                            <td style="font-size:11px;white-space:nowrap">${formatDate(l.created_at)}</td>
                            <td>
                                <button class="btn-glass" style="padding:4px 8px;font-size:11px" onclick="viewLogDetail(${l.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <span style="font-size:13px;color:var(--text-muted)">
                    Showing ${page * PAGE_SIZE + 1}–${Math.min((page + 1) * PAGE_SIZE, total)} of ${total}
                </span>
                <nav>
                    <ul class="pagination pagination-custom mb-0">
                        <li class="page-item ${page === 0 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="loadLogs(${page - 1});return false">‹</a>
                        </li>
                        ${Array.from({length: Math.min(totalPages, 5)}, (_, i) => {
                            const p = page < 3 ? i : page - 2 + i;
                            if (p >= totalPages) return '';
                            return `<li class="page-item ${p === page ? 'active' : ''}">
                                <a class="page-link" href="#" onclick="loadLogs(${p});return false">${p + 1}</a>
                            </li>`;
                        }).join('')}
                        <li class="page-item ${page >= totalPages - 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" onclick="loadLogs(${page + 1});return false">›</a>
                        </li>
                    </ul>
                </nav>
            </div>
        `;
    } catch(e) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-exclamation-circle" style="color:var(--danger)"></i>
                <h4>Failed to load logs</h4>
                <p>${e.message || 'Check database connection'}</p>
            </div>
        `;
    }
}

async function viewLogDetail(id) {
    const body = document.getElementById('logDetailBody');
    body.innerHTML = '<div class="spinner-custom mx-auto my-4"></div>';
    new bootstrap.Modal(document.getElementById('logDetailModal')).show();

    try {
        const data = await api.get(`api/logs.php?id=${id}`);
        if (!data.success) throw new Error(data.message);
        const l = data.log;
        const gaugeColor = getRiskColor(l.risk_score);

        body.innerHTML = `
            <div class="result-grid mb-4">
                <div class="result-item">
                    <div class="result-item-label">Risk Score</div>
                    <div class="result-item-value mono" style="font-size:28px;color:${gaugeColor}">${l.risk_score}</div>
                </div>
                <div class="result-item">
                    <div class="result-item-label">Verdict</div>
                    <div class="result-item-value">${getVerdictBadge(l.verdict)}</div>
                </div>
                <div class="result-item">
                    <div class="result-item-label">Analysis Time</div>
                    <div class="result-item-value mono">${l.analysis_time_ms} ms</div>
                </div>
                <div class="result-item">
                    <div class="result-item-label">Source</div>
                    <div class="result-item-value">${l.source}</div>
                </div>
            </div>

            <h6 style="font-weight:600;margin-bottom:8px"><i class="fas fa-keyboard" style="color:var(--accent-primary)"></i> Prompt Text</h6>
            <div style="background:rgba(0,0,0,0.3);border:1px solid var(--bg-glass-border);border-radius:8px;padding:16px;font-family:'JetBrains Mono',monospace;font-size:13px;margin-bottom:20px;white-space:pre-wrap;word-break:break-word">
${escapeHtml(l.prompt_text)}
            </div>

            ${l.sanitized_text ? `
                <h6 style="font-weight:600;margin-bottom:8px"><i class="fas fa-broom" style="color:var(--info)"></i> Sanitized Text</h6>
                <div style="background:rgba(0,0,0,0.3);border:1px solid rgba(90,200,250,0.15);border-radius:8px;padding:16px;font-family:'JetBrains Mono',monospace;font-size:13px;margin-bottom:20px;white-space:pre-wrap;word-break:break-word">
${escapeHtml(l.sanitized_text)}
                </div>
            ` : ''}

            ${l.rule_matches && l.rule_matches.length > 0 ? `
                <h6 style="font-weight:600;margin-bottom:12px"><i class="fas fa-exclamation-triangle" style="color:var(--warning)"></i> Matched Rules (${l.rule_matches.length})</h6>
                ${l.rule_matches.map(rm => `
                    <div class="matched-rule severity-${rm.severity}" style="margin-bottom:8px">
                        <div>
                            <div class="matched-rule-name">${escapeHtml(rm.rule_name)}</div>
                            <div class="d-flex gap-2 mt-1">
                                ${getSeverityBadge(rm.severity)}
                                <span class="category-tag" style="background:${rm.category_color}20;color:${rm.category_color}">${escapeHtml(rm.category_name)}</span>
                            </div>
                            ${rm.matched_text ? `<div class="matched-rule-matched">Matched: "${escapeHtml(rm.matched_text)}"</div>` : ''}
                        </div>
                    </div>
                `).join('')}
            ` : ''}

            <div class="mt-3" style="font-size:11px;color:var(--text-muted)">
                Logged at: ${formatDate(l.created_at)} &middot; IP: ${l.ip_address || 'N/A'} &middot; ID: ${l.id}
            </div>
        `;
    } catch(e) {
        body.innerHTML = `<div class="text-center py-4" style="color:var(--danger)">Failed to load log detail</div>`;
    }
}

function exportLogs() {
    const params = new URLSearchParams({
        verdict: document.getElementById('filterVerdict').value,
        category: document.getElementById('filterCat').value,
        date_from: document.getElementById('filterDateFrom').value,
        date_to: document.getElementById('filterDateTo').value,
        search: document.getElementById('filterSearch').value,
        limit: 10000,
        offset: 0,
    });
    
    api.get('api/logs.php?' + params.toString()).then(data => {
        if (!data.success || !data.logs.length) {
            showToast('No data to export', 'info');
            return;
        }
        
        const headers = ['ID','Prompt','Risk Score','Verdict','Categories','Rules Matched','Source','Created At'];
        const csvRows = [headers.join(',')];
        data.logs.forEach(l => {
            csvRows.push([
                l.id,
                '"' + (l.prompt_text || '').replace(/"/g, '""') + '"',
                l.risk_score,
                l.verdict,
                '"' + (l.categories_matched || '') + '"',
                l.matched_rules_count,
                l.source,
                l.created_at
            ].join(','));
        });
        
        const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'prompt_logs_' + new Date().toISOString().split('T')[0] + '.csv';
        a.click();
        showToast('Logs exported successfully');
    });
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
