/**
 * AI Prompt Security Gateway — Application JavaScript
 */

// ── API Base URL ──
const API_BASE = 'api';

// ── Utility Functions ──
const api = {
    async get(endpoint, params = {}) {
        const url = new URL(endpoint, window.location.href);
        Object.entries(params).forEach(([k, v]) => {
            if (v !== '' && v !== null && v !== undefined) url.searchParams.set(k, v);
        });
        const res = await fetch(url);
        return res.json();
    },

    async post(endpoint, data) {
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return res.json();
    },

    async put(endpoint, data) {
        const res = await fetch(endpoint, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return res.json();
    },

    async patch(endpoint) {
        const res = await fetch(endpoint, { method: 'PATCH' });
        return res.json();
    },

    async delete(endpoint) {
        const res = await fetch(endpoint, { method: 'DELETE' });
        return res.json();
    }
};

// ── Sidebar Toggle ──
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // Close sidebar on outside click (mobile)
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1024 && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });
    }

    // Load topbar stats
    loadTopbarStats();
});

// ── Topbar Stats ──
async function loadTopbarStats() {
    try {
        const data = await api.get(`${API_BASE}/stats.php`);
        if (data.success) {
            const stats = data.stats;
            animateCounter('topbar-total-scans', stats.total_scans);
            animateCounter('topbar-blocked', stats.verdicts.blocked);
            animateCounter('topbar-safe', stats.verdicts.safe);
        }
    } catch (e) {
        console.log('Stats not loaded yet');
    }
}

// ── Animate Counter ──
function animateCounter(elementId, target, duration = 1000) {
    const el = document.getElementById(elementId);
    if (!el) return;
    
    const start = parseInt(el.textContent) || 0;
    const increment = (target - start) / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= target) || (increment < 0 && current <= target)) {
            current = target;
            clearInterval(timer);
        }
        el.textContent = Math.round(current).toLocaleString();
    }, 16);
}

// ── Chart Defaults ──
if (typeof Chart !== 'undefined') {
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.04)';
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
    Chart.defaults.plugins.legend.labels.padding = 20;
}

// ── Create Trend Chart ──
function createTrendChart(canvasId, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(d => {
                const date = new Date(d.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }),
            datasets: [
                {
                    label: 'Safe',
                    data: data.map(d => d.safe_count),
                    borderColor: '#34c759',
                    backgroundColor: 'rgba(52, 199, 89, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                },
                {
                    label: 'Suspicious',
                    data: data.map(d => d.suspicious_count),
                    borderColor: '#ff9500',
                    backgroundColor: 'rgba(255, 149, 0, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                },
                {
                    label: 'Blocked',
                    data: data.map(d => d.blocked_count),
                    borderColor: '#ff3b30',
                    backgroundColor: 'rgba(255, 59, 48, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { maxTicksLimit: 10 }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.03)' },
                    ticks: { stepSize: 1 }
                }
            },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    borderColor: 'rgba(99, 102, 241, 0.2)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                }
            }
        }
    });
}

// ── Create Doughnut Chart ──
function createDoughnutChart(canvasId, labels, data, colors) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderColor: 'rgba(17, 24, 39, 0.8)',
                borderWidth: 3,
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 16,
                        font: { size: 12 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.95)',
                    borderColor: 'rgba(99, 102, 241, 0.2)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                }
            }
        }
    });
}

// ── Risk Score Color ──
function getRiskColor(score) {
    if (score <= 30) return '#34c759';
    if (score <= 65) return '#ff9500';
    return '#ff3b30';
}

// ── Verdict Display ──
function getVerdictBadge(verdict) {
    const icons = { safe: 'check-circle', suspicious: 'exclamation-triangle', blocked: 'ban' };
    return `<span class="verdict-badge ${verdict}">
        <i class="fas fa-${icons[verdict] || 'question'}"></i> ${verdict}
    </span>`;
}

// ── Severity Badge ──
function getSeverityBadge(severity) {
    return `<span class="severity-badge ${severity}">${severity}</span>`;
}

// ── Format Date ──
function formatDate(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-US', { 
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

// ── Truncate Text ──
function truncateText(text, maxLen = 80) {
    if (!text) return '';
    return text.length > maxLen ? text.substring(0, maxLen) + '...' : text;
}

// ── Toast Notification ──
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add styles
    Object.assign(toast.style, {
        position: 'fixed',
        bottom: '24px',
        right: '24px',
        padding: '12px 20px',
        borderRadius: '8px',
        display: 'flex',
        alignItems: 'center',
        gap: '8px',
        fontSize: '14px',
        fontWeight: '500',
        zIndex: '10000',
        animation: 'fadeInUp 0.3s ease',
        background: type === 'success' ? 'rgba(52, 199, 89, 0.15)' : 
                    type === 'error' ? 'rgba(255, 59, 48, 0.15)' : 'rgba(90, 200, 250, 0.15)',
        color: type === 'success' ? '#34c759' : 
               type === 'error' ? '#ff3b30' : '#5ac8fa',
        border: `1px solid ${type === 'success' ? 'rgba(52, 199, 89, 0.3)' :
                 type === 'error' ? 'rgba(255, 59, 48, 0.3)' : 'rgba(90, 200, 250, 0.3)'}`,
        backdropFilter: 'blur(10px)',
    });

    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = '0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ── Escape HTML ──
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
