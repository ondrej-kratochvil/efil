// Configuration
const API_BASE = 'api';

// State
let filaments = [];
let options = { materials: [], manufacturers: [], locations: [], sellers: [] };
let spoolTemplates = [];
let stats = null;
let user = null;
let state = {
    view: 'loading', // loading, auth, wizard, form, consume, stats, help, account, users, spools, adminStats, inventorySwitch
    authView: 'login', // login, register, forgotPassword, resetPassword
    currentStep: 1,
    filters: { mat: null, color: null },
    editingId: null,
    consumeId: null,
    consumeMode: 'used', // used (subtract), weight (calculate from gross)
    formFieldsStatus: { mat: 'select', man: 'select', loc: 'select', seller: 'select', spool: 'select' }
};

// Router - History API support
const router = {
    // Navigate to a new route
    push(path, stateData = {}) {
        window.history.pushState({ ...stateData, path }, '', path);
        this.handleRoute(path, stateData);
    },
    
    // Replace current route
    replace(path, stateData = {}) {
        window.history.replaceState({ ...stateData, path }, '', path);
        this.handleRoute(path, stateData);
    },
    
    // Handle route changes
    handleRoute(path, stateData = {}) {
        // Parse path
        const segments = path.split('/').filter(s => s);
        
        if (!segments.length || segments[0] === '') {
            // Root - show auth or wizard based on login state
            if (user) {
                state.view = 'wizard';
                state.currentStep = 1;
                state.filters = { mat: null, color: null };
            } else {
                state.view = 'auth';
                state.authView = 'login';
            }
        } else if (segments[0] === 'wizard') {
            state.view = 'wizard';
            if (segments[1] === 'mat') state.currentStep = 1;
            else if (segments[1] === 'bar') state.currentStep = 2;
            else if (segments[1] === 'vyr') state.currentStep = 3;
            else state.currentStep = 1;
        } else if (segments[0] === 'form') {
            state.view = 'form';
            state.editingId = segments[1] ? parseInt(segments[1]) : null;
        } else if (segments[0] === 'consume') {
            state.view = 'consume';
            state.consumeId = segments[1] ? parseInt(segments[1]) : null;
        } else if (segments[0] === 'stats') {
            state.view = 'stats';
        } else if (segments[0] === 'help') {
            state.view = 'help';
        } else if (segments[0] === 'account') {
            state.view = 'account';
        } else if (segments[0] === 'users') {
            state.view = 'users';
        } else if (segments[0] === 'spools') {
            state.view = 'spools';
        } else if (segments[0] === 'admin-stats') {
            state.view = 'adminStats';
        } else if (segments[0] === 'inventory-switch') {
            state.view = 'inventorySwitch';
        } else if (segments[0] === 'forgot-password') {
            state.view = 'auth';
            state.authView = 'forgotPassword';
        } else if (segments[0] === 'reset-password') {
            state.view = 'auth';
            state.authView = 'resetPassword';
            state.resetToken = new URLSearchParams(window.location.search).get('token');
        }
        
        render();
    },
    
    // Get current route path based on state
    getPath() {
        if (state.view === 'auth') {
            if (state.authView === 'forgotPassword') return '/forgot-password';
            if (state.authView === 'resetPassword') return '/reset-password';
            return '/';
        } else if (state.view === 'wizard') {
            if (state.currentStep === 1) return '/wizard/mat';
            if (state.currentStep === 2) return '/wizard/bar';
            if (state.currentStep === 3) return '/wizard/vyr';
        } else if (state.view === 'form') {
            return state.editingId ? `/form/${state.editingId}` : '/form';
        } else if (state.view === 'consume') {
            return `/consume/${state.consumeId}`;
        } else if (state.view === 'stats') {
            return '/stats';
        } else if (state.view === 'help') {
            return '/help';
        } else if (state.view === 'account') {
            return '/account';
        } else if (state.view === 'users') {
            return '/users';
        } else if (state.view === 'spools') {
            return '/spools';
        } else if (state.view === 'adminStats') {
            return '/admin-stats';
        } else if (state.view === 'inventorySwitch') {
            return '/inventory-switch';
        }
        return '/';
    }
};

// Listen to browser back/forward buttons
window.addEventListener('popstate', (e) => {
    if (e.state && e.state.path) {
        router.handleRoute(e.state.path, e.state);
    } else {
        router.handleRoute(window.location.pathname);
    }
});

const colorNames = [
    { name: 'Černá', hex: '#000000' }, { name: 'Bílá', hex: '#ffffff' }, { name: 'Červená', hex: '#ff0000' },
    { name: 'Zelená', hex: '#00ff00' }, { name: 'Modrá', hex: '#0000ff' }, { name: 'Žlutá', hex: '#ffff00' },
    { name: 'Oranžová', hex: '#ffa500' }, { name: 'Šedá', hex: '#808080' }, { name: 'Stříbrná', hex: '#c0c0c0' },
    { name: 'Zlatá', hex: '#ffd700' }, { name: 'Fialová', hex: '#800080' }, { name: 'Hnědá', hex: '#a52a2a' },
    { name: 'Růžová', hex: '#ffc0cb' }, { name: 'Azurová', hex: '#00ffff' }, { name: 'Limetková', hex: '#00ff00' },
    { name: 'Tmavě modrá', hex: '#000080' }
];

// Paleta 32 barev pro color picker
const colorPalette = [
    // Prvních 16 barev
    { name: 'Černá', hex: '#000000' },
    { name: 'Bílá', hex: '#FFFFFF' },
    { name: 'Šedá', hex: '#8E9089' },
    { name: 'Stříbrná', hex: '#A6A9AA' },
    { name: 'Červená', hex: '#C12E1F' },
    { name: 'Modrá', hex: '#0A2989' },
    { name: 'Zelená', hex: '#3F8E43' },
    { name: 'Žlutá', hex: '#FEC600' },
    { name: 'Oranžová', hex: '#FF6A13' },
    { name: 'Hnědá', hex: '#6F5034' },
    { name: 'Fialová', hex: '#5E43B7' },
    { name: 'Růžová', hex: '#F55A74' },
    { name: 'Zlatá', hex: '#E4BD68' },
    { name: 'Průhledná / Čirá', hex: '#E8E8E8' },
    { name: 'Námořnická modrá', hex: '#001F3F' },
    { name: 'Vojenská zelená', hex: '#4B5320' },
    // Dalších 16 barev
    { name: 'Azurová', hex: '#00AEEF' },
    { name: 'Purpurová', hex: '#EC008C' },
    { name: 'Limetková', hex: '#BECF00' },
    { name: 'Tyrkysová', hex: '#40E0D0' },
    { name: 'Vínová', hex: '#800000' },
    { name: 'Béžová / Tělová', hex: '#F7E6DE' },
    { name: 'Bronzová', hex: '#847D48' },
    { name: 'Měděná', hex: '#B87333' },
    { name: 'Mentolová', hex: '#AAF0D1' },
    { name: 'Levandulová', hex: '#B1BCD9' },
    { name: 'Antracitová', hex: '#333333' },
    { name: 'Neonová zelená', hex: '#39FF14' },
    { name: 'Neonová žlutá', hex: '#FFFF00' },
    { name: 'Mramorová', hex: '#FFFFFF' }, // Bílá s tečkami - použijeme bílou
    { name: 'Galaxy černá', hex: '#000000' }, // Černá se třpytkami - použijeme černou
    { name: 'Vícebarevná', hex: '#FF00FF' } // Rainbow - použijeme fialovou jako placeholder
];

// --- AUTH ---

async function checkAuth() {
    try {
        const res = await fetch(`${API_BASE}/auth/me.php`);
        const data = await res.json();
        if (data.authenticated) {
            user = data.user;
            loadData();
            // Navigate to current URL or default to wizard
            const path = window.location.pathname;
            if (path === '/' || path === '') {
                router.replace('/wizard/mat');
            } else {
                router.handleRoute(path);
            }
        } else {
            router.replace('/');
        }
    } catch (err) {
        console.error('Auth check failed', err);
        router.replace('/');
    }
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
            loadData();
            router.push('/wizard/mat');
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
    // Close menu before logout
    document.getElementById('action-menu').classList.add('hidden');
    await fetch(`${API_BASE}/auth/logout.php`);
    user = null;
    state.authView = 'login';
    router.push('/');
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
        if (resOptions.ok) {
            const optionsData = await resOptions.json();
            options = optionsData;
            console.log('Options loaded:', options); // Debug
        } else {
            console.error('Options request failed:', resOptions.status);
        }
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
            state.filters = { mat: null, color: null };
            router.push('/wizard/mat');
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
            router.push('/wizard/mat');
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
    else if (state.view === 'help') renderHelp(appView);
    else if (state.view === 'account') renderAccount(appView);
    else if (state.view === 'users') renderUsers(appView);
    else if (state.view === 'spools') renderSpools(appView);
    else if (state.view === 'adminStats') renderAdminStats(appView);
    else if (state.view === 'inventorySwitch') renderInventorySwitch(appView);
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

    if (['form', 'consume', 'stats', 'help', 'account', 'users', 'spools', 'adminStats', 'inventorySwitch'].includes(state.view)) {
        nav.classList.add('hidden');
        fTitle.classList.remove('hidden');
        if (state.view === 'form') fTitle.innerText = 'Editor';
        else if (state.view === 'consume') fTitle.innerText = 'Vážení';
        else if (state.view === 'stats') fTitle.innerText = 'Přehled skladu';
        else if (state.view === 'help') fTitle.innerText = 'Nápověda';
        else if (state.view === 'account') fTitle.innerText = 'Můj účet';
        else if (state.view === 'users') fTitle.innerText = 'Správa uživatelů';
        else if (state.view === 'spools') fTitle.innerText = 'Správa typů cívek';
        else if (state.view === 'adminStats') fTitle.innerText = 'Statistiky eFil';
        else if (state.view === 'inventorySwitch') fTitle.innerText = 'Přepnout evidenci';
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
    const container = document.createElement('div');
    container.className = 'auth-container bg-white rounded-3xl shadow-sm border border-slate-200 mt-10';
    
    if (state.authView === 'forgotPassword') {
        container.innerHTML = `
            <h2 class="text-2xl font-black text-center mb-6 text-slate-800">Zapomenuté heslo</h2>
            <p class="text-sm text-slate-600 mb-4 text-center">Zadejte svou emailovou adresu a my vám pošleme odkaz pro obnovení hesla.</p>
            <form onsubmit="handleForgotPassword(event)" class="flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Email</label>
                    <input type="email" name="email" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="name@example.com">
                </div>
                <button type="submit" class="mt-2 w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 active:scale-95 transition-transform">
                    Odeslat odkaz
                </button>
            </form>
            <div class="mt-6 text-center text-sm">
                <span onclick="state.authView='login'; render();" class="text-indigo-600 font-bold cursor-pointer hover:underline">
                    ← Zpět na přihlášení
                </span>
            </div>
        `;
    } else if (state.authView === 'resetPassword') {
        container.innerHTML = `
            <h2 class="text-2xl font-black text-center mb-6 text-slate-800">Nastavení hesla</h2>
            <p class="text-sm text-slate-600 mb-4 text-center">Zadejte nové heslo pro váš účet.</p>
            <form onsubmit="handleResetPassword(event)" class="flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Nové heslo</label>
                    <input type="password" name="password" required minlength="6" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="Alespoň 6 znaků">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Potvrdit heslo</label>
                    <input type="password" name="password_confirm" required minlength="6" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="Zadejte znovu">
                </div>
                <button type="submit" class="mt-2 w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 active:scale-95 transition-transform">
                    Nastavit heslo
                </button>
            </form>
        `;
    } else {
        const isLogin = state.authView === 'login';
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
                ${isLogin ? `
                <div class="text-right">
                    <span onclick="state.authView='forgotPassword'; render();" class="text-xs text-indigo-600 font-bold cursor-pointer hover:underline">
                        Zapomněli jste heslo?
                    </span>
                </div>
                ` : ''}
                <button type="submit" class="mt-2 w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 active:scale-95 transition-transform">
                    ${isLogin ? 'Přihlásit se' : 'Vytvořit účet'}
                </button>
            </form>
            ${isLogin ? `
            <button onclick="login('demo@efil.cz', 'demo1234')" class="mt-3 w-full py-3 bg-slate-100 text-slate-600 rounded-xl font-bold border border-slate-200">
                Vyzkoušet Demo
            </button>
            <button onclick="joinInventory()" class="mt-2 w-full py-3 bg-white text-indigo-600 rounded-xl font-bold border border-indigo-100">
                Mám kód pozvánky
            </button>
            ` : ''}
            <div class="mt-6 text-center text-sm">
                ${isLogin ? 'Nemáte účet?' : 'Již máte účet?'}
                <span onclick="toggleAuthView()" class="text-indigo-600 font-bold cursor-pointer hover:underline">
                    ${isLogin ? 'Registrovat' : 'Přihlásit'}
                </span>
            </div>
        `;
    }
    v.appendChild(container);
}

// Forgot password handler
window.handleForgotPassword = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const email = fd.get('email');
    
    try {
        const res = await fetch(`${API_BASE}/auth/forgot-password.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        const data = await res.json();
        
        if (res.ok) {
            showToast('Email s instrukcemi byl odeslán');
            state.authView = 'login';
            render();
        } else {
            showToast(data.error || 'Chyba při odesílání emailu');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
};

// Reset password handler
window.handleResetPassword = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const password = fd.get('password');
    const passwordConfirm = fd.get('password_confirm');
    
    if (password !== passwordConfirm) {
        showToast('Hesla se neshodují');
        return;
    }
    
    const token = state.resetToken;
    if (!token) {
        showToast('Chybí token');
        return;
    }
    
    try {
        const res = await fetch(`${API_BASE}/auth/reset-password.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token, password })
        });
        const data = await res.json();
        
        if (res.ok) {
            showToast('Heslo bylo změněno');
            state.authView = 'login';
            router.push('/');
        } else {
            showToast(data.error || 'Chyba při změně hesla');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
};

// --- SHARE LOGIC ---
window.generateShareCode = async () => {
    try {
        const res = await fetch(`${API_BASE}/inventory/share.php`, { method: 'POST' });
        const data = await res.json();
        if (res.ok) {
            document.getElementById('share-code-display').innerText = data.code;
            document.getElementById('share-section').classList.remove('hidden');
        } else {
            showToast('Chyba generování kódu');
        }
    } catch (e) { showToast('Chyba sítě'); }
};

window.joinInventory = async () => {
    const code = prompt("Zadejte kód pozvánky:");
    if(!code) return;
    try {
        const res = await fetch(`${API_BASE}/inventory/join.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code })
        });
        const data = await res.json();
        if(res.ok) {
            showToast('Připojeno! Načítám...');
            await loadData();
        } else {
            showToast(data.error || 'Chyba připojení');
        }
    } catch(e) { showToast('Chyba sítě'); }
};

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

        <div class="bg-indigo-50 p-4 rounded-2xl border border-indigo-100 text-center space-y-2">
            <h3 class="font-bold text-indigo-900">Sdílení skladu</h3>
            <p class="text-xs text-indigo-600">Vygenerujte kód pro kolegy, aby mohli spravovat tento sklad.</p>
            <button onclick="generateShareCode()" class="bg-white text-indigo-600 px-4 py-2 rounded-xl font-bold text-sm shadow-sm">Vygenerovat kód</button>
            <div id="share-section" class="hidden mt-2 pt-2 border-t border-indigo-200">
                <div class="text-xs text-slate-400 uppercase font-bold">Váš kód:</div>
                <div id="share-code-display" class="text-xl font-black tracking-widest select-all"></div>
            </div>
        </div>

        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm mt-4">Zpět na sklad</button>
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
    render(); // Just re-render, don't change URL
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
    if (!item) { router.push('/wizard/mat'); return; }

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
    // Check if list is an object with top/others structure or a plain array
    const hasGroups = list && typeof list === 'object' && !Array.isArray(list) && list.top && list.others;
    const listArray = hasGroups ? [...(list.top || []), ...(list.others || [])] : (Array.isArray(list) ? list : []);
    const isSelect = state.formFieldsStatus[key] === 'select';

    // If list is empty and in select mode, show input instead (user can add new value)
    if (isSelect && listArray.length > 0) {
        let optionsHtml = `<option value="" disabled ${!value ? 'selected' : ''}>Vybrat...</option>`;

        if (hasGroups && list.top && list.top.length > 0) {
            // Render with optgroups
            optionsHtml += `<optgroup label="Nejčastější">`;
            optionsHtml += list.top.map(i => `<option value="${i}" ${i === value ? 'selected' : ''}>${i}</option>`).join('');
            optionsHtml += `</optgroup>`;

            if (list.others && list.others.length > 0) {
                optionsHtml += `<optgroup label="Ostatní">`;
                optionsHtml += list.others.map(i => `<option value="${i}" ${i === value ? 'selected' : ''}>${i}</option>`).join('');
                optionsHtml += `</optgroup>`;
            }
        } else {
            // Render as plain list
            optionsHtml += listArray.map(i => `<option value="${i}" ${i === value ? 'selected' : ''}>${i}</option>`).join('');
        }

        return `
            <select id="f-${key}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold appearance-none">
                ${optionsHtml}
            </select>
            <button type="button" onclick="toggleField('${key}')" class="bg-indigo-100 text-indigo-600 p-3 rounded-xl font-bold">+</button>
        `;
    }
    // Show input if list is empty or in input mode
    return `
        <input id="f-${key}" type="text" value="${value || ''}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="Zadejte novou hodnotu">
        ${listArray.length > 0 ? `<button type="button" onclick="toggleField('${key}')" class="bg-slate-200 text-slate-500 p-3 rounded-xl font-bold">zpět</button>` : ''}
    `;
};

window.renderSpoolInput = (selectedId) => {
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

        return `
            <select id="f-spool" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold appearance-none">
                <option value="" disabled ${!selectedId ? 'selected' : ''}>Vybrat...</option>
                <option value="" ${selectedId === null || selectedId === '' ? 'selected' : ''}>Žádná / Neznámá</option>
                ${spoolTemplates.map(s => `<option value="${s.id}" ${s.id == selectedId ? 'selected' : ''}>${formatSpoolLabel(s)}</option>`).join('')}
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
};

window.toggleSpoolField = () => {
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

    state.formFieldsStatus.spool = state.formFieldsStatus.spool === 'select' ? 'input' : 'select';
    render();

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
};

window.toggleField = (key) => {
    // Save all form values before switching
    saveFormValues();

    const wasSelect = state.formFieldsStatus[key] === 'select';
    state.formFieldsStatus[key] = wasSelect ? 'input' : 'select';
    render();

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
};

// Save all form values to state
window.saveFormValues = () => {
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
};

// Restore all form values from state
window.restoreFormValues = () => {
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
};

window.selectColor = (hex, name) => {
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
};

window.handleFormSubmit = async (e) => {
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

        // Create spool if at least one identifying field is provided
        if (spoolColor || spoolMaterial || spoolDiameter || spoolWidth || spoolDesc) {
            try {
                const res = await fetch(`${API_BASE}/spools/save.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        color: spoolColor,
                        material: spoolMaterial,
                        outer_diameter_mm: spoolDiameter,
                        width_mm: spoolWidth,
                        weight_grams: spoolWeightInput,
                        visual_description: spoolDesc
                    })
                });
                const newSpool = await res.json();
                if (res.ok && newSpool.id) {
                    spoolId = newSpool.id;
                    spoolWeight = newSpool.weight_grams;
                    // Reload spools list
                    const resSpools = await fetch(`${API_BASE}/spools/list.php`);
                    if (resSpools.ok) spoolTemplates = await resSpools.json();
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

    const item = {
        id: state.editingId,
        user_display_id: userDisplayId ? parseInt(userDisplayId) : null,
        mat: document.getElementById('f-mat').value,
        color: document.getElementById('f-color').value,
        hex: document.getElementById('f-hex').value,
        man: document.getElementById('f-man').value,
        g: weight,
        loc: document.getElementById('f-loc').value,
        price: document.getElementById('f-price').value,
        seller: document.getElementById('f-seller') ? document.getElementById('f-seller').value : '',
        date: document.getElementById('f-date').value,
        spool_id: spoolId
    };
    saveFilament(item);
};

function renderForm(v) {
    // Use saved values if available, otherwise use item values
    const baseItem = state.editingId ? filaments.find(i => i.id === state.editingId) : { mat: '', color: '', hex: '#4f46e5', man: '', g: 1000, loc: '', price: '', date: '', seller: '' };

    // Calculate next available user_display_id for new filament
    let suggestedDisplayId = null;
    if (!state.editingId && filaments.length > 0) {
        const maxId = Math.max(...filaments.map(f => parseInt(f.user_display_id) || 0));
        suggestedDisplayId = maxId + 1;
    } else if (!state.editingId) {
        suggestedDisplayId = 1;
    }

    const item = state.formValues ? {
        ...baseItem,
        user_display_id: state.formValues.user_display_id !== undefined ? state.formValues.user_display_id : (baseItem.user_display_id || suggestedDisplayId),
        mat: state.formValues.mat !== undefined ? state.formValues.mat : baseItem.mat,
        man: state.formValues.man !== undefined ? state.formValues.man : baseItem.man,
        loc: state.formValues.loc !== undefined ? state.formValues.loc : baseItem.loc,
        seller: state.formValues.seller !== undefined ? state.formValues.seller : baseItem.seller,
        color: state.formValues.color !== undefined ? state.formValues.color : baseItem.color,
        hex: state.formValues.hex !== undefined ? state.formValues.hex : baseItem.hex,
        g: state.formValues.g !== undefined ? parseInt(state.formValues.g) : (baseItem.initial_weight_grams || baseItem.g),
        price: state.formValues.price !== undefined ? state.formValues.price : baseItem.price,
        date: state.formValues.date !== undefined ? state.formValues.date : baseItem.date,
        spool_id: state.formValues.spool !== undefined ? state.formValues.spool : baseItem.spool_id
    } : { ...baseItem, user_display_id: baseItem.user_display_id || suggestedDisplayId };

    // Update weight mode from saved values
    if (state.formValues && state.formValues.weightMode) {
        state.weightMode = state.formValues.weightMode;
    }

    // Ensure we have lists even if empty
    const mats = options.materials || [];
    const mans = options.manufacturers || [];
    const locs = options.locations || [];
    const sellers = options.sellers || [];

    // Don't force input mode - always show select with + button, even if list is empty
    // Users can manually switch to input mode if they want to add a new value

    // Initialize spool status if not set
    if (!state.formFieldsStatus.spool) state.formFieldsStatus.spool = 'select';
    if (!state.weightMode) state.weightMode = 'netto';

    const form = document.createElement('div');
    form.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200 max-w-lg mx-auto space-y-5";
    form.innerHTML = `
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
        <div class="grid grid-cols-2 gap-4">
            <div class="field-container"><label class="text-[10px] font-bold text-slate-400 uppercase">Materiál <span class="text-red-500">*</span></label><div class="input-group">${renderFieldInput('mat', mats, item.mat)}</div></div>
            <div class="field-container"><label class="text-[10px] font-bold text-slate-400 uppercase">Výrobce</label><div class="input-group">${renderFieldInput('man', mans, item.man)}</div></div>
        </div>
        <div class="field-container">
            <label class="text-[10px] font-bold text-slate-400 uppercase">Počáteční hmotnost (g) <span class="text-red-500">*</span></label>
            <div class="flex gap-2">
                <select id="f-weight-mode" onchange="updateWeightInfo()" class="bg-slate-50 border-none rounded-xl p-3 font-bold text-sm">
                    <option value="netto" ${!state.weightMode || state.weightMode === 'netto' ? 'selected' : ''}>Bez cívky</option>
                    <option value="gross" ${state.weightMode === 'gross' ? 'selected' : ''}>S cívkou</option>
                </select>
                <input id="f-g" type="number" value="${item.initial_weight_grams || item.g}" class="flex-1 bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="Hmotnost">
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

// Delete filament handler
window.deleteFilament = async (id) => {
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
            showToast('Filament smazán');
            await loadData();
            state.filters = { mat: null, color: null };
            router.push('/wizard/mat');
        } else {
            showToast(data.error || 'Chyba při mazání');
        }
    } catch (e) {
        showToast('Chyba sítě');
    }
};

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
    render(); // Just re-render, stay on same URL
};

window.resetApp = () => {
    state.filters = { mat: null, color: null };
    state.currentStep = 1;
    router.push('/wizard/mat');
};

const formatKg = (g) => (g / 1000).toFixed(1).replace('.', ',') + ' kg';
const getContrast = (hex) => {
    const r = parseInt(hex.substring(1,3),16), g = parseInt(hex.substring(3,5),16), b = parseInt(hex.substring(5,7),16);
    return (((r*299)+(g*587)+(b*114))/1000) >= 128 ? '#000000' : '#ffffff';
};

function renderMaterials(v) {
    const grid = document.createElement('div'); grid.className = "card-grid";
    // Filter out filaments with zero or negative weight
    const activeFilaments = filaments.filter(i => parseInt(i.g) > 0);
    const data = state.filters.color ? activeFilaments.filter(i => i.color === state.filters.color) : activeFilaments;
    const stats = data.reduce((acc, i) => { acc[i.mat] = (acc[i.mat] || 0) + (parseInt(i.g) || 0); return acc; }, {});

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

    Object.keys(stats).sort((a,b)=>stats[b]-stats[a]).forEach(m => {
        const card = document.createElement('div');
        card.className = "aspect-square bg-white border border-slate-200 rounded-2xl p-3 flex items-center justify-center text-center relative shadow-sm cursor-pointer hover:border-indigo-300 transition-colors";
        card.onclick = () => { 
            state.filters.mat = m; 
            const nextStep = state.filters.color ? 3 : 2;
            state.currentStep = nextStep;
            router.push(nextStep === 2 ? '/wizard/bar' : '/wizard/vyr');
        };
        card.innerHTML = `<div class="text-[10px] font-bold text-slate-400 absolute top-2 right-2">${formatKg(stats[m])}</div><div class="text-base font-black uppercase tracking-tight">${m}</div>`;
        grid.appendChild(card);
    });
    v.appendChild(grid);
}

function renderColors(v) {
    const grid = document.createElement('div'); grid.className = "card-grid";
    // Filter out filaments with zero or negative weight
    const activeFilaments = filaments.filter(i => parseInt(i.g) > 0);
    const data = state.filters.mat ? activeFilaments.filter(i => i.mat === state.filters.mat) : activeFilaments;
    const stats = data.reduce((acc, i) => { if(!acc[i.color]) acc[i.color]={g:0, hex:i.hex}; acc[i.color].g+=(parseInt(i.g)||0); return acc; }, {});

    Object.keys(stats).sort((a,b)=>stats[b].g-stats[a].g).forEach(c => {
        const info = stats[c], contrast = getContrast(info.hex), card = document.createElement('div');
        card.className = "aspect-square rounded-2xl p-3 flex items-center justify-center text-center shadow-sm relative cursor-pointer";
        card.style.backgroundColor = info.hex; card.style.color = contrast;
        if(info.hex.toLowerCase()==='#ffffff') card.classList.add('border','border-slate-200');
        card.onclick = () => { 
            state.filters.color = c; 
            const nextStep = state.filters.mat ? 3 : 1;
            state.currentStep = nextStep;
            router.push(nextStep === 1 ? '/wizard/mat' : '/wizard/vyr');
        };
        card.innerHTML = `<div class="text-[10px] font-bold absolute top-2 right-2 opacity-70">${formatKg(info.g)}</div><div class="text-[13px] font-black uppercase px-1">${c}</div>`;
        grid.appendChild(card);
    });
    v.appendChild(grid);
}

function renderDetails(v) {
    const container = document.createElement('div'); container.className = "flex flex-col gap-3 w-full";
    // Filter out filaments with zero or negative weight
    const activeFilaments = filaments.filter(i => parseInt(i.g) > 0);
    const filtered = activeFilaments.filter(i => (!state.filters.mat || i.mat===state.filters.mat) && (!state.filters.color || i.color===state.filters.color)).sort((a,b)=>b.g-a.g);
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
                    <div class="text-[10px] text-indigo-500 font-bold mt-1 uppercase">${item.loc ? `${item.loc} | ` : ''}#${item.user_display_id || item.id}</div>
                </div>
            </div>
            <div onclick="event.stopPropagation(); window.openConsume(${item.id})" class="text-2xl font-black text-indigo-600 leading-none bg-indigo-50 px-4 py-3 rounded-lg hover:bg-indigo-100 transition-colors cursor-pointer">${item.g}<span class="text-sm ml-1">g</span></div>
        `;
        container.appendChild(card);
    });
    v.appendChild(container);
    const btn = document.createElement('button'); btn.className = "mt-6 w-full py-4 text-indigo-600 font-bold text-sm bg-indigo-50 rounded-2xl";
    btn.innerText = "Vymazat filtry"; btn.onclick = window.resetApp; v.appendChild(btn);
}

window.setStep = (s) => { 
    state.currentStep = s; 
    const path = s === 1 ? '/wizard/mat' : (s === 2 ? '/wizard/bar' : '/wizard/vyr');
    router.push(path);
};
window.toggleActionMenu = () => {
    const menu = document.getElementById('action-menu');
    menu.classList.toggle('hidden');
};
window.updateWeightInfo = () => {
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
};

window.openForm = () => {
    // If opening fresh (not edit), reset editingId and form status
    if (!state.editingId) {
        state.editingId = null;
        // Reset form fields to select mode
        state.formFieldsStatus = { mat: 'select', man: 'select', loc: 'select', seller: 'select', spool: 'select' };
        state.weightMode = 'netto';
        state.formValues = null; // Clear saved values when opening new form
    }
    // We update this via onclick in renderDetails so editingId is set before this call if editing

    document.getElementById('action-menu').classList.add('hidden');
    
    const path = state.editingId ? `/form/${state.editingId}` : '/form';
    router.push(path);

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
};

window.openConsume = (id) => {
    state.consumeId = id;
    state.consumeMode = 'used';
    router.push(`/consume/${id}`);
}

// Placeholder render functions for new views (will be implemented in next tasks)
function renderHelp(v) {
    const container = document.createElement('div');
    container.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200 space-y-4";
    container.innerHTML = `
        <h2 class="text-2xl font-black text-slate-800">Nápověda</h2>
        <p class="text-slate-600">Zde bude podrobná nápověda k aplikaci.</p>
        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;
    v.appendChild(container);
}

function renderAccount(v) {
    const container = document.createElement('div');
    container.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200 space-y-4";
    container.innerHTML = `
        <h2 class="text-2xl font-black text-slate-800">Můj účet</h2>
        <p class="text-slate-600">Zde bude správa účtu (změna hesla, emailu, smazání).</p>
        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;
    v.appendChild(container);
}

function renderUsers(v) {
    const container = document.createElement('div');
    container.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200 space-y-4";
    container.innerHTML = `
        <h2 class="text-2xl font-black text-slate-800">Správa uživatelů</h2>
        <p class="text-slate-600">Zde bude správa uživatelů evidence.</p>
        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;
    v.appendChild(container);
}

function renderSpools(v) {
    const container = document.createElement('div');
    container.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200 space-y-4";
    container.innerHTML = `
        <h2 class="text-2xl font-black text-slate-800">Správa typů cívek</h2>
        <p class="text-slate-600">Zde bude správa typů cívek s vazbami na výrobce.</p>
        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;
    v.appendChild(container);
}

function renderAdminStats(v) {
    const container = document.createElement('div');
    container.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200 space-y-4";
    container.innerHTML = `
        <h2 class="text-2xl font-black text-slate-800">Statistiky eFil</h2>
        <p class="text-slate-600">Zde budou statistiky pro admin_efil (počet uživatelů, evidencí, atd.).</p>
        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;
    v.appendChild(container);
}

function renderInventorySwitch(v) {
    const container = document.createElement('div');
    container.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200 space-y-4";
    container.innerHTML = `
        <h2 class="text-2xl font-black text-slate-800">Přepnout evidenci</h2>
        <p class="text-slate-600">Zde bude přepínač mezi evidencemi.</p>
        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;
    v.appendChild(container);
}

// Close menu when clicking outside or pressing ESC
document.addEventListener('click', (e) => {
    const menu = document.getElementById('action-menu');
    const trigger = document.getElementById('menu-trigger');

    // Close menu if clicking outside of it and the trigger button
    if (!menu.contains(e.target) && !trigger.contains(e.target) && !menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const menu = document.getElementById('action-menu');
        if (!menu.classList.contains('hidden')) {
            menu.classList.add('hidden');
        }
    }
});

checkAuth();
