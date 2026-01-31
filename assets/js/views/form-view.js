// Form view: otevření, načtení dat a hlavní render formuláře
import { state, filaments, options } from '../state.js';
import { BASE_PATH } from '../config.js';
import { colorPalette } from '../colors.js';
import { loadData } from '../api.js';
import { router } from '../router.js';
import { renderFieldInput, renderSpoolInput, updateWeightInfo, restoreFormValues } from './form.js';

// Asynchronní vstupní bod pro zobrazení formuláře (nový i editace)
export async function renderFormAsync(v) {
    // Clear formValues when opening form (both for new and edit)
    state.formValues = null;
    
    // If editing and item not found in filaments array, reload data first
    if (state.editingId) {
        const foundItem = filaments.find(i => i.id === state.editingId);
        
        if (!foundItem) {
            try {
                await loadData();
                // After reload, check if item still exists (might have been deleted)
                const stillExists = filaments.find(i => i.id === state.editingId);
                if (!stillExists) {
                    // Filament was deleted, clear editing state
                    state.editingId = null;
                }
            } catch (err) {
                console.error('Failed to load data for form:', err);
                // On error, clear editing state to prevent issues
                state.editingId = null;
            }
        }
    }
    renderForm(v);
}

// Hlavní render funkce formuláře (nový i existující filament)
export function renderForm(v) {
    // Use saved values if available, otherwise use item values
    const baseItem = state.editingId
        ? (filaments.find(i => i.id === state.editingId) || {
            mat: '',
            color: '',
            hex: '#4f46e5',
            man: '',
            g: 1000,
            loc: '',
            price: '',
            date: '',
            seller: ''
        })
        : (() => {
            const empty = {
                mat: '',
                color: '',
                hex: '#4f46e5',
                man: '',
                g: 1000,
                loc: '',
                price: '',
                date: '',
                seller: ''
            };
            if (state.formPreset) {
                const p = state.formPreset;
                if (p.mat !== undefined && p.mat !== '') empty.mat = p.mat;
                if (p.color !== undefined && p.color !== '') empty.color = p.color;
                if (p.hex !== undefined && p.hex !== '') empty.hex = p.hex;
                state.formPreset = null;
            }
            return empty;
        })();

    // Calculate next available user_display_id for new filament
    let suggestedDisplayId = null;
    if (!state.editingId && filaments.length > 0) {
        const maxId = Math.max(...filaments.map(f => parseInt(f.user_display_id) || 0));
        suggestedDisplayId = maxId + 1;
    } else if (!state.editingId) {
        suggestedDisplayId = 1;
    }

    // Only use formValues if they are not empty strings (to avoid overwriting baseItem with empty values)
    // For editing: always use initial_weight_grams (original weight), not current weight (g)
    const originalWeight = baseItem.initial_weight_grams !== undefined && baseItem.initial_weight_grams !== null
        ? baseItem.initial_weight_grams
        : (state.editingId ? baseItem.g : baseItem.g); // Fallback to g only if initial_weight_grams is not available

    const item = state.formValues ? {
        ...baseItem,
        user_display_id: state.formValues.user_display_id !== undefined && state.formValues.user_display_id !== '' ? state.formValues.user_display_id : (baseItem.user_display_id || suggestedDisplayId),
        mat: state.formValues.mat !== undefined && state.formValues.mat !== '' ? state.formValues.mat : baseItem.mat,
        man: state.formValues.man !== undefined && state.formValues.man !== '' ? state.formValues.man : baseItem.man,
        man_id: state.formValues.man_id != null ? state.formValues.man_id : (baseItem.man_id ?? (/^\d+$/.test(String(state.formValues.man || '')) ? parseInt(state.formValues.man, 10) : undefined)),
        loc: state.formValues.loc !== undefined && state.formValues.loc !== '' ? state.formValues.loc : baseItem.loc,
        seller: state.formValues.seller !== undefined && state.formValues.seller !== '' ? state.formValues.seller : baseItem.seller,
        color: state.formValues.color !== undefined && state.formValues.color !== '' ? state.formValues.color : baseItem.color,
        hex: state.formValues.hex !== undefined && state.formValues.hex !== '' ? state.formValues.hex : baseItem.hex,
        g: state.formValues.g !== undefined && state.formValues.g !== '' ? parseInt(state.formValues.g) : originalWeight,
        price: state.formValues.price !== undefined && state.formValues.price !== '' ? state.formValues.price : baseItem.price,
        date: state.formValues.date !== undefined && state.formValues.date !== '' ? state.formValues.date : baseItem.date,
        spool_id: state.formValues.spool !== undefined && state.formValues.spool !== '' ? state.formValues.spool : baseItem.spool_id
    } : { ...baseItem, user_display_id: baseItem.user_display_id || suggestedDisplayId, g: originalWeight, man_id: baseItem.man_id };

    // Update weight mode from saved values
    if (state.formValues && state.formValues.weightMode) {
        state.weightMode = state.formValues.weightMode;
    }

    // Ensure we have lists even if empty
    const mats = options.materials || [];
    const mans = options.manufacturers || [];
    const locs = options.locations || [];
    const sellers = options.sellers || [];

    // Initialize spool status if not set
    if (!state.formFieldsStatus.spool) state.formFieldsStatus.spool = 'select';
    if (!state.weightMode) state.weightMode = 'netto';

    const form = document.createElement('div');
    form.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200 max-w-lg mx-auto space-y-5";
    form.innerHTML = `
        <div class="field-container">
            <label class="text-[10px] font-bold text-slate-400 uppercase">Materiál <span class="text-red-500">*</span></label>
            <div class="input-group">${renderFieldInput('mat', mats, item.mat)}</div>
        </div>
        <div class="field-container">
            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2">Barva (Paleta a Název) <span class="text-red-500">*</span></label>
            <div class="grid grid-cols-8 gap-2 mb-2">
                ${colorPalette.map(c => {
                    const isSelected = item.hex && item.hex.toLowerCase() === c.hex.toLowerCase();
                    // Speciální styly pro průhlednou barvu
                    const bgStyle = c.name === 'Průhledná / Čirá'
                        ? 'background: linear-gradient(45deg, #E8E8E8 25%, transparent 25%), linear-gradient(-45deg, #E8E8E8 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #E8E8E8 75%), linear-gradient(-45deg, transparent 75%, #E8E8E8 75%); background-size: 8px 8px; background-position: 0 0, 0 4px, 4px -4px, -4px 0px;'
                        : `background-color: ${c.hex}`;
                    return `
                    <button type="button" onclick="window.selectColor('${c.hex}', '${c.name}')"
                        class="w-10 h-10 rounded-lg border-2 ${isSelected ? 'border-indigo-600 ring-2 ring-indigo-200' : 'border-slate-200'} cursor-pointer hover:scale-110 transition-transform"
                        style="${bgStyle}"
                        title="${c.name}">
                    </button>
                `;
                }).join('')}
            </div>
            <div class="flex gap-2">
                <input id="f-hex" type="hidden" value="${item.hex}">
                <div class="w-16 h-12 rounded-xl border-2 border-slate-200" style="background-color: ${item.hex}" id="color-preview"></div>
                <input id="f-color" type="text" value="${item.color}" placeholder="Název barvy" class="flex-1 bg-slate-50 border-none rounded-xl p-3 font-bold">
            </div>
        </div>
        <div class="field-container">
            <label class="text-[10px] font-bold text-slate-400 uppercase">Výrobce</label>
            <div class="input-group">${renderFieldInput('man', mans, item.man_id != null && item.man_id !== '' ? item.man_id : item.man)}</div>
        </div>
        <div class="field-container">
            <label class="text-[10px] font-bold text-slate-400 uppercase">Počáteční hmotnost (g) <span class="text-red-500">*</span></label>
            <div class="flex gap-2">
                <select id="f-weight-mode" onchange="updateWeightInfo()" class="bg-slate-50 border-none rounded-xl p-3 font-bold text-sm">
                    <option value="netto" ${!state.weightMode || state.weightMode === 'netto' ? 'selected' : ''}>Bez cívky</option>
                    <option value="gross" ${state.weightMode === 'gross' ? 'selected' : ''}>S cívkou</option>
                </select>
                <input id="f-g" type="number" value="${item.g}" class="flex-1 bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="Hmotnost">
            </div>
            <div id="f-weight-info" class="text-[9px] text-slate-400 mt-1"></div>
        </div>
        <div class="field-container">
             <label class="text-[10px] font-bold text-slate-400 uppercase">Typ Cívky (Tára)</label>
             <div class="input-group">
                 ${renderSpoolInput(item.spool_id)}
             </div>
        </div>

        <div class="field-container">
            <label class="text-[10px] font-bold text-slate-400 uppercase">Umístění</label>
            <div class="input-group">${renderFieldInput('loc', locs, item.loc)}</div>
        </div>
        <div class="field-container">
            <label class="text-[10px] font-bold text-slate-400 uppercase">Číslo filamentu</label>
            <input id="f-user_display_id" type="number" value="${item.user_display_id || ''}" min="1" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="Automaticky navržené">
            <div class="text-[9px] text-slate-400 mt-1">Číslo pro identifikaci filamentu v evidenci. Musí být jedinečné.</div>
        </div>

        <div class="border-t border-slate-100 pt-4 space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase">Obchodní údaje</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="field-container">
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Cena (Kč)</label>
                    <input id="f-price" type="number" value="${item.price || ''}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                </div>
                <div class="field-container">
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Datum pořízení</label>
                    <input id="f-date" type="date" value="${item.date || ''}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                </div>
            </div>
             <div class="field-container"><label class="text-[10px] font-bold text-slate-400 uppercase">Prodejce</label><div class="input-group">${renderFieldInput('seller', sellers, item.seller)}</div></div>
        </div>

        <div class="flex gap-3 pt-4">
            <button onclick="window.resetApp()" class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold">Zrušit</button>
            ${state.editingId ? `
            <button onclick="window.deleteFilament(${state.editingId})" type="button" class="flex-1 py-3 bg-red-500 text-white rounded-xl font-bold hover:bg-red-600 transition-colors">Smazat</button>
            ` : ''}
            <button onclick="window.handleFormSubmit(event)" class="flex-[2] py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200">Uložit</button>
        </div>
    `;
    v.appendChild(form);
}

// Otevření formuláře (nový nebo editace) a nastavení routingu
// preset: { mat?, color?, hex? } – předvyplnění při přidání z wizardu (MAT/BAR/VÝR "+")
export function openForm(preset = null) {
    // If opening fresh (not edit), reset editingId and form status
    if (!state.editingId) {
        state.editingId = null;
        // Reset form fields to select mode
        state.formFieldsStatus = { mat: 'select', man: 'select', loc: 'select', seller: 'select', spool: 'select' };
        state.weightMode = 'netto';
        state.formPreset = preset && (preset.mat || preset.color || preset.hex) ? preset : null;
    }
    // Always clear formValues when opening form (will be cleared again in renderFormAsync, but this ensures it's cleared early)
    state.formValues = null;
    // We update this via onclick in renderDetails so editingId is set before this call if editing

    document.getElementById('action-menu').classList.add('hidden');

    const path = state.editingId ? `/form/${state.editingId}` : '/form';
    router.push(BASE_PATH + path);

    // Update weight info after render
    setTimeout(() => {
        updateWeightInfo();
        const spoolSelect = document.getElementById('f-spool');
        if (spoolSelect) {
            spoolSelect.addEventListener('change', updateWeightInfo);
        }
        // Restore form values if they exist (for when switching between modes)
        if (state.formValues) {
            restoreFormValues();
        }
    }, 100);
}

