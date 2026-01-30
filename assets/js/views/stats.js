// Stats view render function
import { stats } from '../state.js';
import { API_BASE, BASE_PATH } from '../config.js';
import { formatKg } from '../utils.js';

export async function renderStats(v) {
    if(!stats) {
        v.innerHTML = '<p class="text-center p-10">Žádná data</p>';
        return;
    }

    const container = document.createElement('div');
    container.className = "space-y-4";
    container.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase">Celkem na skladě</div>
                <div class="text-2xl font-black text-indigo-600 mt-1">${formatKg(stats.total_weight_grams)}</div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase">Odhad hodnoty</div>
                <div class="text-2xl font-black text-slate-800 mt-1">${stats.total_value_czk} Kč</div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase">Počet cívek</div>
                <div class="text-2xl font-black text-slate-800 mt-1">${stats.total_count} ks</div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase">Spotřeba (30 dní)</div>
                <div class="text-2xl font-black text-slate-800 mt-1">${formatKg(stats.consumed_30_days_grams)}</div>
            </div>
        </div>

        <div class="bg-indigo-50 p-4 rounded-2xl border border-indigo-100 text-center space-y-2">
            <h3 class="font-bold text-indigo-900">Sdílení skladu</h3>
            <p class="text-xs text-indigo-600">Vygenerujte kód pro kolegy, aby mohli spravovat tento sklad.</p>
            <button onclick="generateShareCode()" class="bg-white text-indigo-600 px-4 py-2 rounded-xl font-bold text-sm shadow-sm">Vygenerovat kód</button>
            <div id="share-section" class="hidden mt-2 pt-2 border-t border-indigo-200">
                <div class="text-xs text-slate-400 uppercase font-bold">Váš kód:</div>
                <div id="share-code-display" class="text-xl font-black tracking-widest select-all"></div>
            </div>
        </div>
    `;
    v.appendChild(container);

    // Load and display consumption history for inventory
    try {
        const res = await fetch(`${API_BASE}/consumption/list.php`);
        if (res.ok) {
            const history = await res.json();
            // Ensure history is an array
            if (!Array.isArray(history)) return;

            if (history.length > 0) {
                const historyContainer = document.createElement('div');
                historyContainer.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200";
                historyContainer.innerHTML = `
                    <h3 class="text-lg font-black text-slate-800 mb-4">Historie čerpání (posledních ${history.length})</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left border-b border-slate-200">
                                <tr>
                                    <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Datum</th>
                                    <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Filament</th>
                                    <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Spotřeba</th>
                                    <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Poznámka</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${history.map(h => `
                                    <tr class="border-b border-slate-100">
                                        <td class="py-3 text-slate-600">${h.consumption_date}</td>
                                        <td class="py-3">
                                            <div class="font-bold text-slate-800">${h.manufacturer}</div>
                                            <div class="text-xs text-slate-500">${h.material} • ${h.color}</div>
                                        </td>
                                        <td class="py-3 font-bold text-indigo-600">${h.consumed_weight}g</td>
                                        <td class="py-3 text-slate-600 text-xs">${h.note || '-'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                v.appendChild(historyContainer);
            }
        }
    } catch (err) {
        console.error('Failed to load consumption history:', err);
    }

    const backBtn = document.createElement('button');
    backBtn.onclick = () => {
        if (window.resetApp) window.resetApp();
    };
    backBtn.className = 'w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm mt-4';
    backBtn.textContent = 'Zpět na sklad';
    v.appendChild(backBtn);
}
