// Manufacturers view – správa výrobců (seznam, přidat, upravit, smazat; admin: čekající návrhy)
import { user } from '../state.js';
import { API_BASE } from '../config.js';
import { showToast } from '../utils.js';
import { loadData } from '../api.js';

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

export async function renderManufacturers(container) {
    const isAdmin = user && user.role === 'admin_efil';

    let list = [];
    let pending = [];
    try {
        const resList = await fetch(`${API_BASE}/manufacturers/list.php`);
        if (resList.ok) list = await resList.json();
        if (!Array.isArray(list)) list = [];

        if (isAdmin) {
            const resPending = await fetch(`${API_BASE}/manufacturers/pending.php`);
            if (resPending.ok) pending = await resPending.json();
            if (!Array.isArray(pending)) pending = [];
        }
    } catch (err) {
        console.error('Failed to load manufacturers:', err);
        showToast('Chyba načítání výrobců');
    }

    container.innerHTML = `
        <!-- Přidat / Upravit -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-slate-800 mb-4" id="manufacturer-form-title">Přidat výrobce</h2>
            <form id="manufacturer-form" class="space-y-4">
                <input type="hidden" id="manufacturer-id" value="">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Název</label>
                    <input type="text" id="manufacturer-name" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="např. Prusa Research" required>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="cancelManufacturerEdit()" id="manufacturer-cancel-btn" class="hidden flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold">Zrušit</button>
                    <button type="submit" class="flex-1 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200">Uložit</button>
                </div>
            </form>
        </div>

        <!-- Seznam výrobců -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-slate-800 mb-4">Výrobci</h2>
            <div class="space-y-2" id="manufacturers-list">
                ${list.length === 0
                    ? '<p class="text-slate-400 text-center py-4">Žádní výrobci. Přidejte prvního výše.</p>'
                    : list.map(m => {
                        const nameAttr = String(m.name).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
                        return `
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl" data-manufacturer-id="${m.id}" data-manufacturer-name="${nameAttr}">
                        <div class="font-bold text-slate-800">${escapeHtml(m.name)}</div>
                        <div class="flex gap-2">
                            <button type="button" onclick="editManufacturer(this)" class="px-3 py-2 bg-indigo-50 text-indigo-600 rounded-lg font-bold text-sm hover:bg-indigo-100">Upravit</button>
                            <button type="button" onclick="deleteManufacturer(${m.id})" class="px-3 py-2 bg-red-50 text-red-600 rounded-lg font-bold text-sm hover:bg-red-100">Smazat</button>
                        </div>
                    </div>
                `;
                    }).join('')}
            </div>
        </div>

        ${isAdmin && pending.length > 0 ? `
        <!-- Čekající návrhy (admin) -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-amber-200">
            <h2 class="text-xl font-black text-slate-800 mb-4">Čekající návrhy na změnu</h2>
            <div class="space-y-2" id="manufacturers-pending">
                ${pending.map(p => `
                <div class="flex items-center justify-between p-4 bg-amber-50 rounded-xl">
                    <div>
                        <div class="font-bold text-slate-800">${escapeHtml(p.proposed_name)}</div>
                        <div class="text-xs text-slate-500 mt-1">Aktuálně: ${escapeHtml(p.current_approved_name || '—')} • Návrh od: ${escapeHtml(p.proposed_by_email || '?')}</div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="approveManufacturer(${p.id})" class="px-3 py-2 bg-green-50 text-green-600 rounded-lg font-bold text-sm hover:bg-green-100">Schválit</button>
                        <button onclick="rejectManufacturer(${p.id})" class="px-3 py-2 bg-red-50 text-red-600 rounded-lg font-bold text-sm hover:bg-red-100">Zamítnout</button>
                    </div>
                </div>
                `).join('')}
            </div>
        </div>
        ` : ''}

        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;

    container.querySelector('#manufacturer-form').onsubmit = (e) => {
        e.preventDefault();
        handleManufacturerSubmit(e);
    };
}

export async function handleManufacturerSubmit(e) {
    e.preventDefault();
    const idEl = document.getElementById('manufacturer-id');
    const nameEl = document.getElementById('manufacturer-name');
    const name = (nameEl && nameEl.value) ? nameEl.value.trim() : '';
    if (!name) {
        showToast('Zadejte název výrobce');
        return;
    }

    const manufacturerId = idEl && idEl.value ? parseInt(idEl.value, 10) : 0;
    const isEdit = manufacturerId > 0;

    const url = isEdit ? `${API_BASE}/manufacturers/update.php` : `${API_BASE}/manufacturers/create.php`;
    const body = isEdit ? { id: manufacturerId, name } : { name };

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await res.json();

        if (res.ok) {
            showToast(data.message || (isEdit ? 'Výrobce upraven' : 'Výrobce přidán'));
            await loadData();
            if (window.render) window.render();
        } else {
            showToast(data.error || 'Chyba při ukládání');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

export function editManufacturer(buttonOrId) {
    let id, name;
    if (typeof buttonOrId === 'object' && buttonOrId && buttonOrId.nodeType === 1) {
        const row = buttonOrId.closest('[data-manufacturer-id]');
        if (!row) return;
        id = row.getAttribute('data-manufacturer-id');
        name = row.getAttribute('data-manufacturer-name') || '';
    } else {
        id = buttonOrId;
        name = '';
    }
    const idEl = document.getElementById('manufacturer-id');
    const nameEl = document.getElementById('manufacturer-name');
    const titleEl = document.getElementById('manufacturer-form-title');
    const cancelBtn = document.getElementById('manufacturer-cancel-btn');
    if (idEl) idEl.value = id;
    if (nameEl) nameEl.value = name;
    if (titleEl) titleEl.textContent = 'Upravit výrobce';
    if (cancelBtn) cancelBtn.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setTimeout(() => nameEl?.focus(), 400);
}

export function cancelManufacturerEdit() {
    const form = document.getElementById('manufacturer-form');
    if (form) form.reset();
    const idEl = document.getElementById('manufacturer-id');
    const titleEl = document.getElementById('manufacturer-form-title');
    const cancelBtn = document.getElementById('manufacturer-cancel-btn');
    if (idEl) idEl.value = '';
    if (titleEl) titleEl.textContent = 'Přidat výrobce';
    if (cancelBtn) cancelBtn.classList.add('hidden');
}

export async function deleteManufacturer(id) {
    if (!confirm('Opravdu chcete smazat tohoto výrobce?')) return;
    try {
        const res = await fetch(`${API_BASE}/manufacturers/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        const data = await res.json();
        if (res.ok) {
            showToast(data.message || 'Výrobce smazán');
            await loadData();
            if (window.render) window.render();
        } else {
            showToast(data.error || 'Chyba při mazání');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

export async function approveManufacturer(id) {
    try {
        const res = await fetch(`${API_BASE}/manufacturers/approve.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        const data = await res.json();
        if (res.ok) {
            showToast(data.message || 'Návrh schválen');
            if (window.render) window.render();
        } else {
            showToast(data.error || 'Chyba');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

export async function rejectManufacturer(id) {
    if (!confirm('Zamítnout tento návrh?')) return;
    try {
        const res = await fetch(`${API_BASE}/manufacturers/reject.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id }),
        });
        const data = await res.json();
        if (res.ok) {
            showToast(data.message || 'Návrh zamítnut');
            if (window.render) window.render();
        } else {
            showToast(data.error || 'Chyba');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}
