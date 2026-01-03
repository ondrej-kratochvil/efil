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
    formFieldsStatus: { mat: 'select', man: 'select', loc: 'select', seller: 'select', spool: 'select' },
    expandedGroups: new Set() // Track which filament groups are expanded
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

        // Add admin menu item if user is admin_efil
        updateAdminMenu();
        
        render();
    } catch (err) {
        console.error('Data load error', err);
        showToast('Chyba načítání dat');
    }
}

async function updateAdminMenu() {
    const menu = document.getElementById('action-menu');
    const existingAdminBtn = menu.querySelector('[data-admin-stats]');
    const existingInvSwitchBtn = menu.querySelector('[data-inventory-switch]');
    
    // Remove existing dynamic buttons if present
    if (existingAdminBtn) existingAdminBtn.remove();
    if (existingInvSwitchBtn) existingInvSwitchBtn.remove();
    
    const logoutBtn = menu.querySelector('button[onclick="logout()"]');
    if (!logoutBtn) return;
    
    // Check if user has access to multiple inventories
    try {
        const res = await fetch(`${API_BASE}/inventory/list.php`);
        if (res.ok) {
            const inventories = await res.json();
            
            // Add inventory switch button if user has access to multiple inventories
            if (inventories.length > 1) {
                const invSwitchBtn = document.createElement('button');
                invSwitchBtn.setAttribute('data-inventory-switch', 'true');
                invSwitchBtn.onclick = () => {
                    document.getElementById('action-menu').classList.add('hidden');
                    router.push('/inventory/switch');
                };
                invSwitchBtn.className = 'w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left';
                invSwitchBtn.innerHTML = `
                    <div class="bg-blue-100 text-blue-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                    Přepnout evidenci
                `;
                logoutBtn.parentNode.insertBefore(invSwitchBtn, logoutBtn);
            }
        }
    } catch (err) {
        console.error('Failed to check inventories:', err);
    }
    
    // Add admin button if user is admin_efil
    if (user && user.role === 'admin_efil') {
        const adminBtn = document.createElement('button');
        adminBtn.setAttribute('data-admin-stats', 'true');
        adminBtn.onclick = () => {
            document.getElementById('action-menu').classList.add('hidden');
            router.push('/admin/stats');
        };
        adminBtn.className = 'w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left';
        adminBtn.innerHTML = `
            <div class="bg-emerald-100 text-emerald-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg></div>
            Statistiky eFil
        `;
        logoutBtn.parentNode.insertBefore(adminBtn, logoutBtn);
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

async function consumeFilament(filamentId, amount, description, date) {
    try {
        const res = await fetch(`${API_BASE}/filaments/consume.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ filament_id: filamentId, amount, description, consumption_date: date })
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
    // Add intro section for login page
    const introSection = document.createElement('div');
    introSection.id = 'app-intro';
    introSection.className = 'mb-8 bg-gradient-to-br from-indigo-50 to-white p-8 rounded-3xl border border-indigo-100 shadow-sm';
    introSection.innerHTML = `
        <div class="flex items-center gap-3 mb-4">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-indigo-600">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            <h1 class="text-3xl font-black text-indigo-900">eFil</h1>
        </div>
        <h2 class="text-xl font-bold text-slate-800 mb-3">Evidence Filamentů pro 3D tisk</h2>
        <p class="text-slate-600 mb-4">Profesionální správa 3D tiskových materiálů s přesným sledováním spotřeby na základě reálného čerpání, nikoliv jen odhadů.</p>
        
        <div class="space-y-3 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div>
                    <span class="font-bold text-slate-800">Přehled zásob</span>
                    <span class="text-slate-600"> - Přesná evidence hmotnosti a hodnoty skladu</span>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div>
                    <span class="font-bold text-slate-800">Sledování spotřeby</span>
                    <span class="text-slate-600"> - Záznamy čerpání s datem a popisem projektů</span>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div>
                    <span class="font-bold text-slate-800">Sdílení s týmem</span>
                    <span class="text-slate-600"> - Více uživatelů s různými oprávněními</span>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div>
                    <span class="font-bold text-slate-800">Chytré vážení</span>
                    <span class="text-slate-600"> - Automatický výpočet s tárou cívky</span>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <p class="text-sm text-slate-600 mb-2">
                <span class="font-bold text-indigo-600">Vyvinuto společností</span> 
                <a href="https://sensio.cz" target="_blank" class="font-bold text-slate-800 hover:text-indigo-600 transition-colors">Sensio.cz s.r.o.</a>
            </p>
            <p class="text-xs text-slate-500">
                Vaše zpětná vazba nám pomůže aplikaci dále vylepšovat. 
                <a href="mailto:podpora@sensio.cz" class="text-indigo-600 hover:underline">Napište nám</a>
            </p>
        </div>
        
        ${state.authView === 'login' ? `
        <div class="mt-6 text-center lg:hidden">
            <button onclick="document.getElementById('login-form-section').scrollIntoView({behavior:'smooth'})" class="text-indigo-600 font-bold hover:underline flex items-center justify-center gap-2 mx-auto">
                <span>Přejít na přihlášení</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
        </div>
        ` : ''}
    `;
    
    v.appendChild(introSection);
    
    const container = document.createElement('div');
    container.id = 'login-form-section';
    container.className = 'auth-container bg-white rounded-3xl shadow-sm border border-slate-200';
    
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
async function renderStats(v) {
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
    `;
    v.appendChild(container);

    // Load and display consumption history for inventory
    try {
        const res = await fetch(`${API_BASE}/consumption/list.php`);
        if (res.ok) {
            const history = await res.json();
            
            if (history.length > 0) {
                const historyContainer = document.createElement('div');
                historyContainer.className = "bg-white p-6 rounded-3xl shadow-sm border border-slate-200";
                historyContainer.innerHTML = `
                    <h3 class="text-lg font-black text-slate-800 mb-4">Historie čerpání (posledních ${history.length})</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left border-b border-slate-200">
                                <tr>
                                    <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Datum</th>
                                    <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Filament</th>
                                    <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Spotřeba</th>
                                    <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Poznámka</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${history.map(h => `
                                    <tr class="border-b border-slate-100">
                                        <td class="py-3 text-slate-600">${h.consumption_date}</td>
                                        <td class="py-3">
                                            <div class="font-bold text-slate-800">${h.manufacturer}</div>
                                            <div class="text-xs text-slate-500">${h.material} • ${h.color}</div>
                                        </td>
                                        <td class="py-3 font-bold text-indigo-600">${h.consumed_weight}g</td>
                                        <td class="py-3 text-slate-600 text-xs">${h.note || '-'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                v.appendChild(historyContainer);
            }
        }
    } catch (err) {
        console.error('Failed to load consumption history:', err);
    }

    const backBtn = document.createElement('button');
    backBtn.onclick = window.resetApp;
    backBtn.className = 'w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm mt-4';
    backBtn.textContent = 'Zpět na sklad';
    v.appendChild(backBtn);
}

window.openStats = () => {
    document.getElementById('action-menu').classList.add('hidden');
    router.push('/stats');
};

window.openAccount = () => {
    document.getElementById('action-menu').classList.add('hidden');
    router.push('/account');
};

window.openUsers = () => {
    document.getElementById('action-menu').classList.add('hidden');
    router.push('/users');
};

window.openSpools = () => {
    document.getElementById('action-menu').classList.add('hidden');
    router.push('/spools');
};

window.openHelp = () => {
    document.getElementById('action-menu').classList.add('hidden');
    router.push('/help');
};

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
    const desc = document.getElementById('c-desc').value || '';
    const date = document.getElementById('c-date').value || new Date().toISOString().split('T')[0];

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

    consumeFilament(item.id, grams, desc, date);
}

async function renderConsume(v) {
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

    // Load and display consumption history
    try {
        const res = await fetch(`${API_BASE}/consumption/list.php?filament_id=${item.id}`);
        if (res.ok) {
            const history = await res.json();
            
            if (history.length > 0) {
                const historyContainer = document.createElement('div');
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

        // Get currently selected manufacturer from the form
        const currentManufacturer = document.getElementById('f-man')?.value || null;
        
        // Split spools into two groups: matching manufacturer and others
        const matchingSpools = [];
        const otherSpools = [];
        
        spoolTemplates.forEach(s => {
            // Check if this spool is associated with the current manufacturer
            const hasMatch = s.manufacturers && s.manufacturers.some(m => m.name === currentManufacturer);
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
            if (matchingSpools.length > 0 && currentManufacturer) {
                optionsHtml += `<optgroup label="Ostatní">`;
            }
            optionsHtml += otherSpools.map(s => `<option value="${s.id}" ${s.id == selectedId ? 'selected' : ''}>${formatSpoolLabel(s)}</option>`).join('');
            if (matchingSpools.length > 0 && currentManufacturer) {
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
    const container = document.createElement('div'); 
    container.className = "flex flex-col gap-3 w-full";
    
    // Filter out filaments with zero or negative weight
    const activeFilaments = filaments.filter(i => parseInt(i.g) > 0);
    const filtered = activeFilaments.filter(i => (!state.filters.mat || i.mat===state.filters.mat) && (!state.filters.color || i.color===state.filters.color));
    
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
                // Show grouped item
                const totalWeight = items.reduce((sum, i) => sum + parseInt(i.g), 0);
                const firstItem = items[0];
                
                const groupCard = document.createElement('div');
                groupCard.className = "bg-gradient-to-r from-indigo-50 to-purple-50 p-4 rounded-2xl border-2 border-indigo-200 flex items-center justify-between shadow-sm cursor-pointer hover:shadow-md transition-shadow";
                groupCard.onclick = () => {
                    state.expandedGroups.add(key);
                    render();
                };
                groupCard.innerHTML = `
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-full border-2 border-indigo-300 shadow-inner" style="background-color: ${firstItem.hex}"></div>
                        <div>
                            <div class="font-bold text-slate-900 flex items-center gap-2">${firstItem.man}</div>
                            <div class="text-xs text-slate-500 font-medium uppercase mt-0.5">${firstItem.mat} • ${firstItem.color}</div>
                            <div class="text-[10px] text-indigo-600 font-bold mt-1 uppercase">${items.length} cívek</div>
                        </div>
                    </div>
                    <div class="text-2xl font-black text-indigo-600 leading-none bg-white px-4 py-3 rounded-lg">${totalWeight}<span class="text-sm ml-1">g</span></div>
                `;
                container.appendChild(groupCard);
            } else {
                // Show individual items (or single item, or expanded group)
                items.sort((a,b)=>parseInt(b.g)-parseInt(a.g)).forEach((item, idx) => {
                    const card = document.createElement('div');
                    const isInExpandedGroup = isMultiple && isExpanded;
                    card.className = `bg-white p-4 rounded-2xl border border-slate-200 flex items-center justify-between shadow-sm cursor-pointer ${isInExpandedGroup ? 'ml-6 border-l-4 border-l-indigo-400' : ''}`;
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
                
                // Add collapse button for expanded groups
                if (isMultiple && isExpanded) {
                    const collapseBtn = document.createElement('button');
                    collapseBtn.className = "ml-6 py-2 px-4 bg-indigo-100 text-indigo-600 rounded-lg font-bold text-sm hover:bg-indigo-200 transition-colors";
                    collapseBtn.textContent = "Sbalit skupinu";
                    collapseBtn.onclick = () => {
                        state.expandedGroups.delete(key);
                        render();
                    };
                    container.appendChild(collapseBtn);
                }
            }
        });
    }
    
    v.appendChild(container);
    const btn = document.createElement('button'); 
    btn.className = "mt-6 w-full py-4 text-indigo-600 font-bold text-sm bg-indigo-50 rounded-2xl";
    btn.innerText = "Vymazat filtry"; 
    btn.onclick = window.resetApp; 
    v.appendChild(btn);
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
    container.className = "max-w-4xl mx-auto space-y-6";
    container.innerHTML = `
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h1 class="text-3xl font-black text-slate-800 mb-2">Nápověda eFil</h1>
            <p class="text-slate-600">Stručný průvodce funkcemi aplikace</p>
        </div>

        <!-- Začínáme -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">🚀 Začínáme</h2>
            <ol class="list-decimal list-inside space-y-2 text-slate-700">
                <li>Zaregistrujte se pomocí emailu a hesla</li>
                <li>Po přihlášení se automaticky vytvoří vaše první evidence</li>
                <li>Klikněte na <strong>Přidat nový filament</strong> v menu</li>
                <li>Vyplňte základní informace (materiál, barva, hmotnost)</li>
                <li>Filament se zobrazí v přehledu skladu</li>
            </ol>
        </div>

        <!-- Navigace -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">🧭 Navigace skladem</h2>
            <p class="text-slate-600 mb-3">Aplikace používá třístupňový filtr pro snadné vyhledávání:</p>
            <ol class="list-decimal list-inside space-y-2 text-slate-700">
                <li><strong>MAT (Materiál)</strong> - Vyberte typ materiálu (PLA, PETG, ABS...)</li>
                <li><strong>BAR (Barva)</strong> - Vyberte barvu filamentu</li>
                <li><strong>VÝR (Výrobce/Detail)</strong> - Zobrazí se konkrétní filamenty</li>
            </ol>
            <p class="text-slate-500 text-sm mt-3">💡 Filtry můžete kombinovat nebo resetovat tlačítkem <em>Vymazat filtry</em></p>
        </div>

        <!-- Zápis čerpání -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">⚖️ Zápis čerpání</h2>
            <p class="text-slate-600 mb-3">Dva způsoby záznamu spotřeby:</p>
            <div class="space-y-4">
                <div>
                    <h3 class="font-bold text-slate-800 mb-2">Přesný úbytek:</h3>
                    <ol class="list-decimal list-inside space-y-1 text-slate-700 ml-4">
                        <li>Klikněte na hmotnost filamentu</li>
                        <li>Zadejte spotřebovanou hmotnost v gramech</li>
                        <li>Volitelně přidejte poznámku (např. název projektu)</li>
                        <li>Potvrďte tlačítkem <strong>Zapsat</strong></li>
                    </ol>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 mb-2">Vážení s cívkou:</h3>
                    <ol class="list-decimal list-inside space-y-1 text-slate-700 ml-4">
                        <li>Přepněte na režim <em>Vážení s cívkou</em></li>
                        <li>Zadejte celkovou hmotnost (cívka + filament)</li>
                        <li>Aplikace automaticky odečte táru cívky</li>
                        <li>Nový zůstatek se vypočítá automaticky</li>
                    </ol>
                </div>
            </div>
            <p class="text-slate-500 text-sm mt-3">💡 Pro přesné vážení nastavte typ cívky při přidávání filamentu</p>
        </div>

        <!-- Správa cívek -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">🎯 Typy cívek (Tára)</h2>
            <ol class="list-decimal list-inside space-y-2 text-slate-700">
                <li>Při přidávání filamentu vyberte typ cívky ze seznamu</li>
                <li>Pokud váš typ není v seznamu, klikněte na <strong>+</strong></li>
                <li>Zadejte charakteristiky (barva, materiál, průměr, šířka)</li>
                <li><strong>Důležité:</strong> Hmotnost prázdné cívky zadejte až když ji máte prázdnou</li>
                <li>Typ cívky se uloží a bude dostupný pro další filamenty</li>
            </ol>
        </div>

        <!-- Sdílení -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">👥 Sdílení evidence s týmem</h2>
            <ol class="list-decimal list-inside space-y-2 text-slate-700">
                <li>Otevřete menu → <strong>Přehled skladu</strong></li>
                <li>Klikněte na <strong>Vygenerovat kód</strong></li>
                <li>Sdílejte kód s kolegy</li>
                <li>Kolega klikne <em>Mám kód pozvánky</em> na přihlašovací stránce</li>
                <li>Po zadání kódu má přístup k vaší evidenci</li>
            </ol>
            <p class="text-slate-500 text-sm mt-3">Pro změnu oprávnění použijte menu → <strong>Správa uživatelů</strong></p>
        </div>

        <!-- Správa uživatelů -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">🔐 Správa uživatelů</h2>
            <p class="text-slate-600 mb-3">Tři úrovně oprávnění:</p>
            <ul class="space-y-2 ml-6">
                <li class="text-slate-700"><strong>Jen čtení</strong> - Prohlížení dat bez možnosti editace</li>
                <li class="text-slate-700"><strong>Zápis</strong> - Přidávání filamentů a zápis čerpání</li>
                <li class="text-slate-700"><strong>Správa</strong> - Vše včetně správy uživatelů</li>
            </ul>
            <p class="text-slate-600 mt-3 font-bold">Přidání uživatele:</p>
            <ol class="list-decimal list-inside space-y-1 text-slate-700 ml-4 mt-2">
                <li>Menu → <strong>Správa uživatelů</strong></li>
                <li>Zadejte email a vyberte oprávnění</li>
                <li>Pokud uživatel existuje, přidá se do evidence</li>
                <li>Pokud neexistuje, vytvoří se nový účet a přijde mu email</li>
            </ol>
        </div>

        <!-- Můj účet -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">⚙️ Správa účtu</h2>
            <p class="text-slate-600 mb-2">V sekci <strong>Můj účet</strong> můžete:</p>
            <ul class="space-y-2 ml-6 text-slate-700">
                <li>• Změnit heslo (zadejte současné a nové)</li>
                <li>• Změnit emailovou adresu (vyžaduje potvrzení heslem)</li>
                <li>• Smazat účet (nevratná akce, vyžaduje potvrzení)</li>
            </ul>
        </div>

        <!-- Tipy -->
        <div class="bg-indigo-50 p-6 rounded-3xl border border-indigo-200">
            <h2 class="text-xl font-black text-indigo-900 mb-3">💡 Tipy a triky</h2>
            <ul class="space-y-2 text-indigo-900">
                <li>• Používejte pole <strong>Umístění</strong> pro snadné hledání (např. "Polička A")</li>
                <li>• Číslo filamentu můžete libovolně měnit podle svého systému</li>
                <li>• Filamenty s nulovou hmotností se automaticky skrývají</li>
                <li>• Tlačítka Zpět/Vpřed v prohlížeči fungují pro navigaci v aplikaci</li>
                <li>• Demo účet slouží pouze k prohlížení, vytvořte si vlastní pro plný přístup</li>
            </ul>
        </div>

        <!-- Podpora -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 text-center">
            <h2 class="text-xl font-black text-slate-800 mb-2">📧 Potřebujete pomoc?</h2>
            <p class="text-slate-600 mb-4">Kontaktujte nás na <a href="mailto:podpora@sensio.cz" class="text-indigo-600 font-bold hover:underline">podpora@sensio.cz</a></p>
            <p class="text-sm text-slate-500">Vyvinuto společností <a href="https://sensio.cz" target="_blank" class="text-indigo-600 hover:underline">Sensio.cz s.r.o.</a></p>
        </div>

        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;
    v.appendChild(container);
}

function renderAccount(v) {
    const container = document.createElement('div');
    container.className = "max-w-2xl mx-auto space-y-4";
    container.innerHTML = `
        <!-- Current Account Info -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-slate-800 mb-4">Informace o účtu</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Email:</span>
                    <span class="font-bold">${user?.email || 'Nenačteno'}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Role:</span>
                    <span class="font-bold">${user?.role === 'admin_efil' ? 'Administrátor eFil' : 'Uživatel'}</span>
                </div>
            </div>
        </div>

        <!-- Change Password -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h3 class="text-lg font-black text-slate-800 mb-4">Změna hesla</h3>
            <form onsubmit="handleChangePassword(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Současné heslo</label>
                    <input type="password" name="current_password" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Nové heslo</label>
                    <input type="password" name="new_password" required minlength="6" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                </div>
                <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200">
                    Změnit heslo
                </button>
            </form>
        </div>

        <!-- Change Email -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h3 class="text-lg font-black text-slate-800 mb-4">Změna emailu</h3>
            <form onsubmit="handleChangeEmail(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Nový email</label>
                    <input type="email" name="new_email" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Heslo pro potvrzení</label>
                    <input type="password" name="password" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                </div>
                <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200">
                    Změnit email
                </button>
            </form>
        </div>

        <!-- Delete Account -->
        <div class="bg-red-50 p-6 rounded-3xl shadow-sm border border-red-200">
            <h3 class="text-lg font-black text-red-600 mb-2">Nebezpečná zóna</h3>
            <p class="text-sm text-red-600 mb-4">Smazáním účtu nevratně ztratíte všechna data včetně evidencí a filamentů.</p>
            <button onclick="showDeleteAccountForm()" class="w-full py-3 bg-red-500 text-white rounded-xl font-bold hover:bg-red-600 transition-colors">
                Smazat účet
            </button>
        </div>

        <!-- Delete Account Confirmation (hidden by default) -->
        <div id="delete-account-form" class="hidden bg-red-50 p-6 rounded-3xl shadow-sm border-2 border-red-300">
            <h3 class="text-lg font-black text-red-600 mb-4">⚠️ Potvrzení smazání účtu</h3>
            <form onsubmit="handleDeleteAccount(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-red-600 uppercase mb-1">Heslo</label>
                    <input type="password" name="password" required class="w-full bg-white border-2 border-red-300 rounded-xl p-3 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-red-600 uppercase mb-1">Pro potvrzení napište: SMAZAT</label>
                    <input type="text" name="confirmation" required class="w-full bg-white border-2 border-red-300 rounded-xl p-3 font-bold" placeholder="SMAZAT">
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="hideDeleteAccountForm()" class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold">
                        Zrušit
                    </button>
                    <button type="submit" class="flex-1 py-3 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 transition-colors">
                        Smazat navždy
                    </button>
                </div>
            </form>
        </div>

        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;
    v.appendChild(container);
}

// Account management handlers
window.handleChangePassword = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    
    try {
        const res = await fetch(`${API_BASE}/account/change-password.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                current_password: fd.get('current_password'),
                new_password: fd.get('new_password')
            })
        });
        const data = await res.json();
        
        if (res.ok) {
            showToast('Heslo bylo změněno');
            e.target.reset();
        } else {
            showToast(data.error || 'Chyba při změně hesla');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
};

window.handleChangeEmail = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    
    try {
        const res = await fetch(`${API_BASE}/account/change-email.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                new_email: fd.get('new_email'),
                password: fd.get('password')
            })
        });
        const data = await res.json();
        
        if (res.ok) {
            showToast('Email byl změněn');
            // Update user object
            user.email = fd.get('new_email');
            e.target.reset();
            render();
        } else {
            showToast(data.error || 'Chyba při změně emailu');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
};

window.showDeleteAccountForm = () => {
    document.getElementById('delete-account-form').classList.remove('hidden');
};

window.hideDeleteAccountForm = () => {
    document.getElementById('delete-account-form').classList.add('hidden');
};

window.handleDeleteAccount = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    
    if (!confirm('Jste si opravdu jisti? Tato akce je nevratná!')) {
        return;
    }
    
    try {
        const res = await fetch(`${API_BASE}/account/delete.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                password: fd.get('password'),
                confirmation: fd.get('confirmation')
            })
        });
        const data = await res.json();
        
        if (res.ok) {
            showToast('Účet byl smazán');
            // Redirect to login
            user = null;
            router.push('/');
        } else {
            showToast(data.error || 'Chyba při mazání účtu');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
};

async function renderUsers(v) {
    const container = document.createElement('div');
    container.className = "max-w-3xl mx-auto space-y-4";
    
    // Load users
    let users = [];
    try {
        const res = await fetch(`${API_BASE}/users/list.php`);
        if (res.ok) {
            users = await res.json();
        }
    } catch (err) {
        console.error('Failed to load users:', err);
    }
    
    const roleNames = {
        'owner': 'Vlastník',
        'manage': 'Správa',
        'write': 'Zápis',
        'read': 'Jen čtení'
    };
    
    container.innerHTML = `
        <!-- Add User Form -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-slate-800 mb-4">Přidat uživatele</h2>
            <form onsubmit="handleAddUser(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Email</label>
                    <input type="email" name="email" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="uzivatel@example.com">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Oprávnění</label>
                    <select name="role" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                        <option value="read">Jen čtení</option>
                        <option value="write" selected>Zápis</option>
                        <option value="manage">Správa</option>
                    </select>
                </div>
                <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200">
                    Přidat uživatele
                </button>
            </form>
        </div>

        <!-- Users List -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-slate-800 mb-4">Uživatelé v evidenci</h2>
            <div class="space-y-3" id="users-list">
                ${users.length === 0 ? '<p class="text-slate-400 text-center py-4">Načítání...</p>' : users.map(u => `
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                        <div>
                            <div class="font-bold text-slate-900">${u.email}</div>
                            <div class="text-xs text-slate-500 mt-1">
                                ${u.is_owner ? '<span class="bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded font-bold">VLASTNÍK</span>' : `
                                    <select onchange="handleChangeRole(${u.id}, this.value)" class="bg-white border border-slate-200 rounded px-2 py-1 text-xs font-bold" ${u.is_owner ? 'disabled' : ''}>
                                        <option value="read" ${u.inventory_role === 'read' ? 'selected' : ''}>Jen čtení</option>
                                        <option value="write" ${u.inventory_role === 'write' ? 'selected' : ''}>Zápis</option>
                                        <option value="manage" ${u.inventory_role === 'manage' ? 'selected' : ''}>Správa</option>
                                    </select>
                                `}
                            </div>
                        </div>
                        ${!u.is_owner ? `
                            <button onclick="handleRemoveUser(${u.id}, '${u.email}')" class="px-3 py-2 bg-red-50 text-red-600 rounded-lg font-bold text-sm hover:bg-red-100 transition-colors">
                                Odebrat
                            </button>
                        ` : '<div class="text-xs text-slate-400">Nelze odebrat</div>'}
                    </div>
                `).join('')}
            </div>
        </div>

        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;
    
    v.appendChild(container);
}

// User management handlers
window.handleAddUser = async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    
    try {
        const res = await fetch(`${API_BASE}/users/add.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email: fd.get('email'),
                role: fd.get('role')
            })
        });
        const data = await res.json();
        
        if (res.ok) {
            showToast(data.message || 'Uživatel přidán');
            e.target.reset();
            // Refresh users list
            state.view = 'users';
            render();
        } else {
            showToast(data.error || 'Chyba při přidávání uživatele');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
};

window.handleChangeRole = async (userId, newRole) => {
    try {
        const res = await fetch(`${API_BASE}/users/update-role.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                role: newRole
            })
        });
        const data = await res.json();
        
        if (res.ok) {
            showToast('Oprávnění změněna');
        } else {
            showToast(data.error || 'Chyba při změně oprávnění');
            // Refresh to restore original value
            render();
        }
    } catch (err) {
        showToast('Chyba sítě');
        render();
    }
};

window.handleRemoveUser = async (userId, email) => {
    if (!confirm(`Opravdu chcete odebrat uživatele ${email}?`)) {
        return;
    }
    
    try {
        const res = await fetch(`${API_BASE}/users/remove.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        const data = await res.json();
        
        if (res.ok) {
            showToast('Uživatel odebrán');
            // Refresh users list
            render();
        } else {
            showToast(data.error || 'Chyba při odebírání uživatele');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
};

async function renderSpools(v) {
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
            manufacturers = data.manufacturers || [];
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
                        ${manufacturers.map(m => `<option value="${m}">${m}</option>`).join('')}
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

async function renderAdminStats(v) {
    const container = document.createElement('div');
    container.className = "max-w-6xl mx-auto space-y-6";
    
    // Load stats
    let stats = null;
    try {
        const res = await fetch(`${API_BASE}/admin/stats.php`);
        if (res.ok) {
            stats = await res.json();
        } else {
            const err = await res.json();
            container.innerHTML = `
                <div class="bg-red-50 p-6 rounded-3xl border border-red-200">
                    <p class="text-red-600 font-bold">${err.error || 'Nedostatečná oprávnění'}</p>
                    <button onclick="window.resetApp()" class="mt-4 w-full py-3 bg-slate-100 text-slate-600 rounded-xl font-bold">Zpět</button>
                </div>
            `;
            v.appendChild(container);
            return;
        }
    } catch (err) {
        console.error('Failed to load stats:', err);
    }
    
    if (!stats) {
        container.innerHTML = '<p class="text-slate-400 text-center py-8">Načítání statistik...</p>';
        v.appendChild(container);
        return;
    }
    
    container.innerHTML = `
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6 rounded-3xl shadow-lg text-white">
            <h1 class="text-3xl font-black mb-2">📊 Statistiky eFil</h1>
            <p class="opacity-90">Celkový přehled využívání aplikace</p>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <div class="text-sm text-slate-500 font-bold uppercase">Uživatelé</div>
                <div class="text-3xl font-black text-indigo-600 mt-1">${stats.total_users}</div>
                <div class="text-xs text-slate-400 mt-1">+${stats.recent_users} za 30 dní</div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <div class="text-sm text-slate-500 font-bold uppercase">Evidence</div>
                <div class="text-3xl font-black text-purple-600 mt-1">${stats.total_inventories}</div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <div class="text-sm text-slate-500 font-bold uppercase">Filamenty</div>
                <div class="text-3xl font-black text-pink-600 mt-1">${stats.total_filaments}</div>
                <div class="text-xs text-slate-400 mt-1">${stats.total_weight_kg} kg celkem</div>
            </div>
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <div class="text-sm text-slate-500 font-bold uppercase">Spotřeba</div>
                <div class="text-3xl font-black text-amber-600 mt-1">${stats.total_consumed_kg}</div>
                <div class="text-xs text-slate-400 mt-1">${stats.total_consumptions} záznamů</div>
            </div>
        </div>

        <!-- Activity Stats -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Top Inventories -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                <h2 class="text-xl font-black text-slate-800 mb-4">🏆 Top 10 evidencí</h2>
                <div class="space-y-2">
                    ${stats.top_inventories.length === 0 ? '<p class="text-slate-400">Žádné evidence</p>' : stats.top_inventories.map((inv, idx) => `
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-black flex items-center justify-center text-sm">
                                    ${idx + 1}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800">${inv.name || 'Evidence #' + inv.id}</div>
                                    <div class="text-xs text-slate-500">${inv.filament_count} filamentů • ${Math.round(inv.total_weight / 1000 * 10) / 10} kg</div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>

            <!-- Material Distribution -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                <h2 class="text-xl font-black text-slate-800 mb-4">📦 Materiály</h2>
                <div class="space-y-2">
                    ${stats.material_distribution.length === 0 ? '<p class="text-slate-400">Žádné materiály</p>' : stats.material_distribution.map(mat => {
                        const percent = Math.round((mat.count / stats.total_filaments) * 100);
                        return `
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-bold text-slate-700">${mat.material}</span>
                                <span class="text-slate-500">${mat.count}× • ${Math.round(mat.total_weight / 1000 * 10) / 10} kg</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="bg-indigo-500 h-2 rounded-full" style="width: ${percent}%"></div>
                            </div>
                        </div>
                    `}).join('')}
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-slate-800 mb-4">⚡ Poslední aktivita</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left border-b border-slate-200">
                        <tr>
                            <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Datum</th>
                            <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Filament</th>
                            <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Spotřeba</th>
                            <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Evidence</th>
                            <th class="pb-2 font-bold text-slate-500 uppercase text-xs">Uživatel</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${stats.recent_activity.length === 0 ? '<tr><td colspan="5" class="py-4 text-slate-400 text-center">Žádná aktivita</td></tr>' : stats.recent_activity.map(act => `
                            <tr class="border-b border-slate-100">
                                <td class="py-3 text-slate-600">${act.consumption_date}</td>
                                <td class="py-3">
                                    <div class="font-bold text-slate-800">${act.manufacturer}</div>
                                    <div class="text-xs text-slate-500">${act.material} • ${act.color}</div>
                                </td>
                                <td class="py-3 font-bold text-indigo-600">${act.consumed_weight}g</td>
                                <td class="py-3 text-slate-600">${act.inventory_name || '-'}</td>
                                <td class="py-3 text-slate-500 text-xs">${act.user_email || '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>

        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;
    
    v.appendChild(container);
}

async function renderInventorySwitch(v) {
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
    
    const roleNames = {
        'owner': 'Vlastník',
        'manage': 'Správa',
        'write': 'Zápis',
        'read': 'Jen čtení'
    };
    
    container.innerHTML = `
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-2xl font-black text-slate-800 mb-4">Přepnout evidenci</h2>
            <div class="space-y-3">
                ${inventories.length === 0 ? '<p class="text-slate-400 text-center py-4">Načítání...</p>' : inventories.map(inv => `
                    <button 
                        onclick="handleSwitchInventory(${inv.id})" 
                        class="w-full flex items-center justify-between p-4 rounded-xl border-2 transition-all ${inv.is_current ? 'border-indigo-600 bg-indigo-50' : 'border-slate-200 bg-white hover:border-indigo-300'}"
                        ${inv.is_current ? 'disabled' : ''}>
                        <div class="text-left">
                            <div class="font-bold text-slate-900 flex items-center gap-2">
                                ${inv.name || `Evidence #${inv.id}`}
                                ${inv.is_current ? '<span class="text-xs bg-indigo-600 text-white px-2 py-0.5 rounded font-bold">AKTIVNÍ</span>' : ''}
                                ${inv.is_demo ? '<span class="text-xs bg-amber-100 text-amber-600 px-2 py-0.5 rounded font-bold">DEMO</span>' : ''}
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                ${inv.is_owner ? '<span class="bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded font-bold">VLASTNÍK</span>' : `<span>${roleNames[inv.role] || inv.role}</span>`}
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

        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">Zpět na sklad</button>
    `;
    
    v.appendChild(container);
}

// Handle inventory switch
window.handleSwitchInventory = async (inventoryId) => {
    try {
        const res = await fetch(`${API_BASE}/inventory/switch.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ inventory_id: inventoryId })
        });
        const data = await res.json();
        
        if (res.ok) {
            showToast('Evidence přepnuta');
            // Reload data and reset to main view
            await loadData();
            router.push('/wizard/mat');
        } else {
            showToast(data.error || 'Chyba při přepínání evidence');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
};

// Consumption edit/delete handlers
window.editConsumption = async (consumptionId) => {
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
        
        overlay.innerHTML = `
            <div class="bg-white p-6 rounded-3xl shadow-xl max-w-md w-full" onclick="event.stopPropagation()">
                <h2 class="text-xl font-black text-slate-800 mb-4">Upravit čerpání</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Filament</label>
                        <div class="text-sm font-bold text-slate-600">${consumption.manufacturer} ${consumption.material} ${consumption.color}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Spotřebováno (g)</label>
                        <input id="edit-consumed-weight" type="number" value="${consumption.consumed_weight}" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
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
};

window.saveConsumptionEdit = async (consumptionId) => {
    const consumedWeight = parseInt(document.getElementById('edit-consumed-weight').value);
    const consumptionDate = document.getElementById('edit-consumption-date').value;
    const note = document.getElementById('edit-consumption-note').value;
    
    if (!consumedWeight || consumedWeight <= 0) {
        showToast('Zadejte platnou hmotnost');
        return;
    }
    
    try {
        const res = await fetch(`${API_BASE}/consumption/update.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: consumptionId,
                consumed_weight: consumedWeight,
                consumption_date: consumptionDate,
                note: note
            })
        });
        const data = await res.json();
        
        if (res.ok) {
            showToast('Záznam aktualizován');
            document.getElementById('edit-consumption-modal').remove();
            await loadData();
            render();
        } else {
            showToast(data.error || 'Chyba při ukládání');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
};

window.deleteConsumption = async (consumptionId) => {
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
            render();
        } else {
            showToast(data.error || 'Chyba při mazání');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
};

// Spool management handlers
window.handleSpoolSubmit = async (e) => {
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
    
    // Get manufacturer IDs from options.manufacturers
    const manufIds = [];
    if (options.manufacturers) {
        for (const manufName of selectedManuf) {
            // Manufacturers are loaded from DB, need to match by name
            // Will need to extend API to accept names or load IDs
            manufIds.push(manufName);
        }
    }
    
    const payload = {
        color,
        material,
        outer_diameter_mm: diameter,
        width_mm: width,
        weight_grams: weight,
        visual_description: description,
        manufacturer_names: manufIds // Send names, API will resolve to IDs
    };
    
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
            render();
        } else {
            showToast(data.error || 'Chyba při ukládání');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
};

window.editSpool = async (spoolId) => {
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
        
        // Select manufacturers
        const manufSelect = document.getElementById('spool-manufacturers');
        const manufNames = spool.manufacturers.map(m => m.name);
        Array.from(manufSelect.options).forEach(opt => {
            opt.selected = manufNames.includes(opt.value);
        });
        
        // Update form title and show cancel button
        document.getElementById('spool-form-title').textContent = 'Upravit typ cívky';
        document.getElementById('spool-cancel-btn').classList.remove('hidden');
        
        // Scroll to form
        document.getElementById('spool-form').scrollIntoView({ behavior: 'smooth' });
    } catch (err) {
        showToast('Chyba sítě');
    }
};

window.cancelSpoolEdit = () => {
    document.getElementById('spool-form').reset();
    document.getElementById('spool-id').value = '';
    document.getElementById('spool-form-title').textContent = 'Přidat typ cívky';
    document.getElementById('spool-cancel-btn').classList.add('hidden');
};

window.deleteSpool = async (spoolId) => {
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
            render();
        } else {
            showToast(data.error || 'Chyba při mazání');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
};

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
