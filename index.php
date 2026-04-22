<?php
/**
 * AI Prompt Security Gateway — Dashboard Overview
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
        <div class="kpi-card total">
            <span class="kpi-badge" id="kpi-avg-time">— ms avg</span>
            <div class="kpi-icon"><i class="fas fa-layer-group"></i></div>
            <div class="kpi-value" id="kpi-total">—</div>
            <div class="kpi-label">Total Scans</div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="kpi-card blocked">
            <span class="kpi-badge" id="kpi-block-rate">0%</span>
            <div class="kpi-icon"><i class="fas fa-ban"></i></div>
            <div class="kpi-value" id="kpi-blocked">—</div>
            <div class="kpi-label">Blocked Prompts</div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="kpi-card suspicious">
            <span class="kpi-badge" id="kpi-avg-risk">— risk</span>
            <div class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="kpi-value" id="kpi-suspicious">—</div>
            <div class="kpi-label">Suspicious</div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="kpi-card safe">
            <span class="kpi-badge" id="kpi-rules-active">— rules</span>
            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-value" id="kpi-safe">—</div>
            <div class="kpi-label">Safe Prompts</div>
        </div>
    </div>
</div>

<!-- System Health Bar -->
<div class="glass-card mb-4" style="padding:14px 20px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:8px">
            <span style="width:8px;height:8px;background:var(--green);border-radius:50%;display:inline-block"></span>
            <span style="font-size:12px;font-weight:600;color:var(--txt-1)">System Operational</span>
        </div>
        <div style="display:flex;gap:20px;flex-wrap:wrap">
            <div style="text-align:center">
                <div style="font-size:10px;color:var(--txt-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px">Detection Engine</div>
                <div style="font-size:11px;font-weight:600;color:var(--green);font-family:'IBM Plex Mono',monospace">ONLINE</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:10px;color:var(--txt-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px">Database</div>
                <div style="font-size:11px;font-weight:600;color:var(--green);font-family:'IBM Plex Mono',monospace">CONNECTED</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:10px;color:var(--txt-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px">API Endpoint</div>
                <div style="font-size:11px;font-weight:600;color:var(--green);font-family:'IBM Plex Mono',monospace">ACTIVE</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:10px;color:var(--txt-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px">Active Rules</div>
                <div style="font-size:11px;font-weight:600;color:var(--amber);font-family:'IBM Plex Mono',monospace" id="health-rules">—</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:10px;color:var(--txt-3);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px">Block Rate</div>
                <div style="font-size:11px;font-weight:600;color:var(--red);font-family:'IBM Plex Mono',monospace" id="health-blockrate">—</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="card-header-custom">
                <h3 class="card-title"><i class="fas fa-chart-area"></i> Threat Trends (30 Days)</h3>
                <span style="font-size:11px;color:var(--txt-3);font-family:'IBM Plex Mono',monospace">daily scan volume</span>
            </div>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card" style="height:100%">
            <div class="card-header-custom">
                <h3 class="card-title"><i class="fas fa-chart-pie"></i> Attack Categories</h3>
            </div>
            <div class="chart-container chart-container-sm">
                <canvas id="categoryChart"></canvas>
            </div>
            <div id="category-legend" style="margin-top:8px"></div>
        </div>
    </div>
</div>

<!-- Bottom Row: Rules + Activity + Quick Stats -->
<div class="row g-3">
    <!-- Top Triggered Rules -->
    <div class="col-lg-5">
        <div class="glass-card" style="height:100%">
            <div class="card-header-custom">
                <h3 class="card-title"><i class="fas fa-fire"></i> Top Triggered Rules</h3>
                <a href="rules.php" class="btn-glass" style="font-size:11px;padding:4px 10px">View All</a>
            </div>
            <div id="top-rules-container">
                <div class="empty-state" style="padding:24px">
                    <div class="spinner-custom" style="width:20px;height:20px;border-width:2px;margin:0 auto 10px"></div>
                    <p style="font-size:12px">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-lg-4">
        <div class="glass-card" style="height:100%">
            <div class="card-header-custom">
                <h3 class="card-title"><i class="fas fa-clock-rotate-left"></i> Recent Activity</h3>
                <a href="logs.php" class="btn-glass" style="font-size:11px;padding:4px 10px">All Logs</a>
            </div>
            <div id="recent-activity-container">
                <div class="empty-state" style="padding:24px">
                    <div class="spinner-custom" style="width:20px;height:20px;border-width:2px;margin:0 auto 10px"></div>
                    <p style="font-size:12px">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Panel -->
    <div class="col-lg-3">
        <div class="glass-card mb-3">
            <div class="card-header-custom" style="margin-bottom:12px">
                <h3 class="card-title"><i class="fas fa-gauge-high"></i> Threat Score</h3>
            </div>
            <!-- Risk Gauge -->
            <div style="text-align:center;padding:8px 0 12px">
                <div style="font-size:48px;font-weight:300;line-height:1;font-family:'IBM Plex Mono',monospace;color:var(--txt-1)" id="avg-risk-display">—</div>
                <div style="font-size:10px;color:var(--txt-3);text-transform:uppercase;letter-spacing:0.8px;margin-top:4px">avg risk score</div>
                <div style="margin-top:12px;height:4px;background:var(--bg-3);border-radius:2px;overflow:hidden">
                    <div id="risk-bar" style="height:100%;width:0%;background:var(--red);border-radius:2px;transition:width 0.8s ease"></div>
                </div>
            </div>
        </div>

        <div class="glass-card">
            <div class="card-header-custom" style="margin-bottom:12px">
                <h3 class="card-title"><i class="fas fa-shield-halved"></i> Security Summary</h3>
            </div>
            <div id="security-summary" style="display:flex;flex-direction:column;gap:8px">
                <div class="empty-state" style="padding:12px">
                    <div class="spinner-custom" style="width:18px;height:18px;border-width:2px;margin:0 auto"></div>
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
        const data = await api.get('api/stats.php');
        if (!data.success) return;
        const s = data.stats;

        // KPI counters
        animateCounter('kpi-total',      s.total_scans);
        animateCounter('kpi-blocked',    s.verdicts.blocked);
        animateCounter('kpi-suspicious', s.verdicts.suspicious);
        animateCounter('kpi-safe',       s.verdicts.safe);

        document.getElementById('kpi-block-rate').textContent  = s.block_rate + '%';
        document.getElementById('kpi-avg-time').textContent    = s.avg_analysis_time + ' ms avg';
        document.getElementById('kpi-avg-risk').textContent    = s.avg_risk_score + ' risk';
        document.getElementById('kpi-rules-active').textContent = s.active_rules + ' rules';

        // System health bar
        document.getElementById('health-rules').textContent    = s.active_rules;
        document.getElementById('health-blockrate').textContent = s.block_rate + '%';

        // Avg risk gauge
        const risk = parseFloat(s.avg_risk_score) || 0;
        document.getElementById('avg-risk-display').textContent = risk.toFixed(0);
        const riskEl = document.getElementById('risk-bar');
        setTimeout(() => { riskEl.style.width = Math.min(risk, 100) + '%'; }, 200);
        riskEl.style.background = risk >= 70 ? 'var(--red)' : risk >= 40 ? 'var(--yellow)' : 'var(--green)';
        document.getElementById('avg-risk-display').style.color = risk >= 70 ? 'var(--red)' : risk >= 40 ? 'var(--yellow)' : 'var(--green)';

        // Trend chart
        if (s.time_series && s.time_series.length > 0) {
            createTrendChart('trendChart', s.time_series);
        }

        // Category doughnut
        if (s.category_breakdown && s.category_breakdown.length > 0) {
            const labels = s.category_breakdown.map(c => c.name);
            const values = s.category_breakdown.map(c => parseInt(c.hit_count) || 0);
            const colors = s.category_breakdown.map(c => c.color);
            createDoughnutChart('categoryChart', labels, values, colors);
            // custom legend
            document.getElementById('category-legend').innerHTML = s.category_breakdown.map(c => `
                <div style="display:flex;align-items:center;gap:6px;padding:3px 0">
                    <span style="width:8px;height:8px;border-radius:2px;background:${c.color};flex-shrink:0;display:inline-block"></span>
                    <span style="font-size:11px;color:var(--txt-2)">${escapeHtml(c.name)}</span>
                    <span style="font-size:11px;font-family:'IBM Plex Mono',monospace;color:var(--txt-3);margin-left:auto">${c.hit_count}</span>
                </div>
            `).join('');
        }

        // Top rules
        const rulesContainer = document.getElementById('top-rules-container');
        if (s.top_rules && s.top_rules.length > 0) {
            rulesContainer.innerHTML = s.top_rules.slice(0,6).map((r, i) => `
                <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border)">
                    <span style="font-size:10px;color:var(--txt-3);font-family:'IBM Plex Mono',monospace;width:14px;text-align:right">${i+1}</span>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12.5px;font-weight:500;color:var(--txt-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeHtml(r.name)}</div>
                        <div style="font-size:10.5px;color:var(--txt-3);margin-top:1px">${escapeHtml(r.category_name || '')}</div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        ${getSeverityBadge(r.severity)}
                        <div style="font-size:12px;font-family:'IBM Plex Mono',monospace;color:var(--amber);margin-top:3px">${r.match_count}x</div>
                    </div>
                </div>
            `).join('') + `<div style="padding-bottom:4px"></div>`;
        } else {
            rulesContainer.innerHTML = `
                <div class="empty-state" style="padding:24px">
                    <i class="fas fa-shield-check"></i>
                    <h4>No rules triggered</h4>
                    <p>Analyze prompts in the Test Bench to see triggered rules.</p>
                </div>`;
        }

        // Recent activity
        const actContainer = document.getElementById('recent-activity-container');
        if (s.recent_activity && s.recent_activity.length > 0) {
            actContainer.innerHTML = s.recent_activity.slice(0, 8).map(a => `
                <div class="activity-item">
                    <div class="activity-icon ${a.verdict}">
                        <i class="fas fa-${a.verdict === 'safe' ? 'check' : a.verdict === 'suspicious' ? 'exclamation' : 'times'}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-prompt">${escapeHtml(a.prompt_preview)}</div>
                        <div class="activity-meta">
                            ${getVerdictBadge(a.verdict)}
                            <span>Risk: ${a.risk_score}</span>
                            <span>${formatDate(a.created_at)}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            actContainer.innerHTML = `
                <div class="empty-state" style="padding:24px">
                    <i class="fas fa-history"></i>
                    <h4>No activity yet</h4>
                    <p>Go to the <a href="testbench.php" style="color:var(--amber)">Test Bench</a> to analyze some prompts.</p>
                </div>`;
        }

        // Security summary panel
        const total = s.total_scans || 1;
        const blocked = s.verdicts.blocked || 0;
        const safe = s.verdicts.safe || 0;
        const suspicious = s.verdicts.suspicious || 0;
        const summaryItems = [
            { label: 'Threat Detection Rate', val: s.block_rate + '%', color: 'var(--red)' },
            { label: 'Clean Pass Rate',        val: Math.round(safe/total*100) + '%', color: 'var(--green)' },
            { label: 'Suspicious Rate',        val: Math.round(suspicious/total*100) + '%', color: 'var(--yellow)' },
            { label: 'Rules Deployed',         val: s.active_rules, color: 'var(--amber)' },
            { label: 'Avg Response Time',      val: s.avg_analysis_time + 'ms', color: 'var(--blue)' },
        ];
        document.getElementById('security-summary').innerHTML = summaryItems.map(item => `
            <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border)">
                <span style="font-size:11.5px;color:var(--txt-2)">${item.label}</span>
                <span style="font-size:12px;font-weight:600;font-family:'IBM Plex Mono',monospace;color:${item.color}">${item.val}</span>
            </div>
        `).join('') + '<div style="padding-bottom:2px"></div>';

    } catch(e) {
        console.error('Dashboard load error:', e);
    }
});
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
