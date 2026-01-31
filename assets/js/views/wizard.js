// Wizard view render functions (MAT, BAR, VÝR steps)
import { state, filaments } from '../state.js';
import { router } from '../router.js';
import { BASE_PATH } from '../config.js';
import { formatKg, getContrast, getAvgCzkPerKg } from '../utils.js';
import { colorPalette } from '../colors.js';

export function renderMaterials(v) {
    const grid = document.createElement('div'); 
    grid.className = "card-grid";
    
    // Filter out filaments with zero or negative weight
    const activeFilaments = filaments.filter(i => parseInt(i.g) > 0);
    const data = state.filters.color ? activeFilaments.filter(i => i.color === state.filters.color) : activeFilaments;
    const stats = data.reduce((acc, i) => { 
        if (!acc[i.mat]) acc[i.mat] = { g: 0, count: 0, items: [] };
        acc[i.mat].g += (parseInt(i.g) || 0);
        acc[i.mat].count += 1;
        acc[i.mat].items.push(i);
        return acc; 
    }, {});

    if (Object.keys(stats).length === 0) {
        const emptyDiv = document.createElement('div');
        emptyDiv.className = "text-center py-10 space-y-4";
        emptyDiv.innerHTML = `
            <p class="text-slate-400">Žádná data.</p>
            <button onclick="openForm()" class="px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-colors">
                Přidat nový filament
            </button>
        `;
        v.appendChild(emptyDiv);
        return;
    }

    Object.keys(stats).sort((a,b)=>stats[b].g-stats[a].g).forEach(m => {
        const card = document.createElement('div');
        card.className = "aspect-square bg-white border border-slate-200 rounded-2xl p-3 flex items-center justify-center text-center relative shadow-sm cursor-pointer hover:border-indigo-300 transition-colors";
        card.onclick = () => {
            state.filters.mat = m;
            const nextStep = state.filters.color ? 3 : 2;
            state.currentStep = nextStep;
            router.push(BASE_PATH + (nextStep === 2 ? '/wizard/bar' : '/wizard/vyr'));
        };
        const count = stats[m].count;
        const avgCzkPerKg = getAvgCzkPerKg(stats[m].items);
        const avgPriceHtml = avgCzkPerKg != null ? `<div class="text-[10px] font-bold text-slate-400 absolute bottom-2 right-2">x̄ ${avgCzkPerKg} Kč/kg</div>` : '';
        card.innerHTML = `<div class="text-[10px] font-bold text-slate-400 absolute top-2 left-2">${count} ks</div><div class="text-[10px] font-bold text-slate-400 absolute top-2 right-2">${formatKg(stats[m].g)}</div>${avgPriceHtml}<div class="text-base font-black uppercase tracking-tight">${m}</div>`;
        grid.appendChild(card);
    });
    // "+" pro přidání nového filamentu (bez předvyplnění)
    const addCard = document.createElement('div');
    addCard.className = "aspect-square bg-indigo-50 border-2 border-dashed border-indigo-200 rounded-2xl p-3 flex items-center justify-center text-center shadow-sm cursor-pointer hover:border-indigo-400 hover:bg-indigo-100 transition-colors";
    addCard.onclick = () => { if (window.openForm) window.openForm(); };
    addCard.innerHTML = '<div class="text-3xl font-bold text-indigo-500">+</div>';
    grid.appendChild(addCard);
    v.appendChild(grid);
}

export function renderColors(v) {
    const grid = document.createElement('div'); 
    grid.className = "card-grid";
    
    // Filter out filaments with zero or negative weight
    const activeFilaments = filaments.filter(i => parseInt(i.g) > 0);
    const data = state.filters.mat ? activeFilaments.filter(i => i.mat === state.filters.mat) : activeFilaments;
    const stats = data.reduce((acc, i) => { 
        if(!acc[i.color]) acc[i.color]={g:0, hex:i.hex, count:0, items:[]}; 
        acc[i.color].g+=(parseInt(i.g)||0);
        acc[i.color].count += 1;
        acc[i.color].items.push(i);
        return acc; 
    }, {});

    Object.keys(stats).sort((a,b)=>stats[b].g-stats[a].g).forEach(c => {
        const info = stats[c], contrast = getContrast(info.hex), card = document.createElement('div');
        card.className = "aspect-square rounded-2xl p-3 flex items-center justify-center text-center shadow-sm relative cursor-pointer";
        card.style.backgroundColor = info.hex; 
        card.style.color = contrast;
        if(info.hex.toLowerCase()==='#ffffff') card.classList.add('border','border-slate-200');
        card.onclick = () => {
            state.filters.color = c;
            const nextStep = state.filters.mat ? 3 : 1;
            state.currentStep = nextStep;
            router.push(BASE_PATH + (nextStep === 1 ? '/wizard/mat' : '/wizard/vyr'));
        };
        const avgCzkPerKg = getAvgCzkPerKg(info.items);
        const avgPriceHtml = avgCzkPerKg != null ? `<div class="text-[10px] font-bold absolute bottom-2 right-2 opacity-70">x̄ ${avgCzkPerKg} Kč/kg</div>` : '';
        card.innerHTML = `<div class="text-[10px] font-bold absolute top-2 left-2 opacity-70">${info.count} ks</div><div class="text-[10px] font-bold absolute top-2 right-2 opacity-70">${formatKg(info.g)}</div>${avgPriceHtml}<div class="text-[13px] font-black uppercase px-1">${c}</div>`;
        grid.appendChild(card);
    });
    // "+" pro přidání nového filamentu (předvyplní se materiál, pokud je vyfiltrovaný)
    const addCard = document.createElement('div');
    addCard.className = "aspect-square bg-indigo-50 border-2 border-dashed border-indigo-200 rounded-2xl p-3 flex items-center justify-center text-center shadow-sm cursor-pointer hover:border-indigo-400 hover:bg-indigo-100 transition-colors";
    addCard.onclick = () => {
        const preset = state.filters.mat ? { mat: state.filters.mat } : null;
        if (window.openForm) window.openForm(preset);
    };
    addCard.innerHTML = '<div class="text-3xl font-bold text-indigo-500">+</div>';
    grid.appendChild(addCard);
    v.appendChild(grid);
}

export function renderDetails(v) {
    const container = document.createElement('div');
    container.className = "flex flex-col gap-3 w-full";

    // Filter out filaments with zero or negative weight
    const activeFilaments = filaments.filter(i => parseInt(i.g) > 0);
    const filtered = activeFilaments.filter(i => 
        (!state.filters.mat || i.mat===state.filters.mat) && 
        (!state.filters.color || i.color===state.filters.color)
    );

    if(filtered.length === 0) {
        container.innerHTML = `<div class="text-center py-20 text-slate-400 bg-white rounded-3xl border-2 border-dashed">Žádné položky</div>`;
    } else {
        // Group filaments by manufacturer + material + color
        const groups = new Map();
        filtered.forEach(item => {
            const key = `${item.man}|${item.mat}|${item.color}`;
            if (!groups.has(key)) {
                groups.set(key, []);
            }
            groups.get(key).push(item);
        });

        // Sort groups by total weight (descending)
        const sortedGroups = Array.from(groups.entries()).sort((a, b) => {
            const totalA = a[1].reduce((sum, i) => sum + parseInt(i.g), 0);
            const totalB = b[1].reduce((sum, i) => sum + parseInt(i.g), 0);
            return totalB - totalA;
        });

        sortedGroups.forEach(([key, items]) => {
            const isMultiple = items.length > 1;
            const isExpanded = state.expandedGroups.has(key);

            if (isMultiple && !isExpanded) {
                // Show grouped item – průměr jen z filamentů s vyplněnou cenou
                const totalWeight = items.reduce((sum, i) => sum + parseInt(i.g), 0);
                const avgCzkPerKg = getAvgCzkPerKg(items);
                const priceSuffix = avgCzkPerKg != null ? ` • x̄ ${avgCzkPerKg} Kč/kg` : '';
                const firstItem = items[0];

                const groupCard = document.createElement('div');
                groupCard.className = "bg-gradient-to-r from-indigo-50 to-purple-50 p-4 rounded-2xl border-2 border-indigo-200 flex items-center justify-between shadow-sm cursor-pointer hover:shadow-md transition-shadow";
                groupCard.onclick = () => {
                    state.expandedGroups.add(key);
                    if (window.render) window.render();
                };
                groupCard.innerHTML = `
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full border-2 border-indigo-300 shadow-inner" style="background-color: ${firstItem.hex}"></div>
                        <div>
                            <div class="font-bold text-slate-900 flex items-center gap-2">${firstItem.man}</div>
                            <div class="text-xs text-slate-500 font-medium uppercase mt-0.5">${firstItem.mat} • ${firstItem.color}${priceSuffix}</div>
                            <div class="text-[10px] text-indigo-600 font-bold mt-1 uppercase">${items.length} cívek</div>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-indigo-600 leading-none bg-white px-4 py-3 rounded-lg">${totalWeight}<span class="text-sm ml-1">g</span></div>
                `;
                container.appendChild(groupCard);
            } else {
                // Show individual items (or single item, or expanded group)
                items.sort((a,b)=>parseInt(b.g)-parseInt(a.g)).forEach((item, idx) => {
                    const avgCzkPerKg = getAvgCzkPerKg([item]);
                    const priceSuffix = avgCzkPerKg != null ? ` • ${avgCzkPerKg} Kč/kg` : ''; // bez x̄ – u jednotlivého filamentu jde o cenu za kg, ne průměr
                    const card = document.createElement('div');
                    const isInExpandedGroup = isMultiple && isExpanded;
                    const isHighlighted = state.lastUpdatedFilamentId === item.id;
                    card.className = `bg-white p-4 rounded-2xl border border-slate-200 flex items-center justify-between shadow-sm cursor-pointer ${isInExpandedGroup ? 'ml-6 border-l-4 border-l-indigo-400' : ''} ${isHighlighted ? 'highlight-filament' : ''}`;
                    card.onclick = () => {
                        state.editingId = item.id;
                        if (window.openForm) window.openForm();
                    };
                    card.innerHTML = `
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full border border-slate-100 shadow-inner" style="background-color: ${item.hex}"></div>
                            <div>
                                <div class="font-bold text-slate-900 flex items-center gap-2">${item.man}</div>
                                <div class="text-xs text-slate-500 font-medium uppercase mt-0.5">${item.mat} • ${item.color}${priceSuffix}</div>
                                <div class="text-[10px] text-indigo-500 font-bold mt-1 uppercase">${item.loc ? `${item.loc} | ` : ''}#${item.user_display_id || item.id}</div>
                            </div>
                        </div>
                        <div onclick="event.stopPropagation(); window.openConsume(${item.id})" class="text-2xl font-black text-indigo-600 leading-none bg-indigo-50 px-4 py-3 rounded-lg hover:bg-indigo-100 transition-colors cursor-pointer">${item.g}<span class="text-sm ml-1">g</span></div>
                    `;
                    container.appendChild(card);
                });

                // Add collapse button for expanded groups
                if (isMultiple && isExpanded) {
                    const collapseBtn = document.createElement('button');
                    collapseBtn.className = "ml-6 py-2 px-4 bg-indigo-100 text-indigo-600 rounded-lg font-bold text-sm hover:bg-indigo-200 transition-colors";
                    collapseBtn.textContent = "Sbalit skupinu";
                    collapseBtn.onclick = () => {
                        state.expandedGroups.delete(key);
                        if (window.render) window.render();
                    };
                    container.appendChild(collapseBtn);
                }
            }
        });

        // Zvýraznění jen jednou – po prvním vykreslení vyčisti ID
        state.lastUpdatedFilamentId = null;
    }

    // "+" pro přidání nového filamentu (předvyplní se materiál a barva podle filtrů)
    const addCard = document.createElement('div');
    addCard.className = "bg-indigo-50 border-2 border-dashed border-indigo-200 p-4 rounded-2xl flex items-center justify-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-100 transition-colors";
    addCard.onclick = () => {
        const preset = {};
        if (state.filters.mat) preset.mat = state.filters.mat;
        if (state.filters.color) {
            preset.color = state.filters.color;
            const fromFilament = filtered.find(i => i.color === state.filters.color);
            preset.hex = fromFilament?.hex || colorPalette.find(c => c.name === state.filters.color)?.hex || '#4f46e5';
        }
        if (window.openForm) window.openForm(Object.keys(preset).length ? preset : null);
    };
    addCard.innerHTML = '<div class="text-2xl font-bold text-indigo-500">+</div><span class="ml-2 font-bold text-indigo-600">Přidat filament</span>';
    container.appendChild(addCard);

    v.appendChild(container);
    const btn = document.createElement('button');
    btn.className = "mt-6 w-full py-4 text-indigo-600 font-bold text-sm bg-indigo-50 rounded-2xl";
    btn.innerText = "Vymazat filtry";
    btn.onclick = () => {
        if (window.resetApp) window.resetApp();
    };
    v.appendChild(btn);
}
