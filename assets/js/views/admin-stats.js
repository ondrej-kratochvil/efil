// Admin stats view render function
import { API_BASE } from '../config.js';

export async function renderAdminStats(v) {
    const container = document.createElement('div');
    container.className = "max-w-6xl mx-auto space-y-6";

    // Load stats
    let stats = null;
    try {
        const res = await fetch(`${API_BASE}/admin/stats.php`);
        if (res.ok) {
            stats = await res.json();
        } else {
            const err = await res.json();
            container.innerHTML = `
                <div class="bg-red-50 p-6 rounded-3xl border border-red-200">
                    <p class="text-red-600 font-bold">${err.error || 'Nedostatečná oprávnění'}</p>
                    <button onclick="window.resetApp()" class="mt-4 w-full py-3 bg-slate-100 text-slate-600 rounded-xl font-bold">Zpět</button>
                </div>
            `;
            v.appendChild(container);
            return;
        }
    } catch (err) {
        console.error('Failed to load stats:', err);
    }

    if (!stats) {
        container.innerHTML = '<p class="text-slate-400 text-center py-8">Načítání statistik...</p>';
        v.appendChild(container);
        return;
    }

    container.innerHTML = `
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6 rounded-3xl shadow-lg text-white">
            <h1 class="text-3xl font-black mb-2">📊 Statistiky eFil</h1>
            <p class="opacity-90">Celkový přehled využívání aplikace</p>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <div class="text-sm text-slate-500 font-bold uppercase">Uživatelé</div>
                <div class="text-3xl font-black text-indigo-600 mt-1">${stats.total_users}</div>
                <div class="text-xs text-slate-400 mt-1">+${stats.recent_users} za 30 dní</div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <div class="text-sm text-slate-500 font-bold uppercase">Evidence</div>
                <div class="text-3xl font-black text-purple-600 mt-1">${stats.total_inventories}</div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <div class="text-sm text-slate-500 font-bold uppercase">Filamenty</div>
                <div class="text-3xl font-black text-pink-600 mt-1">${stats.total_filaments}</div>
                <div class="text-xs text-slate-400 mt-1">${stats.total_weight_kg} kg celkem</div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <div class="text-sm text-slate-500 font-bold uppercase">Spotřeba</div>
                <div class="text-3xl font-black text-amber-600 mt-1">${stats.total_consumed_kg}</div>
                <div class="text-xs text-slate-400 mt-1">${stats.total_consumptions} záznamů</div>
            </div>
        </div>

        <!-- Activity Stats -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Top Inventories -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                <h2 class="text-xl font-black text-slate-800 mb-4">🏆 Top 10 evidencí</h2>
                <div class="space-y-2">
                    ${stats.top_inventories.length === 0 ? '<p class="text-slate-400">Žádné evidence</p>' : stats.top_inventories.map((inv, idx) => `
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-black flex items-center justify-center text-sm">
                                    ${idx + 1}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800">${inv.name || 'Evidence #' + inv.id}</div>
                                    <div class="text-xs text-slate-500">${inv.filament_count} filamentů • ${Math.round(inv.total_weight / 1000 * 10) / 10} kg</div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>

            <!-- Material Distribution -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                <h2 class="text-xl font-black text-slate-800 mb-4">📦 Materiály</h2>
                <div class="space-y-2">
                    ${stats.material_distribution.length === 0 ? '<p class="text-slate-400">Žádné materiály</p>' : stats.material_distribution.map(mat => {
                        const percent = Math.round((mat.count / stats.total_filaments) * 100);
                        return `
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-bold text-slate-700">${mat.material}</span>
                                <span class="text-slate-500">${mat.count}× • ${Math.round(mat.total_weight / 1000 * 10) / 10} kg</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="bg-indigo-500 h-2 rounded-full" style="width: ${percent}%"></div>
                            </div>
                        </div>
                    `}).join('')}
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-slate-800 mb-4">⚡ Poslední aktivita</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left border-b border-slate-200">
                        <tr>
                            <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Datum</th>
                            <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Filament</th>
                            <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Spotřeba</th>
                            <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Evidence</th>
                            <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Uživatel</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${stats.recent_activity.length === 0 ? '<tr><td colspan="5" class="py-4 text-slate-400 text-center">Žádná aktivita</td></tr>' : stats.recent_activity.map(act => `
                            <tr class="border-b border-slate-100">
                                <td class="py-3 text-slate-600">${act.consumption_date}</td>
                                <td class="py-3">
                                    <div class="font-bold text-slate-800">${act.manufacturer}</div>
                                    <div class="text-xs text-slate-500">${act.material} • ${act.color}</div>
                                </td>
                                <td class="py-3 font-bold text-indigo-600">${act.consumed_weight}g</td>
                                <td class="py-3 text-slate-600">${act.inventory_name || '-'}</td>
                                <td class="py-3 text-slate-500 text-xs">${act.user_email || '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>

        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;

    v.appendChild(container);
}
