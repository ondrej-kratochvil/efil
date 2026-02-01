// Form helpers, field toggles, submission a logika vážení
import { state, spoolTemplates, setSpoolTemplates, options } from '../state.js';
import { API_BASE, BASE_PATH } from '../config.js';
import { showToast, getClosestColorName } from '../utils.js';
import { loadData, saveFilament } from '../api.js';
import { router } from '../router.js';

// Form field rendering functions
export function renderFieldInput(key, list, value) {
    // Check if list is an object with top/others structure or a plain array
    const hasGroups = list && typeof list === 'object' && !Array.isArray(list) && list.top && list.others;
    const listArray = hasGroups ? [...(list.top || []), ...(list.others || [])] : (Array.isArray(list) ? list : []);
    const isSelect = state.formFieldsStatus[key] === 'select';
    // Výrobci jsou objekty { id, name }; ostatní pole jsou řetězce
    const isManufacturerList = key === 'man' && listArray.length > 0 && typeof listArray[0] === 'object' && listArray[0] != null && 'id' in listArray[0] && 'name' in listArray[0];

    if (isSelect && listArray.length > 0) {
        let optionsHtml = `<option value="" disabled ${!value && value !== 0 ? 'selected' : ''}>Vybrat...</option>`;

        if (isManufacturerList) {
            if (hasGroups && list.top && list.top.length > 0) {
                optionsHtml += `<optgroup label="Nejčastější">`;
                optionsHtml += list.top.map(i => `<option value="${i.id}" ${(value == i.id || value === i.name) ? 'selected' : ''}>${escapeHtml(i.name)}</option>`).join('');
                optionsHtml += `</optgroup>`;
                if (list.others && list.others.length > 0) {
                    optionsHtml += `<optgroup label="Ostatní">`;
                    optionsHtml += list.others.map(i => `<option value="${i.id}" ${(value == i.id || value === i.name) ? 'selected' : ''}>${escapeHtml(i.name)}</option>`).join('');
                    optionsHtml += `</optgroup>`;
                }
            } else {
                optionsHtml += listArray.map(i => `<option value="${i.id}" ${(value == i.id || value === i.name) ? 'selected' : ''}>${escapeHtml(i.name)}</option>`).join('');
            }
        } else if (hasGroups && list.top && list.top.length > 0) {
            optionsHtml += `<optgroup label="Nejčastější">`;
            optionsHtml += list.top.map(i => `<option value="${i}" ${i === value ? 'selected' : ''}>${escapeHtml(String(i))}</option>`).join('');
            optionsHtml += `</optgroup>`;
            if (list.others && list.others.length > 0) {
                optionsHtml += `<optgroup label="Ostatní">`;
                optionsHtml += list.others.map(i => `<option value="${i}" ${i === value ? 'selected' : ''}>${escapeHtml(String(i))}</option>`).join('');
                optionsHtml += `</optgroup>`;
            }
        } else {
            optionsHtml += listArray.map(i => `<option value="${i}" ${i === value ? 'selected' : ''}>${escapeHtml(String(i))}</option>`).join('');
        }

        return `
            <select id="f-${key}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold appearance-none">
                ${optionsHtml}
            </select>
            <button type="button" onclick="toggleField('${key}')" class="bg-indigo-100 text-indigo-600 p-3 rounded-xl font-bold">+</button>
        `;
    }
    return `
        <input id="f-${key}" type="text" value="${escapeHtml(String(value || ''))}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="Zadejte novou hodnotu">
        ${listArray.length > 0 ? `<button type="button" onclick="toggleField('${key}')" class="bg-slate-200 text-slate-500 p-3 rounded-xl font-bold">zpět</button>` : ''}
    `;
}

function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

export function renderSpoolInput(selectedId) {
    const isSelect = state.formFieldsStatus.spool === 'select';

    if (isSelect) {
        const formatSpoolLabel = (s) => {
            const parts = [];
            if (s.color) parts.push(s.color);
            if (s.material) parts.push(s.material);
            if (s.outer_diameter_mm) parts.push(`Ø${s.outer_diameter_mm}mm`);
            if (s.width_mm) parts.push(`${s.width_mm}mm`);
            if (s.weight_grams) parts.push(`${s.weight_grams}g`);
            if (s.visual_description) parts.push(`(${s.visual_description})`);
            return parts.length > 0 ? parts.join(' • ') : 'Neznámá cívka';
        };

        // Get currently selected manufacturer (id or name) and resolve to name for grouping
        const manVal = document.getElementById('f-man')?.value || null;
        let currentManufacturerName = manVal;
        if (options && options.manufacturers) {
            const mans = Array.isArray(options.manufacturers) ? options.manufacturers : [...(options.manufacturers.top || []), ...(options.manufacturers.others || [])];
            const found = mans.find(m => m && (m.id == manVal || m.name === manVal));
            if (found) currentManufacturerName = found.name;
        }

        // Split spools into two groups: matching manufacturer and others
        const matchingSpools = [];
        const otherSpools = [];

        spoolTemplates.forEach(s => {
            const hasMatch = s.manufacturers && s.manufacturers.some(m => m.name === currentManufacturerName);
            if (hasMatch) {
                matchingSpools.push(s);
            } else {
                otherSpools.push(s);
            }
        });

        let optionsHtml = `
            <option value="" disabled ${!selectedId ? 'selected' : ''}>Vybrat...</option>
            <option value="" ${selectedId === null || selectedId === '' ? 'selected' : ''}>Žádná / Neznámá</option>
        `;

        // Add matching spools first in optgroup
        if (matchingSpools.length > 0 && currentManufacturer) {
            optionsHtml += `<optgroup label="Pro výrobce ${currentManufacturer}">`;
            optionsHtml += matchingSpools.map(s => `<option value="${s.id}" ${s.id == selectedId ? 'selected' : ''}>${formatSpoolLabel(s)}</option>`).join('');
            optionsHtml += `</optgroup>`;
        }

        // Add other spools
        if (otherSpools.length > 0) {
            if (matchingSpools.length > 0 && currentManufacturerName) {
                optionsHtml += `<optgroup label="Ostatní">`;
            }
            optionsHtml += otherSpools.map(s => `<option value="${s.id}" ${s.id == selectedId ? 'selected' : ''}>${formatSpoolLabel(s)}</option>`).join('');
            if (matchingSpools.length > 0 && currentManufacturerName) {
                optionsHtml += `</optgroup>`;
            }
        }

        return `
            <select id="f-spool" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold appearance-none">
                ${optionsHtml}
            </select>
            <button type="button" onclick="toggleSpoolField()" class="bg-indigo-100 text-indigo-600 p-3 rounded-xl font-bold">+</button>
        `;
    }

    // Save current values before switching
    const savedValues = state.spoolInputValues || {};

    return `
        <div class="w-full space-y-2">
            <div class="grid grid-cols-2 gap-2">
                <input id="f-spool-color" type="text" value="${savedValues.color || ''}" placeholder="Barva (černá, šedá...)" class="bg-slate-50 border-none rounded-xl p-3 font-bold">
                <input id="f-spool-material" type="text" value="${savedValues.material || ''}" placeholder="Materiál (PC, PS, ABS...)" class="bg-slate-50 border-none rounded-xl p-3 font-bold">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <input id="f-spool-diameter" type="number" value="${savedValues.diameter || ''}" placeholder="Vnější průměr (mm)" class="bg-slate-50 border-none rounded-xl p-3 font-bold">
                <input id="f-spool-width" type="number" value="${savedValues.width || ''}" placeholder="Šířka (mm)" class="bg-slate-50 border-none rounded-xl p-3 font-bold">
            </div>
            <input id="f-spool-weight" type="number" value="${savedValues.weight || ''}" placeholder="Hmotnost (g) - zadejte, až když bude cívka prázdná" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
            <input id="f-spool-desc" type="text" value="${savedValues.desc || ''}" placeholder="Popis (s otvory, s reliéfem...)" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
            ${spoolTemplates.length > 0 ? `<button type="button" onclick="toggleSpoolField()" class="bg-slate-200 text-slate-500 p-3 rounded-xl font-bold w-full">zpět</button>` : ''}
        </div>
    `;
}

// Form field toggle functions
export function toggleSpoolField() {
    // Save all form values before switching (including spool values)
    saveFormValues();

    // Save current spool input values before switching
    if (state.formFieldsStatus.spool === 'input') {
        state.spoolInputValues = {
            color: document.getElementById('f-spool-color')?.value || '',
            material: document.getElementById('f-spool-material')?.value || '',
            diameter: document.getElementById('f-spool-diameter')?.value || '',
            width: document.getElementById('f-spool-width')?.value || '',
            weight: document.getElementById('f-spool-weight')?.value || '',
            desc: document.getElementById('f-spool-desc')?.value || ''
        };
    }

    const switchingToInput = state.formFieldsStatus.spool === 'select';
    state.formFieldsStatus.spool = switchingToInput ? 'input' : 'select';
    // Při rozbalení pole typu cívky předvyplnit barvu a materiál z filamentu
    if (switchingToInput) {
        if (!state.spoolInputValues) state.spoolInputValues = {};
        state.spoolInputValues.color = state.spoolInputValues.color || document.getElementById('f-color')?.value || '';
        state.spoolInputValues.material = state.spoolInputValues.material || document.getElementById('f-mat')?.value || '';
    }
    if (window.render) window.render();

    // Restore all form values after render
    restoreFormValues();

    if (state.formFieldsStatus.spool === 'input') {
        setTimeout(() => {
            const input = document.getElementById('f-spool-color');
            if (input) {
                input.focus();
            }
        }, 50);
    }
}

export function toggleField(key) {
    // Save all form values before switching
    saveFormValues();

    const wasSelect = state.formFieldsStatus[key] === 'select';
    state.formFieldsStatus[key] = wasSelect ? 'input' : 'select';
    if (window.render) window.render();

    // Restore form values after render
    restoreFormValues();

    // Pokud jsme přepnuli do input módu, nastav focus na input pole
    if (wasSelect) {
        setTimeout(() => {
            const input = document.getElementById(`f-${key}`);
            if (input) {
                input.focus();
                input.select();
            }
        }, 50);
    }
}

// Save all form values to state
export function saveFormValues() {
    if (!state.formValues) state.formValues = {};

    // Save all form fields
    const fields = ['user_display_id', 'mat', 'man', 'loc', 'seller', 'color', 'hex', 'g', 'price', 'date', 'spool'];
    fields.forEach(field => {
        const el = document.getElementById(`f-${field}`);
        if (el) {
            state.formValues[field] = el.value;
        }
    });

    // Save weight mode
    const weightModeEl = document.getElementById('f-weight-mode');
    if (weightModeEl) {
        state.formValues.weightMode = weightModeEl.value;
    }

    // Save spool input values
    if (!state.formValues.spoolInput) state.formValues.spoolInput = {};
    const spoolFields = ['spool-color', 'spool-material', 'spool-diameter', 'spool-width', 'spool-weight', 'spool-desc'];
    spoolFields.forEach(field => {
        const el = document.getElementById(`f-${field}`);
        if (el) {
            state.formValues.spoolInput[field] = el.value;
        }
    });
}

// Restore all form values from state
export function restoreFormValues() {
    if (!state.formValues) return;

    // Restore all form fields
    Object.keys(state.formValues).forEach(field => {
        if (field === 'weightMode') {
            const el = document.getElementById('f-weight-mode');
            if (el) {
                el.value = state.formValues[field];
                state.weightMode = state.formValues[field];
                updateWeightInfo();
            }
        } else if (field === 'spoolInput') {
            // Restore spool input values
            if (state.formValues.spoolInput) {
                // Map spool input field names to actual input IDs
                const fieldMap = {
                    'spool-color': 'f-spool-color',
                    'spool-material': 'f-spool-material',
                    'spool-diameter': 'f-spool-diameter',
                    'spool-width': 'f-spool-width',
                    'spool-weight': 'f-spool-weight',
                    'spool-desc': 'f-spool-desc'
                };
                Object.keys(state.formValues.spoolInput).forEach(spoolField => {
                    const inputId = fieldMap[spoolField] || `f-${spoolField}`;
                    const el = document.getElementById(inputId);
                    if (el) {
                        el.value = state.formValues.spoolInput[spoolField];
                    }
                });
                // Also update state.spoolInputValues for renderSpoolInput
                if (!state.spoolInputValues) state.spoolInputValues = {};
                state.spoolInputValues.color = state.formValues.spoolInput['spool-color'] || '';
                state.spoolInputValues.material = state.formValues.spoolInput['spool-material'] || '';
                state.spoolInputValues.diameter = state.formValues.spoolInput['spool-diameter'] || '';
                state.spoolInputValues.width = state.formValues.spoolInput['spool-width'] || '';
                state.spoolInputValues.weight = state.formValues.spoolInput['spool-weight'] || '';
                state.spoolInputValues.desc = state.formValues.spoolInput['spool-desc'] || '';
            }
        } else {
            const el = document.getElementById(`f-${field}`);
            if (el) {
                el.value = state.formValues[field];
            }
        }
    });

    // Also restore from state.spoolInputValues if formValues doesn't have spoolInput
    if (state.spoolInputValues && (!state.formValues.spoolInput || Object.keys(state.formValues.spoolInput).length === 0)) {
        const spoolFieldMap = {
            color: 'f-spool-color',
            material: 'f-spool-material',
            diameter: 'f-spool-diameter',
            width: 'f-spool-width',
            weight: 'f-spool-weight',
            desc: 'f-spool-desc'
        };
        Object.keys(state.spoolInputValues).forEach(key => {
            const inputId = spoolFieldMap[key];
            if (inputId) {
                const el = document.getElementById(inputId);
                if (el) {
                    el.value = state.spoolInputValues[key] || '';
                }
            }
        });
    }

    // Restore color preview
    const hex = state.formValues.hex;
    const preview = document.getElementById('color-preview');
    if (hex && preview) {
        preview.style.backgroundColor = hex;
    }
}

export function selectColor(hex, name) {
    // Save current form values before changing color
    saveFormValues();

    const nameInput = document.getElementById('f-color');
    const hexInput = document.getElementById('f-hex');
    const preview = document.getElementById('color-preview');

    if (hexInput) hexInput.value = hex;
    if (nameInput) nameInput.value = name || getClosestColorName(hex);
    if (preview) preview.style.backgroundColor = hex;

    // Update saved values
    if (!state.formValues) state.formValues = {};
    state.formValues.hex = hex;
    state.formValues.color = name || getClosestColorName(hex);

    // Update all color buttons to show selected state
    document.querySelectorAll('[onclick^="window.selectColor"]').forEach(btn => {
        const btnHex = btn.getAttribute('onclick').match(/'([^']+)'/)?.[1];
        if (btnHex === hex) {
            btn.classList.remove('border-slate-200');
            btn.classList.add('border-indigo-600', 'ring-2', 'ring-indigo-200');
        } else {
            btn.classList.remove('border-indigo-600', 'ring-2', 'ring-indigo-200');
            btn.classList.add('border-slate-200');
        }
    });
}

export async function handleFormSubmit(e) {
    e.preventDefault();

    // Handle spool creation if in input mode
    let spoolId = null;
    let spoolWeight = null;

    if (state.formFieldsStatus.spool === 'input') {
        const spoolColor = document.getElementById('f-spool-color')?.value || '';
        const spoolMaterial = document.getElementById('f-spool-material')?.value || '';
        const spoolDiameter = document.getElementById('f-spool-diameter')?.value ? parseInt(document.getElementById('f-spool-diameter').value) : null;
        const spoolWidth = document.getElementById('f-spool-width')?.value ? parseInt(document.getElementById('f-spool-width').value) : null;
        const spoolWeightInput = document.getElementById('f-spool-weight')?.value ? parseInt(document.getElementById('f-spool-weight').value) : null;
        const spoolDesc = document.getElementById('f-spool-desc')?.value || '';

        // Create spool if at least one identifying field is provided (barva a materiál jsou povinné na API)
        if (spoolColor || spoolMaterial || spoolDiameter || spoolWidth || spoolDesc) {
            try {
                // Výrobce filamentu propsat do typu cívky
                let manufacturerForSpool = null;
                const manVal = document.getElementById('f-man')?.value;
                if (manVal) {
                    const mans = Array.isArray(options.manufacturers) ? options.manufacturers : [...(options.manufacturers?.top || []), ...(options.manufacturers?.others || [])];
                    const found = mans.find(m => m && (m.id == manVal || String(m.id) === String(manVal) || m.name === manVal));
                    manufacturerForSpool = found ? found.name : (typeof manVal === 'string' ? manVal : null);
                }
                const payload = {
                    color: spoolColor,
                    material: spoolMaterial,
                    outer_diameter_mm: spoolDiameter,
                    width_mm: spoolWidth,
                    weight_grams: spoolWeightInput,
                    visual_description: spoolDesc
                };
                if (manufacturerForSpool) payload.manufacturer = manufacturerForSpool;
                const res = await fetch(`${API_BASE}/spools/save.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const newSpool = await res.json();
                if (res.ok && newSpool.id) {
                    spoolId = newSpool.id;
                    spoolWeight = newSpool.weight_grams;
                    // Reload spools list
                    const resSpools = await fetch(`${API_BASE}/spools/list.php`);
                    if (resSpools.ok) {
                        const spools = await resSpools.json();
                        setSpoolTemplates(spools);
                    }
                }
            } catch (err) {
                console.error('Error creating spool:', err);
            }
        }
    } else {
        spoolId = document.getElementById('f-spool')?.value || null;
        if (spoolId) {
            const selectedSpool = spoolTemplates.find(s => s.id == spoolId);
            spoolWeight = selectedSpool?.weight_grams || null;
        }
    }

    // Handle weight mode (with/without spool)
    const weightMode = document.getElementById('f-weight-mode')?.value || 'netto';
    let weight = parseInt(document.getElementById('f-g').value);

    if (weightMode === 'gross' && spoolWeight) {
        // Calculate netto weight from gross
        weight = weight - spoolWeight;
    }

    const userDisplayId = document.getElementById('f-user_display_id')?.value;
    const manVal = document.getElementById('f-man')?.value ?? '';

    const item = {
        id: state.editingId,
        user_display_id: userDisplayId ? parseInt(userDisplayId) : null,
        mat: document.getElementById('f-mat').value,
        color: document.getElementById('f-color').value,
        hex: document.getElementById('f-hex').value,
        g: weight,
        loc: document.getElementById('f-loc').value,
        price: document.getElementById('f-price').value,
        seller: document.getElementById('f-seller') ? document.getElementById('f-seller').value : '',
        date: document.getElementById('f-date').value,
        spool_id: spoolId
    };
    if (manVal !== '') {
        if (/^\d+$/.test(String(manVal))) {
            item.man_id = parseInt(manVal, 10);
        } else {
            item.man = manVal;
        }
    }
    saveFilament(item);
}

export async function deleteFilament(id) {
    if (!confirm('Opravdu chcete smazat tento filament? Tato akce je nevratná.')) {
        return;
    }

    try {
        const res = await fetch(`${API_BASE}/filaments/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();

        if (res.ok) {
            // Clear editing state if deleted filament was being edited
            if (state.editingId === id) {
                state.editingId = null;
                state.formValues = null;
            }
            showToast('Filament smazán');
            await loadData();
            state.filters = { mat: null, color: null };
            router.push(BASE_PATH + '/wizard/mat');
        } else {
            showToast(data.error || 'Chyba při mazání');
        }
    } catch (e) {
        showToast('Chyba sítě');
    }
}

export function updateWeightInfo() {
    const mode = document.getElementById('f-weight-mode')?.value;
    const spoolSelect = document.getElementById('f-spool');
    const infoDiv = document.getElementById('f-weight-info');

    if (!infoDiv) return;

    if (mode === 'gross' && spoolSelect && spoolSelect.value) {
        const selectedSpool = spoolTemplates.find(s => s.id == spoolSelect.value);
        if (selectedSpool && selectedSpool.weight_grams) {
            infoDiv.textContent = `Tára cívky: ${selectedSpool.weight_grams}g - bude odečtena automaticky`;
        } else {
            infoDiv.textContent = 'Vyberte cívku pro automatický výpočet';
        }
    } else {
        infoDiv.textContent = '';
    }
}
