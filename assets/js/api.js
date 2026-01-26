// API communication functions
import { API_BASE, BASE_PATH } from './config.js';
import { state, user, setFilaments, setOptions, setSpoolTemplates, setStats, setUser } from './state.js';
import { router } from './router.js';
import { showToast } from './utils.js';
// Note: render function is in app.js, accessed via window.render

/**
 * Check authentication status
 */
export async function checkAuth() {
    try {
        const res = await fetch(`${API_BASE}/auth/me.php`);
        const data = await res.json();
        if (data.authenticated) {
            setUser(data.user);
            await loadData();
            // Navigate to current URL or default to wizard
            const path = window.location.pathname;
            // Check if path is root (empty, /, or ends with / and has no known routes)
            const segments = path.split('/').filter(s => s);
            const appRoutes = ['wizard', 'form', 'consume', 'stats', 'help', 'account', 'users', 'spools', 'admin-stats', 'inventory-switch', 'forgot-password', 'reset-password'];
            const hasAppRoute = segments.some(seg => appRoutes.includes(seg));

            if (!hasAppRoute) {
                // No app route in path, treat as root
                router.replace(BASE_PATH + '/wizard/mat');
            } else {
                router.handleRoute(path);
            }
        } else {
            router.replace(BASE_PATH + '/');
        }
    } catch (err) {
        router.replace('/');
    }
}

/**
 * Login user
 */
export async function login(email, password) {
    try {
        const res = await fetch(`${API_BASE}/auth/login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const data = await res.json();
        if (res.ok) {
            setUser(data.user);
            await loadData();
            router.push(BASE_PATH + '/wizard/mat');
        } else {
            showToast(data.error || 'Chyba přihlášení');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

/**
 * Register new user
 */
export async function register(email, password) {
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

/**
 * Logout user
 */
export async function logout() {
    // Close menu before logout
    document.getElementById('action-menu').classList.add('hidden');
    try {
        const res = await fetch(`${API_BASE}/auth/logout.php`);
        const data = await res.json();

        // Delete session cookie on client side as backup (in case server-side deletion fails)
        // Use path="/" which is the default PHP session cookie path
        document.cookie = 'PHPSESSID=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';

        setUser(null);
        state.authView = 'login';
        state.view = 'auth';
        // Force reload to clear any cached state
        window.location.href = BASE_PATH + '/';
    } catch (err) {
        // Even if logout fails, clear local state and redirect
        setUser(null);
        state.authView = 'login';
        state.view = 'auth';
        window.location.href = BASE_PATH + '/';
    }
}

/**
 * Load all data (filaments, options, spools, stats)
 */
export async function loadData() {
    try {
        const [resFilaments, resOptions, resSpools, resStats] = await Promise.all([
            fetch(`${API_BASE}/filaments/list.php`),
            fetch(`${API_BASE}/data/options.php`),
            fetch(`${API_BASE}/spools/list.php`),
            fetch(`${API_BASE}/dashboard/stats.php`)
        ]);

        if (!resFilaments.ok) throw new Error('Failed to load filaments');
        if (!resOptions.ok) throw new Error('Failed to load options');
        if (!resSpools.ok) throw new Error('Failed to load spools');
        if (!resStats.ok) throw new Error('Failed to load stats');

        const [filamentsData, optionsData, spoolsData, statsData] = await Promise.all([
            resFilaments.json(),
            resOptions.json(),
            resSpools.json(),
            resStats.json()
        ]);

        setFilaments(Array.isArray(filamentsData) ? filamentsData : []);
        setOptions(optionsData || { materials: [], manufacturers: [], locations: [], sellers: [] });
        setSpoolTemplates(Array.isArray(spoolsData) ? spoolsData : []);
        setStats(statsData || null);

        // Add admin menu item if user is admin_efil
        updateAdminMenu();

        // Call render function (available via window.render)
        if (window.render) {
            window.render();
        }
    } catch (err) {
        console.error('Data load error', err);
        showToast('Chyba načítání dat');
        if (state.view === 'loading') {
            state.view = 'auth';
            state.authView = 'login';
            if (window.render) {
                window.render();
            }
        }
    }
}

/**
 * Save filament (create or update)
 */
export async function saveFilament(data) {
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
            router.push(BASE_PATH + '/wizard/mat');
        } else {
            const err = await res.json();
            showToast(err.error || 'Chyba při ukládání');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

/**
 * Consume filament (record consumption)
 */
export async function consumeFilament(filamentId, amount, description, date) {
    try {
        const res = await fetch(`${API_BASE}/filaments/consume.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                filament_id: filamentId,
                amount_grams: amount,
                description: description,
                consumption_date: date
            })
        });
        const data = await res.json();
        if (res.ok) {
            showToast('Spotřeba zaznamenána');
            await loadData();
            router.push(BASE_PATH + '/wizard/mat');
        } else {
            showToast(data.error || 'Chyba při ukládání');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

/**
 * Update admin menu with dynamic items
 */
export async function updateAdminMenu() {
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
            if (inventories.length > 1) {
                const invSwitchBtn = document.createElement('button');
                invSwitchBtn.setAttribute('data-inventory-switch', 'true');
                invSwitchBtn.onclick = () => {
                    document.getElementById('action-menu').classList.add('hidden');
                    router.push(BASE_PATH + '/inventory-switch');
                };
                invSwitchBtn.className = 'w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left';
                invSwitchBtn.innerHTML = `
                    <div class="bg-blue-100 text-blue-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg></div>
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
            router.push(BASE_PATH + '/admin-stats');
        };
        adminBtn.className = 'w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left';
        adminBtn.innerHTML = `
            <div class="bg-emerald-100 text-emerald-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg></div>
            Statistiky eFil
        `;
        logoutBtn.parentNode.insertBefore(adminBtn, logoutBtn);
    }
}
