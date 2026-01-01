// Configuration
const API_BASE = 'api';

// State
let filaments = [];
let options = { materials: [], manufacturers: [], locations: [], sellers: [] };
let spoolTemplates = [];
let stats = null;
let user = null;
let state = {
    view: 'loading', // loading, auth, wizard, form, consume, stats
    authView: 'login', // login, register
    currentStep: 1,
    filters: { mat: null, color: null },
    editingId: null,
    consumeId: null,
    consumeMode: 'used', // used (subtract), weight (calculate from gross)
    formFieldsStatus: { mat: 'select', man: 'select', loc: 'select', seller: 'select' }
};

const colorNames = [
    { name: 'Černá', hex: '#000000' }, { name: 'Bílá', hex: '#ffffff' }, { name: 'Červená', hex: '#ff0000' },
    { name: 'Zelená', hex: '#00ff00' }, { name: 'Modrá', hex: '#0000ff' }, { name: 'Žlutá', hex: '#ffff00' },
    { name: 'Oranžová', hex: '#ffa500' }, { name: 'Šedá', hex: '#808080' }, { name: 'Stříbrná', hex: '#c0c0c0' },
    { name: 'Zlatá', hex: '#ffd700' }, { name: 'Fialová', hex: '#800080' }, { name: 'Hnědá', hex: '#a52a2a' }
];

// --- AUTH ---

async function checkAuth() {
    try {
        const res = await fetch(`${API_BASE}/auth/me.php`);
        const data = await res.json();
        if (data.authenticated) {
            user = data.user;
            document.getElementById('sync-status').classList.replace('bg-slate-200', 'bg-green-500');
            state.view = 'wizard'; // Default logged in view
            loadData();
        } else {
            state.view = 'auth';
        }
    } catch (err) {
        console.error('Auth check failed', err);
        state.view = 'auth'; // Fallback
    }
    render();
}

async function login(email, password) {
    try {
        const res = await fetch(`${API_BASE}/auth/login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();
        if (res.ok) {
            user = data.user;
            state.view = 'wizard';
            document.getElementById('sync-status').classList.replace('bg-slate-200', 'bg-green-500');
            loadData();
            render();
        } else {
            showToast(data.error || 'Chyba přihlášení');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

async function register(email, password) {
    try {
        const res = await fetch(`${API_BASE}/auth/register.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();
        if (res.ok) {
            login(email, password);
        } else {
            showToast(data.error || 'Chyba registrace');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

async function logout() {
    await fetch(`${API_BASE}/auth/logout.php`);
    user = null;
    state.view = 'auth';
    state.authView = 'login';
    document.getElementById('sync-status').classList.replace('bg-green-500', 'bg-slate-200');
    render();
}

// --- DATA ---
async function loadData() {
    try {
        const [resFilaments, resOptions, resSpools, resStats] = await Promise.all([
            fetch(`${API_BASE}/filaments/list.php`),
            fetch(`${API_BASE}/data/options.php`),
            fetch(`${API_BASE}/spools/list.php`),
            fetch(`${API_BASE}/dashboard/stats.php`)
        ]);
        
        if (resFilaments.ok) filaments = await resFilaments.json();
        if (resOptions.ok) options = await resOptions.json();
        if (resSpools.ok) spoolTemplates = await resSpools.json();
        if (resStats.ok) stats = await resStats.json();
        
        render();
    } catch (err) {
        console.error('Data load error', err);
        showToast('Chyba načítání dat');
    }
}

async function saveFilament(data) {
    try {
        const res = await fetch(`${API_BASE}/filaments/save.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        if (res.ok) {
            showToast('Uloženo');
            await loadData();
            state.view = 'wizard';
            state.filters = { mat: null, color: null };
            state.currentStep = 1;
            render();
        } else {
            const err = await res.json();
            showToast(err.error || 'Chyba ukládání');
        }
    } catch (e) {
        showToast('Chyba sítě');
    }
}

async function consumeFilament(filamentId, amount, description) {
    try {
        const res = await fetch(`${API_BASE}/filaments/consume.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ filament_id: filamentId, amount, description })
        });
        
        if (res.ok) {
            showToast('Zapsáno');
            await loadData();
            state.view = 'wizard'; 
            render();
        } else {
            const err = await res.json();
            showToast(err.error || 'Chyba zápisu');
        }
    } catch (e) {
        showToast('Chyba sítě');
    }
}

// --- UI HELPER ---
function showToast(msg) {
    const el = document.getElementById('toast');
    el.innerText = msg;
    el.style.opacity = '1';
    el.style.transform = 'translate(-50%, -20px)';
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translate(-50%, 0)';
    }, 3000);
}

function getClosestColorName(hex) {
    function hexToRgb(h) {
        let r = parseInt(h.slice(1, 3), 16), g = parseInt(h.slice(3, 5), 16), b = parseInt(h.slice(5, 7), 16);
        return [r, g, b];
    }
    const target = hexToRgb(hex);
    let minDist = Infinity;
    let closest = 'Vlastní barva';
    colorNames.forEach(c => {
        const curr = hexToRgb(c.hex);
        const dist = Math.sqrt(Math.pow(target[0]-curr[0],2) + Math.pow(target[1]-curr[1],2) + Math.pow(target[2]-curr[2],2));
        if (dist < minDist) { minDist = dist; closest = c.name; }
    });
    return minDist < 60 ? closest : 'Vlastní barva';
}

// --- RENDER ---
function render() {
    const appView = document.getElementById('app-view');
    const loadingScreen = document.getElementById('loading-screen');
    
    if (state.view === 'loading') {
        loadingScreen.classList.remove('hidden');
        appView.classList.add('hidden');
        return;
    } else {
        loadingScreen.classList.add('hidden');
        appView.classList.remove('hidden');
    }

    updateHeader();
    appView.innerHTML = '';

    if (state.view === 'auth') renderAuth(appView);
    else if (state.view === 'form') renderForm(appView);
    else if (state.view === 'consume') renderConsume(appView);
    else if (state.view === 'stats') renderStats(appView);
    else {
        if (state.currentStep === 1) renderMaterials(appView);
        else if (state.currentStep === 2) renderColors(appView);
        else if (state.currentStep === 3) renderDetails(appView);
    }
}

function updateHeader() {
    const nav = document.getElementById('wizard-nav');
    const fTitle = document.getElementById('form-title');
    const menuTrigger = document.getElementById('menu-trigger');
    
    if (state.view === 'auth') {
        nav.classList.add('hidden');
        fTitle.classList.add('hidden');
        menuTrigger.classList.add('hidden');
        return;
    }
    
    menuTrigger.classList.remove('hidden');

    if (state.view === 'form' || state.view === 'consume' || state.view === 'stats') { 
        nav.classList.add('hidden'); 
        fTitle.classList.remove('hidden'); 
        if (state.view === 'form') fTitle.innerText = 'Editor';
        else if (state.view === 'consume') fTitle.innerText = 'Vážení';
        else fTitle.innerText = 'Přehled skladu';
    } else {
        nav.classList.remove('hidden'); 
        fTitle.classList.add('hidden');
        ['nav-mat', 'nav-bar', 'nav-vyr'].forEach((id, idx) => {
            const el = document.getElementById(id);
            const active = state.currentStep === (idx + 1);
            el.className = `flex flex-col items-center justify-center px-3 h-full text-xs font-medium border-b-2 transition-all uppercase tracking-wider ${active ? 'border-indigo-600 text-indigo-600 font-bold' : 'border-transparent text-slate-400'}`;
        });
        const spanMat = document.getElementById('nav-mat').querySelector('span');
        const spanBar = document.getElementById('nav-bar').querySelector('span');
        if (spanMat) spanMat.innerText = state.filters.mat || 'MAT';
        if (spanBar) spanBar.innerText = state.filters.color || 'BAR';
    }
}

function renderAuth(v) {
    const isLogin = state.authView === 'login';
    const container = document.createElement('div');
    container.className = 'auth-container bg-white rounded-3xl shadow-sm border border-slate-200 mt-10';
    container.innerHTML = `
        <h2 class="text-2xl font-black text-center mb-6 text-slate-800">${isLogin ? 'Přihlášení' : 'Registrace'}</h2>
        <form onsubmit="handleAuthSubmit(event)" class="flex flex-col gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Email</label>
                <input type="email" name="email" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="name@example.com">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Heslo</label>
                <input type="password" name="password" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="********">
            </div>
            <button type="submit" class="mt-2 w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 active:scale-95 transition-transform">
                ${isLogin ? 'Přihlásit se' : 'Vytvořit účet'}
            </button>
        </form>
        ${isLogin ? `
        <button onclick="login('demo@efil.cz', 'demo1234')" class="mt-3 w-full py-3 bg-slate-100 text-slate-600 rounded-xl font-bold border border-slate-200">
            Vyzkoušet Demo
        </button>
        ` : ''}
        <div class="mt-6 text-center text-sm">
            ${isLogin ? 'Nemáte účet?' : 'Již máte účet?'} 
            <span onclick="toggleAuthView()" class="text-indigo-600 font-bold cursor-pointer hover:underline">
                ${isLogin ? 'Registrovat' : 'Přihlásit'}
            </span>
        </div>
    `;
    v.appendChild(container);
}

// --- STATS ---
function renderStats(v) {
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

        <button onclick="window.resetApp()" class="w-full py-4 bg-indigo-50 text-indigo-600 rounded-2xl font-bold shadow-sm mt-8">Zpět na sklad</button>
    `;
    v.appendChild(container);
}

window.openStats = () => {
    state.view = 'stats';
    document.getElementById('action-menu').classList.add('hidden');
    render();
}

// --- CONSUME LOGIC ---

window.setConsumeMode = (mode) => {
    state.consumeMode = mode;
    render();
}

window.handleConsumeSubmit = (e) => {
    e.preventDefault();
    const item = filaments.find(i => i.id === state.consumeId);
    if(!item) return;

    let grams = 0;
    const desc = document.getElementById('c-desc').value || 'Tisk';
    
    if(state.consumeMode === 'used') {
        grams = -1 * parseInt(document.getElementById('c-val').value);
    } else {
        // Weight mode: NewNetto = MeasuredGross - SpoolWeight
        // Diff = NewNetto - OldNetto
        const measuredGross = parseInt(document.getElementById('c-val').value);
        const spoolWeight = item.spool_weight || 0;
        const currentNetto = item.g;
        
        const newNetto = measuredGross - spoolWeight;
        grams = newNetto - currentNetto;
    }

    consumeFilament(item.id, grams, desc);
}

function renderConsume(v) {
    const item = filaments.find(i => i.id === state.consumeId);
    if (!item) { state.view = 'wizard'; render(); return; }

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
            <button onclick="setConsumeMode('used')" class="flex-1 py-2 rounded-lg font-bold text-sm transition-all ${isUsed ? 'bg-white shadow text-indigo-600' : 'text-slate-500'}">Spotřebováno</button>
            <button onclick="setConsumeMode('weight')" class="flex-1 py-2 rounded-lg font-bold text-sm transition-all ${!isUsed ? 'bg-white shadow text-indigo-600' : 'text-slate-500'}">Zváženo (Brutto)</button>
        </div>

        <div class="space-y-4">
            ${!isUsed && !hasSpool ? `<div class="bg-amber-50 text-amber-600 p-3 rounded-xl text-xs font-bold border border-amber-100">⚠ Pozor: U této cívky není nastavena Tára. Výpočet bude nepřesný (bude se počítat Tára 0g).</div>` : ''}
            
            <div class="text-center">
                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-2">
                    ${isUsed ? 'Kolik gramů jste spotřebovali?' : 'Kolik váží cívka na váze?'}
                </label>
                <div class="flex items-center justify-center gap-2">
                    <input id="c-val" type="number" autofocus class="w-32 text-center text-3xl font-black bg-slate-50 border-none rounded-2xl p-4 focus:ring-2 ring-indigo-500 outline-none" placeholder="0">
                    <span class="text-xl font-bold text-slate-300">g</span>
                </div>
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
}

// --- FORM LOGIC ---

window.renderFieldInput = (key, list, value) => {
    const isSelect = state.formFieldsStatus[key] === 'select';
    if (isSelect && list.length > 0) {
        return `
            <select id="f-${key}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold appearance-none">
                <option value="" disabled ${!value ? 'selected' : ''}>Vybrat...</option>
                ${list.map(i => `<option value="${i}" ${i === value ? 'selected' : ''}>${i}</option>`).join('')}
            </select>
            <button onclick="toggleField('${key}')" class="bg-indigo-100 text-indigo-600 p-3 rounded-xl font-bold">+</button>
        `;
    }
    return `
        <input id="f-${key}" type="text" value="${value || ''}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="Zadejte novou hodnotu">
        ${list.length > 0 ? `<button onclick="toggleField('${key}')" class="bg-slate-200 text-slate-500 p-3 rounded-xl font-bold">zpět</button>` : ''}
    `;
};

window.toggleField = (key) => {
    state.formFieldsStatus[key] = state.formFieldsStatus[key] === 'select' ? 'input' : 'select';
    render();
};

window.handleColorChange = (hex) => {
    const nameInput = document.getElementById('f-color');
    const hexInput = document.getElementById('f-hex');
    hexInput.value = hex;
    if (nameInput) nameInput.value = getClosestColorName(hex);
};

window.handleFormSubmit = (e) => {
    e.preventDefault();
    const item = {
        id: state.editingId,
        mat: document.getElementById('f-mat').value,
        color: document.getElementById('f-color').value,
        hex: document.getElementById('f-hex').value,
        man: document.getElementById('f-man').value,
        g: parseInt(document.getElementById('f-g').value),
        loc: document.getElementById('f-loc').value,
        price: document.getElementById('f-price').value,
        seller: document.getElementById('f-seller') ? document.getElementById('f-seller').value : '',
        date: document.getElementById('f-date').value,
        spool_id: document.getElementById('f-spool').value
    };
    saveFilament(item);
};

function renderForm(v) {
    const item = state.editingId ? filaments.find(i => i.id === state.editingId) : { mat: '', color: '', hex: '#4f46e5', man: '', g: 1000, loc: '', price: '', date: '', seller: '' };
    
    // Ensure we have lists even if empty
    const mats = options.materials || [];
    const mans = options.manufacturers || [];
    const locs = options.locations || [];
    const sellers = options.sellers || [];

    // If lists are empty, force input mode
    if(mats.length===0) state.formFieldsStatus.mat = 'input';
    if(mans.length===0) state.formFieldsStatus.man = 'input';
    if(locs.length===0) state.formFieldsStatus.loc = 'input';
    if(sellers.length===0) state.formFieldsStatus.seller = 'input';

    const form = document.createElement('div');
    form.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200 max-w-lg mx-auto space-y-5";
    form.innerHTML = `
        <div class="field-container">
            <label class="block text-[10px] font-bold text-slate-400 uppercase">Barva (Paleta a Název)</label>
            <div class="flex gap-2">
                <input id="f-hex" type="color" value="${item.hex}" oninput="window.handleColorChange(this.value)" class="w-16 h-12 bg-transparent border-none p-0 cursor-pointer">
                <input id="f-color" type="text" value="${item.color}" placeholder="Název" class="flex-1 bg-slate-50 border-none rounded-xl p-3 font-bold">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="field-container"><label class="text-[10px] font-bold text-slate-400 uppercase">Materiál</label><div class="input-group">${renderFieldInput('mat', mats, item.mat)}</div></div>
            <div class="field-container"><label class="text-[10px] font-bold text-slate-400 uppercase">Výrobce</label><div class="input-group">${renderFieldInput('man', mans, item.man)}</div></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="field-container"><label class="text-[10px] font-bold text-slate-400 uppercase">Počáteční hmotnost (g)</label><input id="f-g" type="number" value="${item.initial_weight_grams || item.g}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold"></div>
            <div class="field-container"><label class="text-[10px] font-bold text-slate-400 uppercase">Umístění</label><div class="input-group">${renderFieldInput('loc', locs, item.loc)}</div></div>
        </div>
        
        <div class="field-container">
             <label class="text-[10px] font-bold text-slate-400 uppercase">Typ Cívky (Tára)</label>
             <select id="f-spool" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold appearance-none">
                <option value="" ${!item.spool_id ? 'selected' : ''}>Žádná / Neznámá</option>
                ${spoolTemplates.map(s => `<option value="${s.id}" ${s.id == item.spool_id ? 'selected' : ''}>${s.manufacturer} - ${s.weight_grams}g (${s.visual_description || 'Standard'})</option>`).join('')}
            </select>
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
            <button onclick="window.handleFormSubmit(event)" class="flex-[2] py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200">Uložit</button>
        </div>
    `;
    v.appendChild(form);
}

window.handleAuthSubmit = (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    if (state.authView === 'login') {
        login(fd.get('email'), fd.get('password'));
    } else {
        register(fd.get('email'), fd.get('password'));
    }
};

window.toggleAuthView = () => {
    state.authView = state.authView === 'login' ? 'register' : 'login';
    render();
};

window.resetApp = () => {
    state.filters = { mat: null, color: null };
    state.currentStep = 1;
    state.view = 'wizard';
    render();
};

const formatKg = (g) => (g / 1000).toFixed(1).replace('.', ',') + ' kg';
const getContrast = (hex) => {
    const r = parseInt(hex.substring(1,3),16), g = parseInt(hex.substring(3,5),16), b = parseInt(hex.substring(5,7),16);
    return (((r*299)+(g*587)+(b*114))/1000) >= 128 ? '#000000' : '#ffffff';
};

function renderMaterials(v) {
    const grid = document.createElement('div'); grid.className = "card-grid";
    const data = state.filters.color ? filaments.filter(i => i.color === state.filters.color) : filaments;
    const stats = data.reduce((acc, i) => { acc[i.mat] = (acc[i.mat] || 0) + (parseInt(i.g) || 0); return acc; }, {});
    
    if (Object.keys(stats).length === 0) {
        v.innerHTML = `<div class="text-center py-10 text-slate-400">Žádná data.<br>Přidejte filament v menu.</div>`;
        return;
    }

    Object.keys(stats).sort((a,b)=>stats[b]-stats[a]).forEach(m => {
        const card = document.createElement('div');
        card.className = "aspect-square bg-white border border-slate-200 rounded-2xl p-3 flex items-center justify-center text-center relative shadow-sm cursor-pointer hover:border-indigo-300 transition-colors";
        card.onclick = () => { state.filters.mat = m; state.currentStep = state.filters.color ? 3 : 2; render(); };
        card.innerHTML = `<div class="text-[10px] font-bold text-slate-400 absolute top-2 right-2">${formatKg(stats[m])}</div><div class="text-base font-black uppercase tracking-tight">${m}</div>`;
        grid.appendChild(card);
    });
    v.appendChild(grid);
}

function renderColors(v) {
    const grid = document.createElement('div'); grid.className = "card-grid";
    const data = state.filters.mat ? filaments.filter(i => i.mat === state.filters.mat) : filaments;
    const stats = data.reduce((acc, i) => { if(!acc[i.color]) acc[i.color]={g:0, hex:i.hex}; acc[i.color].g+=(parseInt(i.g)||0); return acc; }, {});
    
    Object.keys(stats).sort((a,b)=>stats[b].g-stats[a].g).forEach(c => {
        const info = stats[c], contrast = getContrast(info.hex), card = document.createElement('div');
        card.className = "aspect-square rounded-2xl p-3 flex items-center justify-center text-center shadow-sm relative cursor-pointer";
        card.style.backgroundColor = info.hex; card.style.color = contrast;
        if(info.hex.toLowerCase()==='#ffffff') card.classList.add('border','border-slate-200');
        card.onclick = () => { state.filters.color = c; state.currentStep = state.filters.mat ? 3 : 1; render(); };
        card.innerHTML = `<div class="text-[10px] font-bold absolute top-2 right-2 opacity-70">${formatKg(info.g)}</div><div class="text-[13px] font-black uppercase px-1">${c}</div>`;
        grid.appendChild(card);
    });
    v.appendChild(grid);
}

function renderDetails(v) {
    const container = document.createElement('div'); container.className = "flex flex-col gap-3 w-full";
    const filtered = filaments.filter(i => (!state.filters.mat || i.mat===state.filters.mat) && (!state.filters.color || i.color===state.filters.color)).sort((a,b)=>b.g-a.g);
    if(filtered.length === 0) container.innerHTML = `<div class="text-center py-20 text-slate-400 bg-white rounded-3xl border-2 border-dashed">Žádné položky</div>`;
    else filtered.forEach(item => {
        const card = document.createElement('div');
        card.className = "bg-white p-4 rounded-2xl border border-slate-200 flex items-center justify-between shadow-sm cursor-pointer";
        card.onclick = () => {
             state.editingId = item.id;
             openForm();
        };
        card.innerHTML = `
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full border border-slate-100 shadow-inner" style="background-color: ${item.hex}"></div>
                <div>
                    <div class="font-bold text-slate-900 flex items-center gap-2">${item.man}</div>
                    <div class="text-xs text-slate-500 font-medium uppercase mt-0.5">${item.mat} • ${item.color}</div>
                    <div class="text-[10px] text-indigo-500 font-bold mt-1 uppercase">#${item.id}</div>
                </div>
            </div>
            <div class="text-right">
                <div onclick="event.stopPropagation(); window.openConsume(${item.id})" class="text-xl font-black text-indigo-600 leading-none bg-indigo-50 px-2 py-1 rounded-lg hover:bg-indigo-100 transition-colors">${item.g}<span class="text-xs ml-0.5">g</span></div>
                <div class="text-[9px] text-slate-400 font-bold mt-1 uppercase">Zůstatek</div>
            </div>
        `;
        container.appendChild(card);
    });
    v.appendChild(container);
    const btn = document.createElement('button'); btn.className = "mt-6 w-full py-4 text-indigo-600 font-bold text-sm bg-indigo-50 rounded-2xl";
    btn.innerText = "Vymazat filtry"; btn.onclick = window.resetApp; v.appendChild(btn);
}

window.setStep = (s) => { state.currentStep = s; render(); };
window.toggleActionMenu = () => {
    const menu = document.getElementById('action-menu');
    menu.classList.toggle('hidden');
};
window.openForm = () => {
    state.view = 'form';
    // If opening fresh (not edit), reset editingId
    if (state.view === 'form' && !state.editingId) {
        state.editingId = null; 
    }
    // We update this via onclick in renderDetails so editingId is set before this call if editing
    
    document.getElementById('action-menu').classList.add('hidden');
    render();
};

window.openConsume = (id) => {
    state.consumeId = id;
    state.view = 'consume';
    state.consumeMode = 'used';
    render();
}

window.openStats = () => {
    state.view = 'stats';
    document.getElementById('action-menu').classList.add('hidden');
    render();
}

checkAuth();
