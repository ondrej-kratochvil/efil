// Configuration
const API_BASE = 'api';

// State
let filaments = [];
let user = null;
let state = {
    view: 'loading', // loading, auth, wizard, form
    authView: 'login', // login, register
    currentStep: 1,
    filters: { mat: null, color: null },
    editingId: null,
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
            // Auto login after register or ask to login
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
// Placeholder for Phase 2
async function loadData() {
    // In Phase 1 we just simulate or fetch empty list if endpoint doesn't exist yet
    // For now we can keep filaments empty or mock it if needed for UI testing
    filaments = []; 
    // TODO: Phase 2 - implement fetch from api/filaments/list.php
    render();
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

// --- RENDER ---
function render() {
    const appView = document.getElementById('app-view');
    const loadingScreen = document.getElementById('loading-screen');
    
    // Global visibility handling
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

    if (state.view === 'auth') {
        renderAuth(appView);
    } else if (state.view === 'form') {
        renderForm(appView);
    } else {
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

    if (state.view === 'form') { 
        nav.classList.add('hidden'); 
        fTitle.classList.remove('hidden'); 
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
        <div class="mt-6 text-center text-sm">
            ${isLogin ? 'Nemáte účet?' : 'Již máte účet?'} 
            <span onclick="toggleAuthView()" class="text-indigo-600 font-bold cursor-pointer hover:underline">
                ${isLogin ? 'Registrovat' : 'Přihlásit'}
            </span>
        </div>
    `;
    v.appendChild(container);
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
    // If we are deep in wizard or form, go back to start of wizard
    state.filters = { mat: null, color: null };
    state.currentStep = 1;
    state.view = 'wizard';
    render();
};

// --- EXISTING WIZARD RENDER FUNCTIONS (Simplified/Copied) ---
// (Keeping the logic from original file but adapting to new structure if needed)

const formatKg = (g) => (g / 1000).toFixed(1).replace('.', ',') + ' kg';
const getContrast = (hex) => {
    const r = parseInt(hex.substring(1,3),16), g = parseInt(hex.substring(3,5),16), b = parseInt(hex.substring(5,7),16);
    return (((r*299)+(g*587)+(b*114))/1000) >= 128 ? '#000000' : '#ffffff';
};

function renderMaterials(v) {
    const grid = document.createElement('div'); grid.className = "card-grid";
    const data = state.filters.color ? filaments.filter(i => i.color === state.filters.color) : filaments;
    const stats = data.reduce((acc, i) => { acc[i.mat] = (acc[i.mat] || 0) + (parseInt(i.g) || 0); return acc; }, {});
    
    // If no data, show message (relevant for empty DB)
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
        // card.onclick = () => window.openForm(item.id); // TODO: Re-enable edit
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
                <div class="text-xl font-black text-indigo-600 leading-none">${item.g}<span class="text-xs ml-0.5">g</span></div>
                <div class="text-[9px] text-slate-400 font-bold mt-1 uppercase">Zůstatek</div>
            </div>
        `;
        container.appendChild(card);
    });
    v.appendChild(container);
    const btn = document.createElement('button'); btn.className = "mt-6 w-full py-4 text-indigo-600 font-bold text-sm bg-indigo-50 rounded-2xl";
    btn.innerText = "Vymazat filtry"; btn.onclick = window.resetApp; v.appendChild(btn);
}

// TODO: Implement renderForm for adding/editing items (Phase 2)
function renderForm(v) {
    v.innerHTML = '<p class="text-center p-10">Form editor placeholder</p>';
    const btn = document.createElement('button');
    btn.innerText = 'Zpět';
    btn.className = "mt-4 p-2 bg-slate-200 rounded";
    btn.onclick = () => { state.view = 'wizard'; render(); };
    v.appendChild(btn);
}

// Navigation helpers
window.setStep = (s) => { state.currentStep = s; render(); };
window.toggleActionMenu = () => {
    const menu = document.getElementById('action-menu');
    menu.classList.toggle('hidden');
};
window.openForm = () => {
    state.view = 'form';
    state.editingId = null;
    document.getElementById('action-menu').classList.add('hidden');
    render();
};

// Init
checkAuth();
