// Spools view render function and handlers
import { options } from '../state.js';
import { API_BASE } from '../config.js';
import { showToast } from '../utils.js';
import { loadData } from '../api.js';

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

export async function renderSpools(v) {
    const container = document.createElement('div');
    container.className = "max-w-4xl mx-auto space-y-6";

    // Load spools and manufacturers
    let spools = [];
    let manufacturers = [];
    try {
        const [resSpools, resManuf] = await Promise.all([
            fetch(`${API_BASE}/spools/list.php`),
            fetch(`${API_BASE}/data/options.php`)
        ]);
        if (resSpools.ok) spools = await resSpools.json();
        if (resManuf.ok) {
            const data = await resManuf.json();
            if (data && typeof data === 'object' && !Array.isArray(data)) {
                const manufData = data.manufacturers;
                // Handle both formats: object with top/others or plain array
                if (manufData && typeof manufData === 'object' && !Array.isArray(manufData)) {
                    manufacturers = [...(manufData.top || []), ...(manufData.others || [])];
                } else {
                    manufacturers = Array.isArray(manufData) ? manufData : [];
                }
            }
        }
        // Ensure manufacturers is always an array
        if (!Array.isArray(manufacturers)) {
            manufacturers = [];
        }
    } catch (err) {
        console.error('Failed to load spools:', err);
    }

    container.innerHTML = `
        <!-- Add/Edit Form -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-slate-800 mb-4" id="spool-form-title">Přidat typ cívky</h2>
            <form id="spool-form" class="space-y-4">
                <input type="hidden" id="spool-id" value="">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Barva</label>
                        <input type="text" id="spool-color" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="např. Černá">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Materiál</label>
                        <input type="text" id="spool-material" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="např. Plast">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Ø vnější (mm)</label>
                        <input type="number" id="spool-diameter" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="200">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Šířka (mm)</label>
                        <input type="number" id="spool-width" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="70">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Hmotnost (g)</label>
                        <input type="number" id="spool-weight" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="240">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Výrobci (multiselect)</label>
                    <select multiple id="spool-manufacturers" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" style="min-height: 100px;">
                        ${manufacturers.map(m => typeof m === 'object' && m && 'id' in m ? `<option value="${m.id}">${escapeHtml(m.name)}</option>` : `<option value="${m}">${escapeHtml(String(m))}</option>`).join('')}
                    </select>
                    <div class="text-xs text-slate-500 mt-1">Držte Ctrl/Cmd pro výběr více výrobců</div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Poznámka</label>
                    <textarea id="spool-description" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" rows="2" placeholder="Doplňující informace..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="cancelSpoolEdit()" id="spool-cancel-btn" class="hidden flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold">Zrušit</button>
                    <button type="submit" class="flex-1 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200">Uložit</button>
                </div>
            </form>
        </div>

        <!-- Spools List -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-slate-800 mb-4">Existující typy</h2>
            <div class="space-y-2" id="spools-list">
                ${spools.length === 0 ? '<p class="text-slate-400 text-center py-4">Žádné typy cívek</p>' : spools.map(s => {
                    const isStandard = s.created_by === null;
                    const manufNames = s.manufacturers.map(m => m.name).join(', ') || 'Žádný výrobce';
                    const label = `${s.color || '?'} ${s.material || '?'} • Ø${s.outer_diameter_mm || '?'}mm × ${s.width_mm || '?'}mm • ${s.weight_grams || '?'}g`;
                    return `
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                        <div>
                            <div class="font-bold text-slate-800">${label}</div>
                            <div class="text-xs text-slate-500 mt-1">Výrobci: ${manufNames}</div>
                            ${s.visual_description ? `<div class="text-xs text-slate-400 mt-1">${s.visual_description}</div>` : ''}
                            ${isStandard ? '<div class="text-xs text-indigo-600 font-bold mt-1">STANDARDNÍ TYP</div>' : ''}
                        </div>
                        <div class="flex gap-2">
                            <button onclick="editSpool(${s.id})" class="px-3 py-2 bg-indigo-50 text-indigo-600 rounded-lg font-bold text-sm hover:bg-indigo-100">Upravit</button>
                            ${!isStandard ? `<button onclick="deleteSpool(${s.id})" class="px-3 py-2 bg-red-50 text-red-600 rounded-lg font-bold text-sm hover:bg-red-100">Smazat</button>` : ''}
                        </div>
                    </div>
                `}).join('')}
            </div>
        </div>

        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;

    v.appendChild(container);

    // Attach form handler
    document.getElementById('spool-form').onsubmit = handleSpoolSubmit;
}

// Spool management handlers
export async function handleSpoolSubmit(e) {
    e.preventDefault();

    const spoolId = document.getElementById('spool-id').value;
    const color = document.getElementById('spool-color').value;
    const material = document.getElementById('spool-material').value;
    const diameter = parseInt(document.getElementById('spool-diameter').value) || null;
    const width = parseInt(document.getElementById('spool-width').value) || null;
    const weight = parseInt(document.getElementById('spool-weight').value) || null;
    const description = document.getElementById('spool-description').value;

    // Get selected manufacturers
    const manufSelect = document.getElementById('spool-manufacturers');
    const selectedManuf = Array.from(manufSelect.selectedOptions).map(o => o.value);

    const manufIds = selectedManuf.filter(v => v !== '').map(v => /^\d+$/.test(String(v)) ? parseInt(v, 10) : v);
    const manufacturerIds = manufIds.filter(v => typeof v === 'number');
    const manufacturerNames = manufIds.filter(v => typeof v === 'string');

    const payload = {
        color,
        material,
        outer_diameter_mm: diameter,
        width_mm: width,
        weight_grams: weight,
        visual_description: description
    };
    if (manufacturerIds.length > 0) payload.manufacturer_ids = manufacturerIds;
    if (manufacturerNames.length > 0) payload.manufacturer_names = manufacturerNames;

    if (spoolId) {
        payload.id = parseInt(spoolId);
    }

    const endpoint = spoolId ? '/spools/update.php' : '/spools/create.php';

    try {
        const res = await fetch(`${API_BASE}${endpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (res.ok) {
            showToast(spoolId ? 'Typ cívky aktualizován' : 'Typ cívky přidán');
            await loadData();
            if (window.render) window.render();
        } else {
            showToast(data.error || 'Chyba při ukládání');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

export async function editSpool(spoolId) {
    try {
        const res = await fetch(`${API_BASE}/spools/list.php`);
        if (!res.ok) {
            showToast('Chyba načítání typů cívek');
            return;
        }
        const spools = await res.json();
        const spool = spools.find(s => s.id === spoolId);

        if (!spool) {
            showToast('Typ cívky nenalezen');
            return;
        }

        // Fill form
        document.getElementById('spool-id').value = spool.id;
        document.getElementById('spool-color').value = spool.color || '';
        document.getElementById('spool-material').value = spool.material || '';
        document.getElementById('spool-diameter').value = spool.outer_diameter_mm || '';
        document.getElementById('spool-width').value = spool.width_mm || '';
        document.getElementById('spool-weight').value = spool.weight_grams || '';
        document.getElementById('spool-description').value = spool.visual_description || '';

        // Select manufacturers (by id; spool.manufacturers have id and name)
        const manufSelect = document.getElementById('spool-manufacturers');
        const manufIds = (spool.manufacturers || []).map(m => m.id != null ? m.id : m).filter(Boolean);
        Array.from(manufSelect.options).forEach(opt => {
            opt.selected = manufIds.some(id => id == opt.value);
        });

        // Update form title and show cancel button
        document.getElementById('spool-form-title').textContent = 'Upravit typ cívky';
        document.getElementById('spool-cancel-btn').classList.remove('hidden');

        // Scroll to form
        document.getElementById('spool-form').scrollIntoView({ behavior: 'smooth' });
    } catch (err) {
        showToast('Chyba sítě');
    }
}

export function cancelSpoolEdit() {
    document.getElementById('spool-form').reset();
    document.getElementById('spool-id').value = '';
    document.getElementById('spool-form-title').textContent = 'Přidat typ cívky';
    document.getElementById('spool-cancel-btn').classList.add('hidden');
}

export async function deleteSpool(spoolId) {
    if (!confirm('Opravdu chcete smazat tento typ cívky?')) {
        return;
    }

    try {
        const res = await fetch(`${API_BASE}/spools/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: spoolId })
        });
        const data = await res.json();

        if (res.ok) {
            showToast('Typ cívky smazán');
            await loadData();
            if (window.render) window.render();
        } else {
            showToast(data.error || 'Chyba při mazání');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}
