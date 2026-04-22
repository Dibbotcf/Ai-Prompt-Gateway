<?php
/**
 * AI Prompt Security Gateway — Architecture & Design Documentation
 */
$pageTitle = 'Architecture & Design';
require_once __DIR__ . '/includes/header.php';
?>

<!-- System Architecture -->
<div class="glass-card mb-4">
    <div class="card-header-custom">
        <h3 class="card-title"><i class="fas fa-project-diagram"></i> System Architecture</h3>
    </div>
    
    <div class="arch-diagram">
        <div class="arch-flow">
            <div class="arch-node user"><i class="fas fa-user"></i> User Prompt</div>
            <div class="arch-arrow">→</div>
            <div class="arch-node gateway"><i class="fas fa-shield-halved"></i> Security Gateway</div>
            <div class="arch-arrow">→</div>
            <div class="arch-node engine"><i class="fas fa-cogs"></i> Analysis Engine</div>
            <div class="arch-arrow">→</div>
            <div class="arch-node db"><i class="fas fa-database"></i> Rule Matching</div>
        </div>
        <div class="arch-flow mt-3">
            <div class="arch-node db"><i class="fas fa-database"></i> Rule Matching</div>
            <div class="arch-arrow">→</div>
            <div class="arch-node engine"><i class="fas fa-broom"></i> Sanitization</div>
            <div class="arch-arrow">→</div>
            <div class="arch-node safe"><i class="fas fa-check"></i> Safe? → LLM</div>
            <div class="arch-arrow">/</div>
            <div class="arch-node blocked"><i class="fas fa-ban"></i> Blocked → Log</div>
        </div>
    </div>

    <div class="row mt-4 g-3">
        <div class="col-md-6 col-lg-3">
                <div class="result-item">
                    <div class="result-item-label">Frontend</div>
                    <div class="result-item-value">PHP 8 + Vanilla JS</div>
                    <div style="font-size:11px;color:var(--txt-3);margin-top:4px">IBM Plex Sans/Mono · Chart.js 4 · Font Awesome 6</div>
                </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="result-item">
                <div class="result-item-label">Analysis Engine</div>
                <div class="result-item-value">PHP Rule Engine</div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Regex, Keyword, Phrase matching</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
                <div class="result-item">
                    <div class="result-item-label">Database</div>
                    <div class="result-item-value">MySQL (DianaHost)</div>
                    <div style="font-size:11px;color:var(--txt-3);margin-top:4px">7 tables · InnoDB · 44 detection rules</div>
                </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="result-item">
                <div class="result-item-label">Visualization</div>
                <div class="result-item-value">Chart.js 4</div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Trend lines, Doughnut, KPI counters</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Database ER Diagram -->
    <div class="col-lg-6">
        <div class="glass-card">
            <div class="card-header-custom">
                <h3 class="card-title"><i class="fas fa-database"></i> Database Schema</h3>
            </div>
            
            <div class="arch-section">
                <table class="table-custom">
                    <thead>
                        <tr><th>Table</th><th>Purpose</th><th>Key Columns</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="color:var(--amber);font-weight:600">attack_categories</td>
                            <td>5 attack taxonomy categories</td>
                            <td class="mono" style="font-size:11px">name, slug, severity_weight, color</td>
                        </tr>
                        <tr>
                            <td style="color:var(--amber);font-weight:600">rules</td>
                            <td>Detection rules with patterns</td>
                            <td class="mono" style="font-size:11px">pattern, pattern_type, severity, severity_score</td>
                        </tr>
                        <tr>
                            <td style="color:var(--accent-primary);font-weight:600">prompt_logs</td>
                            <td>All analyzed prompts</td>
                            <td class="mono" style="font-size:11px">prompt_text, risk_score, verdict</td>
                        </tr>
                        <tr>
                            <td style="color:var(--accent-primary);font-weight:600">rule_matches</td>
                            <td>Rule-to-log junction</td>
                            <td class="mono" style="font-size:11px">log_id, rule_id, matched_text</td>
                        </tr>
                        <tr>
                            <td style="color:var(--accent-primary);font-weight:600">sanitization_log</td>
                            <td>Sanitization transformations</td>
                            <td class="mono" style="font-size:11px">original_fragment, sanitized_fragment</td>
                        </tr>
                        <tr>
                            <td style="color:var(--accent-primary);font-weight:600">settings</td>
                            <td>System configuration</td>
                            <td class="mono" style="font-size:11px">setting_key, setting_value, setting_type</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="background:var(--bg-2);border-radius:var(--r);padding:14px;font-family:'IBM Plex Mono',monospace;font-size:12px;line-height:2;color:var(--txt-2)">
                <span style="color:var(--amber)">attack_categories</span> 1──∞ <span style="color:var(--accent-primary)">rules</span><br>
                <span style="color:var(--amber)">prompt_logs</span> 1──∞ <span style="color:var(--accent-primary)">rule_matches</span><br>
                <span style="color:var(--amber)">rules</span> 1──∞ <span style="color:var(--accent-primary)">rule_matches</span><br>
                <span style="color:var(--amber)">prompt_logs</span> 1──∞ <span style="color:var(--accent-primary)">sanitization_log</span>
            </div>
        </div>
    </div>

    <!-- Rule Engine Flowchart -->
    <div class="col-lg-6">
        <div class="glass-card">
            <div class="card-header-custom">
                <h3 class="card-title"><i class="fas fa-stream"></i> Rule Engine Pipeline</h3>
            </div>
            
            <div class="arch-section">
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div class="result-item" style="border-left:3px solid var(--info)">
                        <div style="display:flex;align-items:center;gap:10px">
                            <span style="background:var(--bg-2);color:var(--blue);width:28px;height:28px;border-radius:var(--r);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">1</span>
                            <div>
                                <div class="result-item-value">Input Validation</div>
                                <div style="font-size:11px;color:var(--txt-3)">Check prompt length, encoding, and emptiness</div>
                            </div>
                        </div>
                    </div>
                    <div class="result-item" style="border-left:3px solid var(--accent-primary)">
                        <div style="display:flex;align-items:center;gap:10px">
                            <span style="background:var(--amber-dim);color:var(--amber);width:28px;height:28px;border-radius:var(--r);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">2</span>
                            <div>
                                <div class="result-item-value">Rule Evaluation</div>
                                <div style="font-size:11px;color:var(--txt-3)">Match against 44 active rules (regex, keyword, phrase)</div>
                            </div>
                        </div>
                    </div>
                    <div class="result-item" style="border-left:3px solid var(--accent-secondary)">
                        <div style="display:flex;align-items:center;gap:10px">
                            <span style="background:var(--bg-2);color:var(--txt-2);width:28px;height:28px;border-radius:var(--r);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">3</span>
                            <div>
                                <div class="result-item-value">Risk Scoring</div>
                                <div style="font-size:11px;color:var(--txt-3)">Calculate weighted risk: Σ(rule_score × category_weight), cap at 100</div>
                            </div>
                        </div>
                    </div>
                    <div class="result-item" style="border-left:3px solid var(--warning)">
                        <div style="display:flex;align-items:center;gap:10px">
                            <span style="background:var(--amber-dim);color:var(--amber);width:28px;height:28px;border-radius:var(--r);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">4</span>
                            <div>
                                <div class="result-item-value">Sanitization</div>
                                <div style="font-size:11px;color:var(--txt-3)">Strip PII (SSN, CC, email, phone), remove injection tokens</div>
                            </div>
                        </div>
                    </div>
                    <div class="result-item" style="border-left:3px solid var(--success)">
                        <div style="display:flex;align-items:center;gap:10px">
                            <span style="background:var(--bg-2);color:var(--green);width:28px;height:28px;border-radius:var(--r);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">5</span>
                            <div>
                                <div class="result-item-value">Verdict & Logging</div>
                                <div style="font-size:11px;color:var(--txt-3)">
                                    Safe (≤30) → Pass &nbsp;|&nbsp; 
                                    Suspicious (31–65) → Warn &nbsp;|&nbsp; 
                                    Blocked (>65) → Deny + Log to DB
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attack Categories Documentation -->
<div class="glass-card mb-4">
    <div class="card-header-custom">
        <h3 class="card-title"><i class="fas fa-crosshairs"></i> Attack Categories</h3>
    </div>
    
    <div class="row g-3" id="categories-container">
        <!-- Populated by JS -->
    </div>
</div>

<!-- API Reference -->
<div class="glass-card mb-4">
    <div class="card-header-custom">
        <h3 class="card-title"><i class="fas fa-code"></i> API Reference</h3>
    </div>
    
    <div class="arch-section">
        <table class="table-custom">
            <thead>
                <tr><th>Method</th><th>Endpoint</th><th>Description</th><th>Parameters</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="severity-badge high">POST</span></td>
                    <td class="mono" style="color:var(--amber)">/api/analyze.php</td>
                    <td>Analyze a prompt for threats</td>
                    <td class="mono" style="font-size:11px">{ prompt, source, activity_id, destination_model }</td>
                </tr>
                <tr>
                    <td><span class="severity-badge low">GET</span></td>
                    <td class="mono" style="color:var(--amber)">/api/rules.php</td>
                    <td>List all detection rules + categories</td>
                    <td class="mono" style="font-size:11px">?id=X (optional)</td>
                </tr>
                <tr>
                    <td><span class="severity-badge high">POST</span></td>
                    <td class="mono" style="color:var(--amber)">/api/rules.php</td>
                    <td>Create a new detection rule</td>
                    <td class="mono" style="font-size:11px">{ name, pattern, severity, category_id, ... }</td>
                </tr>
                <tr>
                    <td><span class="severity-badge medium">PUT</span></td>
                    <td class="mono" style="color:var(--amber)">/api/rules.php?id=X</td>
                    <td>Update a rule</td>
                    <td class="mono" style="font-size:11px">{ name, pattern, ... }</td>
                </tr>
                <tr>
                    <td><span class="severity-badge critical">DELETE</span></td>
                    <td class="mono" style="color:var(--amber)">/api/rules.php?id=X</td>
                    <td>Delete a rule</td>
                    <td class="mono" style="font-size:11px">—</td>
                </tr>
                <tr>
                    <td><span class="severity-badge low">GET</span></td>
                    <td class="mono" style="color:var(--amber)">/api/logs.php</td>
                    <td>Fetch prompt logs with filters</td>
                    <td class="mono" style="font-size:11px">?verdict=&category=&search=&date_from=&date_to=</td>
                </tr>
                <tr>
                    <td><span class="severity-badge low">GET</span></td>
                    <td class="mono" style="color:var(--amber)">/api/stats.php</td>
                    <td>Dashboard statistics + time series</td>
                    <td class="mono" style="font-size:11px">—</td>
                </tr>
                <tr>
                    <td><span class="severity-badge low">GET</span></td>
                    <td class="mono" style="color:var(--amber)">/api/activities.php</td>
                    <td>List test bench activity sessions</td>
                    <td class="mono" style="font-size:11px">?id=X (optional)</td>
                </tr>
                <tr>
                    <td><span class="severity-badge high">POST</span></td>
                    <td class="mono" style="color:var(--amber)">/api/activities.php</td>
                    <td>Create a new activity session</td>
                    <td class="mono" style="font-size:11px">{ name, description, user_model, destination_model }</td>
                </tr>
                <tr>
                    <td><span class="severity-badge low">GET</span></td>
                    <td class="mono" style="color:var(--amber)">/api/settings.php?providers=1</td>
                    <td>Get AI provider status (configured/unconfigured)</td>
                    <td class="mono" style="font-size:11px">—</td>
                </tr>
                <tr>
                    <td><span class="severity-badge medium">PUT</span></td>
                    <td class="mono" style="color:var(--amber)">/api/settings.php</td>
                    <td>Update API keys / risk thresholds / model config</td>
                    <td class="mono" style="font-size:11px">{ settings: { key: value } }</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Scoring Model -->
<div class="glass-card">
    <div class="card-header-custom">
        <h3 class="card-title"><i class="fas fa-calculator"></i> Risk Scoring Model</h3>
    </div>
    
    <div class="row g-3">
        <div class="col-md-6">
            <h5 style="font-size:14px;font-weight:600;margin-bottom:12px">Formula</h5>
            <div style="background:var(--bg-2);border-radius:var(--r);padding:14px;font-family:'IBM Plex Mono',monospace;font-size:12px;line-height:2;color:var(--txt-2)">
                <span style="color:var(--accent-primary)">risk_score</span> = min(100, Σ(<span style="color:var(--warning)">rule.severity_score</span> × <span style="color:var(--accent-secondary)">category.weight</span>))<br><br>
                <span style="color:var(--text-muted)">// Category Weights:</span><br>
                <span style="color:#ff3b30">Harmful Intent</span>: 1.80×<br>
                <span style="color:#ff2d55">Jailbreak</span>: 1.50×<br>
                <span style="color:#af52de">System Override</span>: 1.40×<br>
                <span style="color:#ff9500">PII Exposure</span>: 1.30×<br>
                <span style="color:#5856d6">Social Engineering</span>: 1.20×
            </div>
        </div>
        <div class="col-md-6">
            <h5 style="font-size:14px;font-weight:600;margin-bottom:12px">Verdict Thresholds</h5>
            <div style="display:flex;flex-direction:column;gap:12px">
                <div class="result-item" style="border-left:3px solid var(--success)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="result-item-value" style="color:var(--success)">SAFE</div>
                            <div style="font-size:11px;color:var(--text-muted)">No threats detected, prompt allowed</div>
                        </div>
                        <span class="mono" style="font-size:18px;font-weight:700;color:var(--success)">0–30</span>
                    </div>
                </div>
                <div class="result-item" style="border-left:3px solid var(--warning)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="result-item-value" style="color:var(--warning)">SUSPICIOUS</div>
                            <div style="font-size:11px;color:var(--text-muted)">Potential risk, sanitized and warned</div>
                        </div>
                        <span class="mono" style="font-size:18px;font-weight:700;color:var(--warning)">31–65</span>
                    </div>
                </div>
                <div class="result-item" style="border-left:3px solid var(--danger)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="result-item-value" style="color:var(--danger)">BLOCKED</div>
                            <div style="font-size:11px;color:var(--text-muted)">High-risk content, prompt denied</div>
                        </div>
                        <span class="mono" style="font-size:18px;font-weight:700;color:var(--danger)">66–100</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const data = await api.get('api/rules.php');
        if (!data.success) return;
        
        const container = document.getElementById('categories-container');
        const catIcons = {
            'jailbreak': 'unlock-alt', 'pii-exposure': 'id-card',
            'harmful-intent': 'skull-crossbones', 'system-override': 'terminal',
            'social-engineering': 'masks-theater'
        };
        
        container.innerHTML = data.categories.map(c => {
            const ruleCount = data.rules.filter(r => r.category_id == c.id).length;
            return `
                <div class="col-md-6 col-lg-4">
                    <div class="result-item" style="border-left:3px solid ${c.color}">
                        <div class="d-flex align-items-center gap-10 mb-2" style="gap:10px">
                            <i class="fas fa-${catIcons[c.slug] || 'shield-alt'}" style="color:${c.color};font-size:20px"></i>
                            <div>
                                <div class="result-item-value">${escapeHtml(c.name)}</div>
                                <div style="font-size:11px;color:var(--text-muted)">${ruleCount} rules · Weight: ${c.severity_weight}×</div>
                            </div>
                        </div>
                        <p style="font-size:12px;color:var(--text-secondary);margin:0;line-height:1.6">${escapeHtml(c.description)}</p>
                    </div>
                </div>
            `;
        }).join('');
    } catch(e) {
        console.error('Failed to load categories', e);
    }
});
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
