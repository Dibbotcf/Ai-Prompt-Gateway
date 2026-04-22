<?php
/**
 * AI Prompt Security Gateway — Settings
 */
$pageTitle = 'Settings';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-3">
    <div class="col-lg-7">
        <!-- Free API Keys Info Banner -->
        <div class="glass-card mb-3" style="border-left:2px solid var(--amber);padding:14px 18px">
            <div style="display:flex;align-items:flex-start;gap:10px">
                <i class="fas fa-circle-info" style="color:var(--amber);margin-top:1px"></i>
                <div>
                    <div style="font-size:13px;font-weight:600;color:var(--txt-1);margin-bottom:4px">Free API Keys Available</div>
                    <div style="font-size:12px;color:var(--txt-2);line-height:1.6">
                        <strong style="color:var(--green)">Groq</strong> — 100% free, no credit card. Sign up at <a href="https://console.groq.com/keys" target="_blank" style="color:var(--amber)">console.groq.com</a> → API Keys → Create Key.<br>
                        <strong style="color:var(--blue)">Google Gemini</strong> — free tier (60 req/min). Get key at <a href="https://aistudio.google.com/apikey" target="_blank" style="color:var(--amber)">aistudio.google.com</a>.<br>
                        <strong style="color:var(--txt-3)">OpenAI GPT</strong> — requires credit card ($5 minimum).
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Provider API Keys -->
        <div class="glass-card mb-3">
            <div class="card-header-custom">
                <h3 class="card-title"><i class="fas fa-key"></i> AI Provider API Keys</h3>
            </div>
            <div id="apiKeysContainer">
                <div class="empty-state" style="padding:24px">
                    <div class="spinner-custom" style="width:20px;height:20px;border-width:2px;margin:0 auto 8px"></div>
                    <p style="font-size:12px">Loading...</p>
                </div>
            </div>
        </div>

        <!-- Model Settings -->
        <div class="glass-card">
            <div class="card-header-custom">
                <h3 class="card-title"><i class="fas fa-sliders-h"></i> Model Configuration</h3>
            </div>
            <div id="modelSettingsContainer">
                <div class="empty-state" style="padding:24px">
                    <div class="spinner-custom" style="width:20px;height:20px;border-width:2px;margin:0 auto 8px"></div>
                    <p style="font-size:12px">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <!-- Provider Status -->
        <div class="glass-card mb-3">
            <div class="card-header-custom">
                <h3 class="card-title"><i class="fas fa-circle-nodes"></i> Provider Status</h3>
            </div>
            <div id="providerStatus">
                <div class="empty-state" style="padding:24px">
                    <div class="spinner-custom" style="width:20px;height:20px;border-width:2px;margin:0 auto"></div>
                </div>
            </div>
        </div>

        <!-- Risk Thresholds -->
        <div class="glass-card mb-3">
            <div class="card-header-custom">
                <h3 class="card-title"><i class="fas fa-gauge-high"></i> Risk Thresholds</h3>
            </div>
            <div id="thresholdSettings">
                <div class="empty-state" style="padding:24px">
                    <div class="spinner-custom" style="width:20px;height:20px;border-width:2px;margin:0 auto"></div>
                </div>
            </div>
        </div>

        <!-- Quick Guide -->
        <div class="glass-card" style="border-left:2px solid var(--green)">
            <div class="card-header-custom">
                <h3 class="card-title"><i class="fas fa-rocket"></i> Quick Start Guide</h3>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;font-size:12px;color:var(--txt-2)">
                <div style="display:flex;gap:10px;align-items:flex-start">
                    <span style="width:18px;height:18px;background:var(--amber);color:#000;border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0">1</span>
                    <span>Go to <a href="https://console.groq.com/keys" target="_blank" style="color:var(--amber)">console.groq.com</a> → Sign up free → Create API Key</span>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start">
                    <span style="width:18px;height:18px;background:var(--amber);color:#000;border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0">2</span>
                    <span>Paste the key in the <strong style="color:var(--txt-1)">Groq</strong> field above and click Save</span>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start">
                    <span style="width:18px;height:18px;background:var(--amber);color:#000;border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;flex-shrink:0">3</span>
                    <span>Go to <strong style="color:var(--txt-1)">Test Bench</strong> → select Groq as destination → start testing</span>
                </div>
                <div style="margin-top:4px;padding:10px;background:var(--bg-2);border-radius:var(--r);border:1px solid var(--border)">
                    <div style="font-size:10.5px;color:var(--txt-3);margin-bottom:3px;font-weight:600;text-transform:uppercase;letter-spacing:0.4px">Groq Free Tier Limits</div>
                    <div style="font-family:'IBM Plex Mono',monospace;font-size:11px;color:var(--green)">14,400 req/day · 30 req/min · Free forever</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = <<<'JS'
<script>
let allSettings = {};

// Real SVG logos for settings page
const providerLogos = {
    gemini: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z" fill="#4285f4"/></svg>`,
    openai: `<svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.282 9.821a5.985 5.985 0 0 0-.516-4.91 6.046 6.046 0 0 0-6.51-2.9A6.065 6.065 0 0 0 4.981 4.18a5.985 5.985 0 0 0-3.998 2.9 6.046 6.046 0 0 0 .743 7.097 5.98 5.98 0 0 0 .51 4.911 6.051 6.051 0 0 0 6.515 2.9A5.985 5.985 0 0 0 13.26 24a6.056 6.056 0 0 0 5.772-4.206 5.99 5.99 0 0 0 3.997-2.9 6.056 6.056 0 0 0-.747-7.073z" fill="#10a37f"/></svg>`,
    groq:   `<svg width="18" height="18" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm-.5 5.5l5 3.5-5 3.5V5.5zm-1 3v7l-4-3.5 4-3.5z" fill="#f55036"/></svg>`,
};

const providerMeta = {
    gemini: { free: true,  freeNote: 'Free tier — 60 req/min',    link: 'https://aistudio.google.com/apikey',   color: '#4285f4' },
    openai: { free: false, freeNote: 'Paid — needs credit card',   link: 'https://platform.openai.com/api-keys', color: '#10a37f' },
    groq:   { free: true,  freeNote: '100% Free — no card needed', link: 'https://console.groq.com/keys',         color: '#f55036' },
};

document.addEventListener('DOMContentLoaded', loadAllSettings);

async function loadAllSettings() {
    try {
        const [settingsData, providerData] = await Promise.all([
            api.get('api/settings.php'),
            api.get('api/settings.php?providers=1'),
        ]);

        if (settingsData.success) {
            allSettings = {};
            settingsData.settings.forEach(s => { allSettings[s.setting_key] = s; });
            renderApiKeys();
            renderModelSettings();
            renderThresholds();
        }

        if (providerData.success) {
            renderProviderStatus(providerData.providers);
        }
    } catch(e) {
        console.error('Settings load error:', e);
    }
}

function renderApiKeys() {
    const providers = [
        { key: 'ai_groq_api_key',   provId: 'groq',   name: 'Groq' },
        { key: 'ai_gemini_api_key', provId: 'gemini', name: 'Google Gemini' },
        { key: 'ai_openai_api_key', provId: 'openai', name: 'OpenAI GPT' },
    ];

    document.getElementById('apiKeysContainer').innerHTML = providers.map(p => {
        const s       = allSettings[p.key];
        const hasKey  = s && s.has_key;
        const meta    = providerMeta[p.provId];
        const logo    = providerLogos[p.provId] || '';
        return `
        <div style="padding:14px;background:var(--bg-2);border-radius:var(--r);border:1px solid var(--border);margin-bottom:10px">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                <span style="width:28px;height:28px;display:flex;align-items:center;justify-content:center;flex-shrink:0">${logo}</span>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:13px;display:flex;align-items:center;gap:6px">
                        ${p.name}
                        ${meta.free 
                            ? `<span style="font-size:9.5px;padding:1px 6px;background:var(--green-dim);color:var(--green);border:1px solid var(--green-border);border-radius:var(--r);font-family:'IBM Plex Mono',monospace">FREE</span>`
                            : `<span style="font-size:9.5px;padding:1px 6px;background:var(--bg-3);color:var(--txt-3);border:1px solid var(--border);border-radius:var(--r);font-family:'IBM Plex Mono',monospace">PAID</span>`
                        }
                    </div>
                    <div style="font-size:11px;color:var(--txt-3);margin-top:1px">${meta.freeNote}</div>
                </div>
                <a href="${meta.link}" target="_blank" class="btn-glass" style="font-size:11px;padding:4px 10px;color:var(--amber)">
                    <i class="fas fa-external-link-alt" style="font-size:9px"></i> Get Key
                </a>
            </div>
            <div style="display:flex;gap:8px">
                <input type="password" class="form-control-custom" style="flex:1;font-family:'IBM Plex Mono',monospace;font-size:12px" 
                    id="${p.key}" placeholder="${hasKey ? '••••••••' + (s.setting_value_masked || '') : 'Paste your API key here...'}" 
                    value="">
                <button class="btn-gradient" style="padding:8px 14px;white-space:nowrap" onclick="saveApiKey('${p.key}')">
                    <i class="fas fa-save"></i> Save
                </button>
                ${hasKey ? `<button class="btn-glass" style="padding:8px;color:var(--red)" onclick="clearApiKey('${p.key}')" title="Remove key"><i class="fas fa-trash"></i></button>` : ''}
            </div>
            ${hasKey ? `<div style="font-size:11px;color:var(--green);margin-top:6px"><i class="fas fa-check-circle"></i> API key is configured — provider is active</div>` : ''}
        </div>`;
    }).join('');
}

function renderModelSettings() {
    const models = [
        { key: 'ai_groq_model',   name: 'Groq Model',   placeholder: 'llama3-8b-8192',   hint: 'Free: llama3-8b-8192, llama3-70b-8192, mixtral-8x7b-32768' },
        { key: 'ai_gemini_model', name: 'Gemini Model', placeholder: 'gemini-2.0-flash', hint: 'Free: gemini-2.0-flash, gemini-1.5-flash' },
        { key: 'ai_openai_model', name: 'OpenAI Model', placeholder: 'gpt-3.5-turbo',   hint: 'Paid: gpt-3.5-turbo (cheapest), gpt-4o' },
    ];

    document.getElementById('modelSettingsContainer').innerHTML = models.map(m => {
        const val = allSettings[m.key]?.setting_value || '';
        return `
        <div style="margin-bottom:12px">
            <label class="form-label-custom">${m.name}</label>
            <input type="text" class="form-control-custom w-100" id="${m.key}" value="${escapeHtml(val)}" placeholder="${m.placeholder}">
            <div style="font-size:10.5px;color:var(--txt-3);margin-top:4px;font-family:'IBM Plex Mono',monospace">${m.hint}</div>
        </div>`;
    }).join('') + `
        <button class="btn-gradient mt-2" onclick="saveModelSettings()">
            <i class="fas fa-save"></i> Save Model Settings
        </button>`;
}

function renderThresholds() {
    const safeVal = allSettings['risk_threshold_safe']?.setting_value || '30';
    const suspVal = allSettings['risk_threshold_suspicious']?.setting_value || '65';

    document.getElementById('thresholdSettings').innerHTML = `
        <div style="margin-bottom:12px">
            <label class="form-label-custom">Safe Threshold (0–100)</label>
            <input type="number" class="form-control-custom w-100" id="threshold_safe" value="${safeVal}" min="0" max="100">
            <div style="font-size:11px;color:var(--txt-3);margin-top:4px">Risk ≤ this → SAFE</div>
        </div>
        <div style="margin-bottom:12px">
            <label class="form-label-custom">Suspicious Threshold (0–100)</label>
            <input type="number" class="form-control-custom w-100" id="threshold_suspicious" value="${suspVal}" min="0" max="100">
            <div style="font-size:11px;color:var(--txt-3);margin-top:4px">Safe &lt; Risk ≤ this → SUSPICIOUS · Above → BLOCKED</div>
        </div>
        <button class="btn-gradient" onclick="saveThresholds()">
            <i class="fas fa-save"></i> Save Thresholds
        </button>`;
}

function renderProviderStatus(providers) {
    document.getElementById('providerStatus').innerHTML = providers.map(p => {
        const logo = providerLogos[p.id] || `<i class="fas fa-robot" style="color:${p.color}"></i>`;
        const meta = providerMeta[p.id] || {};
        return `
        <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border)">
            <span style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;flex-shrink:0">${logo}</span>
            <div style="flex:1">
                <div style="font-size:12.5px;font-weight:500">${p.name}</div>
                <div style="font-size:10.5px;color:var(--txt-3);font-family:'IBM Plex Mono',monospace">${p.model || (p.id === 'simulated' ? 'built-in' : 'default model')}</div>
            </div>
            ${p.configured 
                ? `<span style="font-size:11px;color:var(--green);display:flex;align-items:center;gap:4px"><span style="width:6px;height:6px;background:var(--green);border-radius:50%;display:inline-block"></span>READY</span>`
                : `<span style="font-size:11px;color:var(--txt-3);font-family:'IBM Plex Mono',monospace">NO KEY</span>`
            }
        </div>`;
    }).join('') + '<div style="padding-bottom:4px"></div>';
}

async function saveApiKey(key) {
    const input = document.getElementById(key);
    const value = input.value.trim();
    if (!value) { showToast('Paste an API key first', 'error'); return; }
    
    try {
        const result = await api.put('api/settings.php', { settings: { [key]: value } });
        if (result.success) {
            showToast('API key saved!');
            input.value = '';
            loadAllSettings();
        } else {
            showToast(result.message || 'Failed to save', 'error');
        }
    } catch(e) { showToast('Failed to save', 'error'); }
}

async function clearApiKey(key) {
    if (!confirm('Remove this API key? The provider will no longer be active.')) return;
    try {
        const result = await api.put('api/settings.php', { settings: { [key]: '' } });
        if (result.success) { showToast('Key removed'); loadAllSettings(); }
    } catch(e) { showToast('Failed', 'error'); }
}

async function saveModelSettings() {
    const settings = {};
    ['ai_gemini_model', 'ai_openai_model', 'ai_groq_model'].forEach(k => {
        settings[k] = document.getElementById(k).value.trim();
    });
    try {
        const result = await api.put('api/settings.php', { settings });
        showToast(result.success ? 'Model settings saved' : 'Failed', result.success ? 'success' : 'error');
    } catch(e) { showToast('Failed', 'error'); }
}

async function saveThresholds() {
    const settings = {
        risk_threshold_safe: document.getElementById('threshold_safe').value,
        risk_threshold_suspicious: document.getElementById('threshold_suspicious').value,
    };
    try {
        const result = await api.put('api/settings.php', { settings });
        showToast(result.success ? 'Thresholds saved' : 'Failed', result.success ? 'success' : 'error');
    } catch(e) { showToast('Failed', 'error'); }
}
</script>
JS;
require_once __DIR__ . '/includes/footer.php';
?>
