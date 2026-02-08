// Inventory switch view render function and handler
import { API_BASE, BASE_PATH } from '../config.js';
import { showToast } from '../utils.js';
import { loadData } from '../api.js';
import { router } from '../router.js';
import { t } from '../i18n.js';

export async function renderInventorySwitch(v) {
    const container = document.createElement('div');
    container.className = "max-w-2xl mx-auto space-y-4";

    // Load inventories
    let inventories = [];
    try {
        const res = await fetch(`${API_BASE}/inventory/list.php`);
        if (res.ok) {
            inventories = await res.json();
        }
    } catch (err) {
        console.error('Failed to load inventories:', err);
    }

    const roleKeys = { owner: 'inventorySwitch.owner', manage: 'inventorySwitch.manage', write: 'inventorySwitch.write', read: 'inventorySwitch.read' };

    container.innerHTML = `
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-2xl font-black text-slate-800 mb-4">${t('inventorySwitch.title')}</h2>
            <div class="space-y-3">
                ${inventories.length === 0 ? `<p class="text-slate-400 text-center py-4">${t('common.loading')}</p>` : inventories.map(inv => `
                    <button
                        onclick="handleSwitchInventory(${inv.id})"
                        class="w-full flex items-center justify-between p-4 rounded-xl border-2 transition-all ${inv.is_current ? 'border-indigo-600 bg-indigo-50' : 'border-slate-200 bg-white hover:border-indigo-300'}"
                        ${inv.is_current ? 'disabled' : ''}>
                        <div class="text-left">
                            <div class="font-bold text-slate-900 flex items-center gap-2">
                                ${inv.name || t('inventorySwitch.inventoryLabel', { id: inv.id })}
                                ${inv.is_current ? `<span class="text-xs bg-indigo-600 text-white px-2 py-0.5 rounded font-bold">${t('inventorySwitch.active')}</span>` : ''}
                                ${inv.is_demo ? `<span class="text-xs bg-amber-100 text-amber-600 px-2 py-0.5 rounded font-bold">${t('inventorySwitch.demo')}</span>` : ''}
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                ${inv.is_owner ? `<span class="bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded font-bold">${t('inventorySwitch.owner')}</span>` : `<span>${roleKeys[inv.role] ? t(roleKeys[inv.role]) : inv.role}</span>`}
                            </div>
                        </div>
                        ${!inv.is_current ? `
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-slate-400">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        ` : ''}
                    </button>
                `).join('')}
            </div>
        </div>

        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">${t('inventorySwitch.backToStock')}</button>
    `;

    v.appendChild(container);
}

// Handle inventory switch
export async function handleSwitchInventory(inventoryId) {
    try {
        const res = await fetch(`${API_BASE}/inventory/switch.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ inventory_id: inventoryId })
        });
        const data = await res.json();

        if (res.ok) {
            showToast(t('inventorySwitch.switched'));
            await loadData();
            router.push(BASE_PATH + '/wizard/mat');
        } else {
            showToast(data.error || t('inventorySwitch.errorSwitch'));
        }
    } catch (err) {
        showToast(t('inventorySwitch.errorNetwork'));
    }
}
