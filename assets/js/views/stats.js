// Stats view render function
import { stats } from '../state.js';
import { API_BASE, BASE_PATH } from '../config.js';
import { formatKg } from '../utils.js';
import { t, getCurrencyUnit } from '../i18n.js';

const PIE_COLORS = ['#2563eb', '#16a34a', '#dc2626', '#ea580c', '#0891b2', '#7c3aed', '#ca8a04', '#4b5563'];

function buildPieGradient(materialDistribution) {
    const total = materialDistribution.reduce((sum, row) => sum + Number(row.remaining_weight), 0);
    if (total <= 0) return 'transparent';
    let acc = 0;
    const parts = materialDistribution.map((row, i) => {
        const pct = (Number(row.remaining_weight) / total) * 100;
        const start = acc;
        acc += pct;
        return `${PIE_COLORS[i % PIE_COLORS.length]} ${start.toFixed(2)}% ${acc.toFixed(2)}%`;
    });
    return `conic-gradient(from 0deg, ${parts.join(', ')})`;
}

function formatChartDate(dateStr) {
    const d = new Date(dateStr + 'T12:00:00');
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    return `${day}.${month}`;
}

function escapeHtml(s) {
    const t = String(s ?? '');
    return t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

let statsRenderCounter = 0;

export async function renderStats(v) {
    const callId = ++statsRenderCounter;

    if(!stats) {
        v.innerHTML = `<p class="text-center p-10">${t('stats.noData')}</p>`;
        return;
    }

    v.innerHTML = '';

    const materialDist = Array.isArray(stats.material_distribution) ? stats.material_distribution : [];
    const consumptionByDay = Array.isArray(stats.consumption_by_day) ? stats.consumption_by_day : [];
    const maxConsumed = consumptionByDay.length
        ? Math.max(...consumptionByDay.map(d => Number(d.total_grams)), 1)
        : 1;

    const container = document.createElement('div');
    container.className = "space-y-4";
    container.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase">${t('stats.totalStock')}</div>
                <div class="text-2xl font-black text-indigo-600 mt-1">${formatKg(stats.total_weight_grams)}</div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase">${t('stats.estimatedValue')}</div>
                <div class="text-2xl font-black text-slate-800 mt-1">${stats.total_value_czk} ${getCurrencyUnit()}</div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase">${t('stats.spoolCount')}</div>
                <div class="text-2xl font-black text-slate-800 mt-1">${stats.total_count} ${t('stats.pcs')}</div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase">${t('stats.consumption30d')}</div>
                <div class="text-2xl font-black text-slate-800 mt-1">${formatKg(stats.consumed_30_days_grams)}</div>
            </div>
        </div>

        ${materialDist.length > 0 ? `
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h3 class="text-lg font-black text-slate-800 mb-4">${t('stats.materialDistribution')}</h3>
            <div class="flex flex-wrap items-center gap-6">
                <div class="w-40 h-40 rounded-full border-4 border-white shadow-inner flex-shrink-0" style="background: ${buildPieGradient(materialDist)}"></div>
                <ul class="flex-1 min-w-0 space-y-1 text-sm">
                    ${materialDist.map((row, i) => `
                        <li class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full flex-shrink-0" style="background: ${PIE_COLORS[i % PIE_COLORS.length]}"></span>
                            <span class="font-medium text-slate-800">${escapeHtml(row.material ?? '—')}</span>
                            <span class="text-slate-500 ml-auto">${formatKg(Number(row.remaining_weight))}</span>
                        </li>
                    `).join('')}
                </ul>
            </div>
        </div>
        ` : ''}

        <div class="bg-indigo-50 p-4 rounded-2xl border border-indigo-100 text-center space-y-2">
            <h3 class="font-bold text-indigo-900">${t('stats.sharingTitle')}</h3>
            <p class="text-xs text-indigo-600">${t('stats.sharingDesc')}</p>
            <button onclick="generateShareCode()" class="bg-white text-indigo-600 px-4 py-2 rounded-xl font-bold text-sm shadow-sm">${t('stats.generateCode')}</button>
            <div id="share-section" class="hidden mt-2 pt-2 border-t border-indigo-200">
                <div class="text-xs text-slate-400 uppercase font-bold">${t('stats.yourCode')}:</div>
                <div id="share-code-display" class="text-xl font-black tracking-widest select-all"></div>
            </div>
        </div>
    `;
    v.appendChild(container);

    // Historie čerpání: sloupcový graf (po dnech) + tabulka
    const historyContainer = document.createElement('div');
    historyContainer.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200";

    const barChartHtml = consumptionByDay.length > 0
        ? `
        <h3 class="text-lg font-black text-slate-800 mb-4">${t('stats.consumptionByDay')}</h3>
        <div class="flex items-end gap-1 mb-6" style="height: 8rem;" role="img" aria-label="${t('stats.consumptionByDay')}">
            ${consumptionByDay.map(d => {
                const grams = Number(d.total_grams);
                const heightPct = maxConsumed > 0 ? Math.round((grams / maxConsumed) * 100) : 0;
                return `
                <div class="flex-1 min-w-0 flex flex-col items-center gap-1 h-full">
                    <div class="w-full flex-1 min-h-[2px] flex flex-col justify-end rounded-t">
                        <div class="w-full bg-indigo-600 rounded-t flex-shrink-0" style="height: ${Math.max(heightPct, 2)}%"></div>
                    </div>
                    <span class="text-[10px] text-slate-500 truncate w-full text-center flex-shrink-0" title="${escapeHtml(d.date)}">${formatChartDate(d.date)}</span>
                </div>
                `;
            }).join('')}
        </div>
        `
        : `<h3 class="text-lg font-black text-slate-800 mb-4">${t('stats.consumptionHistory')}</h3><p class="text-slate-500 text-sm mb-4">${t('stats.noConsumptionIn30')}</p>`;

    historyContainer.innerHTML = barChartHtml;

    try {
        const res = await fetch(`${API_BASE}/consumption/list.php`);
        if (res.ok) {
            const history = await res.json();
            if (callId !== statsRenderCounter) {
                return;
            }
            if (Array.isArray(history) && history.length > 0) {
                const tableHtml = `
                    <h3 class="text-lg font-black text-slate-800 mb-4">${t('stats.consumptionTable', { count: history.length })}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left border-b border-slate-200">
                                <tr>
                                    <th class="pb-2 font-bold text-slate-500 uppercase text-xs">${t('stats.date')}</th>
                                    <th class="pb-2 font-bold text-slate-500 uppercase text-xs">${t('stats.filament')}</th>
                                    <th class="pb-2 font-bold text-slate-500 uppercase text-xs">${t('stats.consumption')}</th>
                                    <th class="pb-2 font-bold text-slate-500 uppercase text-xs">${t('stats.description')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${history.map(h => `
                                    <tr class="border-b border-slate-100">
                                        <td class="py-3 text-slate-600">${escapeHtml(h.consumption_date)}</td>
                                        <td class="py-3">
                                            <div class="font-bold text-slate-800">${escapeHtml(h.manufacturer ?? '')}</div>
                                            <div class="text-xs text-slate-500">${escapeHtml(h.material ?? '')} • ${escapeHtml(h.color ?? '')}</div>
                                        </td>
                                        <td class="py-3 font-bold text-indigo-600">${escapeHtml(String(h.consumed_weight))}g</td>
                                        <td class="py-3 text-slate-600 text-xs">${escapeHtml(h.note || '-')}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                historyContainer.insertAdjacentHTML('beforeend', tableHtml);
            }
        }
    } catch (err) {
        console.error('Failed to load consumption history:', err);
    }

    v.appendChild(historyContainer);

    const backBtn = document.createElement('button');
    backBtn.onclick = () => {
        if (window.resetApp) window.resetApp();
    };
    backBtn.className = 'w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm mt-4';
    backBtn.textContent = t('inventorySwitch.backToStock');
    v.appendChild(backBtn);
}
