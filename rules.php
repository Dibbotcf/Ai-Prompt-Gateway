<?php
/**
 * AI Prompt Security Gateway — Rules Management
 */
$pageTitle = 'Rules Engine';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div class="d-flex gap-2 flex-wrap">
        <select class="form-select-custom" id="filterCategory" onchange="filterRules()">
            <option value="">All Categories</option>
        </select>
        <select class="form-select-custom" id="filterSeverity" onchange="filterRules()">
            <option value="">All Severities</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
        </select>
        <input type="text" class="form-control-custom" id="searchRules" placeholder="Search rules..." oninput="filterRules()" style="width:200px">
    </div>
    <button class="btn-gradient" onclick="showAddRuleModal()">
        <i class="fas fa-plus"></i> Add Rule
    </button>
</div>

<div class="glass-card">
    <div id="rules-table-container">
        <div class="empty-state">
            <div class="spinner-custom mx-auto"></div>
            <p class="mt-3">Loading rules...</p>
        </div>
    </div>
</div>

<!-- Add/Edit Rule Modal -->
<div class="modal fade" id="ruleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-gavel" style="color:var(--accent-primary)"></i> <span id="modalTitle">Add Rule</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ruleId">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block">Rule Name</label>
                        <input type="text" class="form-control-custom w-100" id="ruleName" placeholder="e.g., Custom Jailbreak Detection">
                    </div>
                    <div class="col-md-4">
                        <label style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block">Category</label>
                        <select class="form-select-custom w-100" id="ruleCategory"></select>
                    </div>
                    <div class="col-12">
                        <label style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block">Description</label>
                        <input type="text" class="form-control-custom w-100" id="ruleDescription" placeholder="Describe what this rule detects">
                    </div>
                    <div class="col-md-8">
                        <label style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block">Pattern</label>
                        <textarea class="form-control-custom w-100" id="rulePattern" rows="3" placeholder="Regex: /pattern/i or Keywords: word1, word2" style="font-family:'JetBrains Mono',monospace;font-size:13px"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block">Pattern Type</label>
                        <select class="form-select-custom w-100" id="rulePatternType">
                            <option value="regex">Regex</option>
                            <option value="keyword">Keyword</option>
                            <option value="phrase">Phrase</option>
                        </select>
                        <label style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin:12px 0 6px;display:block">Severity</label>
                        <select class="form-select-custom w-100" id="ruleSeverity">
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium" selected>Medium</option>
                            <option value="low">Low</option>
                        </select>
                        <label style="font-size:12px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin:12px 0 6px;display:block">Score (0-100)</label>
                        <input type="number" class="form-control-custom w-100" id="ruleScore" value="50" min="0" max="100">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-glass" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-gradient" onclick="saveRule()">
                    <i class="fas fa-save"></i> Save Rule
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
let allRules = [];
let allCategories = [];

document.addEventListener('DOMContentLoaded', loadRules);

async function loadRules() {
    try {
        const data = await api.get('api/rules.php');
        if (data.success) {
            allRules = data.rules;
            allCategories = data.categories;
            populateCategoryFilters();
            renderRulesTable(allRules);
        }
    } catch(e) {
        document.getElementById('rules-table-container').innerHTML = `
            <div class="empty-state">
                <i class="fas fa-exclamation-circle" style="color:var(--danger)"></i>
                <h4>Failed to load rules</h4>
                <p>Make sure the database is set up correctly.</p>
            </div>
        `;
    }
}

function populateCategoryFilters() {
    const options = allCategories.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    document.getElementById('filterCategory').innerHTML = '<option value="">All Categories</option>' + options;
    document.getElementById('ruleCategory').innerHTML = options;
}

function renderRulesTable(rules) {
    const container = document.getElementById('rules-table-container');
    
    if (rules.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-gavel"></i>
                <h4>No rules found</h4>
                <p>Create your first detection rule to get started.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = `
        <div style="overflow-x:auto">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Rule Name</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Severity</th>
                    <th>Score</th>
                    <th>Hits</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                ${rules.map(r => `
                    <tr style="opacity:${r.is_active ? 1 : 0.5}">
                        <td>
                            <label class="toggle-switch">
                                <input type="checkbox" ${r.is_active ? 'checked' : ''} onchange="toggleRule(${r.id})">
                                <span class="toggle-slider"></span>
                            </label>
                        </td>
                        <td style="color:var(--text-primary);font-weight:500;max-width:200px" class="truncate">${escapeHtml(r.name)}</td>
                        <td><span class="category-tag" style="background:${r.category_color}20;color:${r.category_color}">${escapeHtml(r.category_name)}</span></td>
                        <td><span class="mono" style="font-size:11px">${r.pattern_type}</span></td>
                        <td>${getSeverityBadge(r.severity)}</td>
                        <td><span class="mono">${r.severity_score}</span></td>
                        <td><span class="mono" style="color:var(--accent-primary)">${r.match_count}</span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn-glass" style="padding:4px 8px;font-size:12px" onclick="editRule(${r.id})" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button class="btn-glass" style="padding:4px 8px;font-size:12px;color:var(--danger)" onclick="deleteRule(${r.id})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3" style="font-size:13px;color:var(--text-muted)">
            <span>Showing ${rules.length} rules</span>
            <span>Active: ${rules.filter(r => r.is_active).length} / ${rules.length}</span>
        </div>
    `;
}

function filterRules() {
    const cat = document.getElementById('filterCategory').value;
    const sev = document.getElementById('filterSeverity').value;
    const search = document.getElementById('searchRules').value.toLowerCase();

    let filtered = allRules;
    if (cat) filtered = filtered.filter(r => r.category_id == cat);
    if (sev) filtered = filtered.filter(r => r.severity === sev);
    if (search) filtered = filtered.filter(r => 
        r.name.toLowerCase().includes(search) || 
        (r.description && r.description.toLowerCase().includes(search))
    );

    renderRulesTable(filtered);
}

function showAddRuleModal() {
    document.getElementById('modalTitle').textContent = 'Add Rule';
    document.getElementById('ruleId').value = '';
    document.getElementById('ruleName').value = '';
    document.getElementById('ruleDescription').value = '';
    document.getElementById('rulePattern').value = '';
    document.getElementById('rulePatternType').value = 'regex';
    document.getElementById('ruleSeverity').value = 'medium';
    document.getElementById('ruleScore').value = 50;
    new bootstrap.Modal(document.getElementById('ruleModal')).show();
}

async function editRule(id) {
    try {
        const data = await api.get(`api/rules.php?id=${id}`);
        if (data.success) {
            const r = data.rule;
            document.getElementById('modalTitle').textContent = 'Edit Rule';
            document.getElementById('ruleId').value = r.id;
            document.getElementById('ruleName').value = r.name;
            document.getElementById('ruleDescription').value = r.description || '';
            document.getElementById('ruleCategory').value = r.category_id;
            document.getElementById('rulePattern').value = r.pattern;
            document.getElementById('rulePatternType').value = r.pattern_type;
            document.getElementById('ruleSeverity').value = r.severity;
            document.getElementById('ruleScore').value = r.severity_score;
            new bootstrap.Modal(document.getElementById('ruleModal')).show();
        }
    } catch(e) {
        showToast('Failed to load rule', 'error');
    }
}

async function saveRule() {
    const id = document.getElementById('ruleId').value;
    const data = {
        name: document.getElementById('ruleName').value,
        description: document.getElementById('ruleDescription').value,
        category_id: document.getElementById('ruleCategory').value,
        pattern: document.getElementById('rulePattern').value,
        pattern_type: document.getElementById('rulePatternType').value,
        severity: document.getElementById('ruleSeverity').value,
        severity_score: parseInt(document.getElementById('ruleScore').value),
        is_active: 1
    };

    if (!data.name || !data.pattern) {
        showToast('Name and pattern are required', 'error');
        return;
    }

    try {
        let result;
        if (id) {
            result = await api.put(`api/rules.php?id=${id}`, data);
        } else {
            result = await api.post('api/rules.php', data);
        }

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('ruleModal')).hide();
            showToast(id ? 'Rule updated successfully' : 'Rule created successfully');
            loadRules();
        } else {
            showToast(result.message || 'Failed to save rule', 'error');
        }
    } catch(e) {
        showToast('Failed to save rule', 'error');
    }
}

async function toggleRule(id) {
    try {
        await api.patch(`api/rules.php?id=${id}`);
        showToast('Rule status toggled');
        loadRules();
    } catch(e) {
        showToast('Failed to toggle rule', 'error');
    }
}

async function deleteRule(id) {
    if (!confirm('Are you sure you want to delete this rule? This cannot be undone.')) return;
    try {
        await api.delete(`api/rules.php?id=${id}`);
        showToast('Rule deleted');
        loadRules();
    } catch(e) {
        showToast('Failed to delete rule', 'error');
    }
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
