// Consume view render function
import { state, filaments } from '../state.js';
import { API_BASE, BASE_PATH } from '../config.js';
import { router } from '../router.js';
import { showToast } from '../utils.js';
import { loadData } from '../api.js';

export async function renderConsume(v) {
    const currentConsumeId = state.consumeId;
    const item = filaments.find(i => i.id === currentConsumeId);
    if (!item) { 
        router.push(BASE_PATH + '/wizard/mat'); 
        return; 
    }

    const isUsed = state.consumeMode === 'used';
    const hasSpool = !!item.spool_id;
    const spoolWeight = item.spool_weight || 0;
    const grossWeight = item.g + spoolWeight;

    const container = document.createElement('div');
    container.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200 max-w-lg mx-auto space-y-6";
    container.innerHTML = `
        <div class="text-center">
            <h2 class="text-xl font-black text-slate-800">${item.mat} ${item.color}</h2>
            <div class="text-sm text-slate-500 font-bold uppercase mt-1">Aktuálně: ${item.g}g (Netto)</div>
        </div>

        <div class="flex p-1 bg-slate-100 rounded-xl">
            <button onclick="setConsumeMode('used')" class="flex-1 py-2 rounded-lg font-bold text-sm transition-all ${isUsed ? 'bg-white shadow text-indigo-600' : 'text-slate-500'}">Přesný úbytek</button>
            <button onclick="setConsumeMode('weight')" class="flex-1 py-2 rounded-lg font-bold text-sm transition-all ${!isUsed ? 'bg-white shadow text-indigo-600' : 'text-slate-500'}">Vážení s cívkou</button>
        </div>

        <div class="space-y-4">
            ${!isUsed && !hasSpool ? `<div class="bg-amber-50 text-amber-600 p-3 rounded-xl text-xs font-bold border border-amber-100">⚠ Pozor: U této cívky není nastavena Tára. Výpočet bude nepřesný (bude se počítat Tára 0g).</div>` : ''}
            ${!isUsed && hasSpool ? `<div class="bg-indigo-50 text-indigo-600 p-3 rounded-xl text-xs font-bold border border-indigo-100">ℹ️ Tára cívky: ${spoolWeight}g - bude automaticky odečtena od zadané hmotnosti</div>` : ''}

            <div class="text-center">
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2">
                    ${isUsed ? 'Kolik gramů jste spotřebovali? (zadejte úbytek)' : 'Kolik váží cívka s filamentem? (váha na váze)'}
                </label>
                <div class="flex items-center justify-center gap-2">
                    <input id="c-val" type="number" autofocus class="w-32 text-center text-3xl font-black bg-slate-50 border-none rounded-2xl p-4 focus:ring-2 ring-indigo-500 outline-none" placeholder="0">
                    <span class="text-xl font-bold text-slate-300">g</span>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Datum čerpání</label>
                <input id="c-date" type="date" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold text-sm" value="${new Date().toISOString().split('T')[0]}">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Poznámka (Volitelné)</label>
                <input id="c-desc" type="text" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold text-sm" placeholder="Např. Projekt XY">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button onclick="window.resetApp()" class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold">Zrušit</button>
            <button onclick="window.handleConsumeSubmit(event)" class="flex-[2] py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200">Zapsat</button>
        </div>
    `;
    v.appendChild(container);

    // Umísti kurzor do inputu pro gramy po zobrazení formuláře (odloženě, aby prohlížeč neblokoval autofocus)
    const gramsInput = document.getElementById('c-val');
    if (gramsInput) {
        requestAnimationFrame(() => {
            if (document.hasFocus() && document.activeElement !== gramsInput) {
                gramsInput.focus();
                gramsInput.select();
            }
        });
    }

    // Load and display consumption history
    // Remove existing history container if present (to prevent duplicates)
    const existingHistory = v.querySelectorAll('[data-consumption-history]');
    existingHistory.forEach(el => el.remove());

    try {
        const res = await fetch(`${API_BASE}/consumption/list.php?filament_id=${item.id}`);
        if (res.ok) {
            const history = await res.json();

            // Pokud už mezitím nejsme ve view 'consume' nebo se změnil consumeId,
            // historii nevykresluj (už nejsme na stránce vážení)
            if (state.view !== 'consume' || state.consumeId !== currentConsumeId) {
                return;
            }

            if (history.length > 0) {
                // Double-check: remove any remaining history containers
                const remainingHistory = v.querySelectorAll('[data-consumption-history]');
                remainingHistory.forEach(el => el.remove());
                
                const historyContainer = document.createElement('div');
                historyContainer.setAttribute('data-consumption-history', 'true');
                historyContainer.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200 max-w-lg mx-auto mt-6";
                historyContainer.innerHTML = `
                    <h3 class="text-lg font-black text-slate-800 mb-4">Historie čerpání</h3>
                    <div class="space-y-2">
                        ${history.map(h => `
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg text-sm">
                                <div class="flex-1">
                                    <div class="font-bold text-slate-800">${h.consumed_weight}g</div>
                                    <div class="text-xs text-slate-500">${h.consumption_date}${h.note ? ` • ${h.note}` : ''}</div>
                                </div>
                                <button onclick="editConsumption(${h.id})" class="text-indigo-600 font-bold text-xs hover:underline mr-2">Upravit</button>
                            </div>
                        `).join('')}
                    </div>
                `;
                v.appendChild(historyContainer);
            }
        }
    } catch (err) {
        console.error('Failed to load consumption history:', err);
    }
}

// Consumption edit/delete handlers
export async function editConsumption(consumptionId) {
    try {
        const res = await fetch(`${API_BASE}/consumption/get.php?id=${consumptionId}`);
        if (!res.ok) {
            const err = await res.json();
            showToast(err.error || 'Chyba načítání záznamu');
            return;
        }
        const consumption = await res.json();

        // Show edit form in a modal-like overlay
        const overlay = document.createElement('div');
        overlay.id = 'edit-consumption-modal';
        overlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
        overlay.onclick = (e) => { if (e.target === overlay) overlay.remove(); };

        // Store original amount_grams to preserve sign (positive = correction, negative = consumption)
        const originalAmountGrams = consumption.amount_grams !== undefined ? consumption.amount_grams : -consumption.consumed_weight;
        const isCorrection = originalAmountGrams > 0;
        
        overlay.innerHTML = `
            <div class="bg-white p-6 rounded-3xl shadow-xl max-w-md w-full" onclick="event.stopPropagation()">
                <h2 class="text-xl font-black text-slate-800 mb-4">Upravit čerpání</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Filament</label>
                        <div class="text-sm font-bold text-slate-600">${consumption.manufacturer} ${consumption.material} ${consumption.color}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">${isCorrection ? 'Korekce (g)' : 'Spotřebováno (g)'}</label>
                        <input id="edit-consumed-weight" type="number" value="${consumption.consumed_weight}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                        <input type="hidden" id="edit-original-amount-grams" value="${originalAmountGrams}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Datum</label>
                        <input id="edit-consumption-date" type="date" value="${consumption.consumption_date}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Poznámka</label>
                        <input id="edit-consumption-note" type="text" value="${consumption.note || ''}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button onclick="document.getElementById('edit-consumption-modal').remove()" class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold">Zrušit</button>
                    <button onclick="deleteConsumption(${consumptionId})" class="px-4 py-3 bg-red-500 text-white rounded-xl font-bold hover:bg-red-600">Smazat</button>
                    <button onclick="saveConsumptionEdit(${consumptionId})" class="flex-[2] py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200">Uložit</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
    } catch (err) {
        showToast('Chyba sítě');
    }
}

export async function saveConsumptionEdit(consumptionId) {
    const consumedWeight = parseInt(document.getElementById('edit-consumed-weight').value);
    const consumptionDate = document.getElementById('edit-consumption-date').value;
    const note = document.getElementById('edit-consumption-note').value;
    const originalAmountGrams = parseInt(document.getElementById('edit-original-amount-grams')?.value || '0');

    if (!consumedWeight || consumedWeight <= 0) {
        showToast('Zadejte platnou hmotnost');
        return;
    }

    // Preserve sign: if original was positive (correction), keep positive; if negative (consumption), keep negative
    const amountGrams = originalAmountGrams > 0 ? consumedWeight : -consumedWeight;

    try {
        const res = await fetch(`${API_BASE}/consumption/update.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: consumptionId,
                consumed_weight: consumedWeight,
                amount_grams: amountGrams,
                consumption_date: consumptionDate,
                note: note
            })
        });
        const data = await res.json();

        if (res.ok) {
            showToast('Záznam aktualizován');
            document.getElementById('edit-consumption-modal').remove();
            // loadData() already calls render(), so we don't need to call it again
            await loadData();
        } else {
            showToast(data.error || 'Chyba při ukládání');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

export async function deleteConsumption(consumptionId) {
    if (!confirm('Opravdu chcete smazat tento záznam čerpání? Hmotnost bude vrácena zpět k filamentu.')) {
        return;
    }

    try {
        const res = await fetch(`${API_BASE}/consumption/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: consumptionId })
        });
        const data = await res.json();

        if (res.ok) {
            showToast('Záznam smazán');
            document.getElementById('edit-consumption-modal')?.remove();
            await loadData();
            if (window.render) window.render();
        } else {
            showToast(data.error || 'Chyba při mazání');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}
