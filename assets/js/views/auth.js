// Auth view render functions
import { state } from '../state.js';
import { API_BASE, BASE_PATH } from '../config.js';
import { showToast } from '../utils.js';
import { login, loadData } from '../api.js';
import { router } from '../router.js';

export function renderAuth(v) {
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
                    <input type="email" name="email" autocomplete="email" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="name@example.com">
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
                    <input type="password" name="password" autocomplete="new-password" required minlength="6" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="Alespoň 6 znaků">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Potvrdit heslo</label>
                    <input type="password" name="password_confirm" autocomplete="new-password" required minlength="6" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="Zadejte znovu">
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
                    <input type="email" name="email" autocomplete="email" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="name@example.com">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Heslo</label>
                    <input type="password" name="password" autocomplete="${isLogin ? 'current-password' : 'new-password'}" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="********">
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
export async function handleForgotPassword(e) {
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
            showToast(data.message || 'Pokud účet existuje, byl odeslán email s instrukcemi');
            state.authView = 'login';
            if (window.render) window.render();
        } else {
            showToast(data.error || 'Chyba při odesílání emailu');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

// Reset password handler
export async function handleResetPassword(e) {
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
            showToast(data.message || 'Heslo bylo úspěšně změněno');
            state.authView = 'login';
            state.resetToken = null;
            router.push(BASE_PATH + '/');
        } else {
            showToast(data.error || 'Chyba při změně hesla');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}
