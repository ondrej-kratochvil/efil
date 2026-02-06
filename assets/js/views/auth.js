// Auth view render functions
import { state } from '../state.js';
import { API_BASE, BASE_PATH } from '../config.js';
import { showToast } from '../utils.js';
import { login, loadData } from '../api.js';
import { router } from '../router.js';
import { t, getCurrentLang } from '../i18n.js';

const INTRO_HIDDEN_MOBILE_KEY = 'efil-intro-hidden-mobile';
const MOBILE_MAX_WIDTH = 767;

function isMobile() {
    return typeof window !== 'undefined' && window.matchMedia(`(max-width: ${MOBILE_MAX_WIDTH}px)`).matches;
}

/** Toggle úvodního textu na mobilu (zobrazit/skrýt); voláno z odkazu v auth view. */
export function toggleAuthIntro() {
    if (!isMobile()) return;
    const intro = document.getElementById('app-intro');
    const link = document.getElementById('intro-toggle-link');
    if (!intro || !link) return;
    const hidden = intro.classList.toggle('hidden');
    try { localStorage.setItem(INTRO_HIDDEN_MOBILE_KEY, hidden ? '1' : '0'); } catch (_) {}
    link.textContent = hidden ? (typeof window !== 'undefined' && window.t ? window.t('auth.introShow') : 'Zobrazit úvod') : (typeof window !== 'undefined' && window.t ? window.t('auth.introHide') : 'Skrýt úvod');
}

export function renderAuth(v) {
    const mobile = isMobile();
    const introHidden = mobile && (typeof localStorage !== 'undefined' && localStorage.getItem(INTRO_HIDDEN_MOBILE_KEY) === '1');

    // Add intro section for login page
    const introSection = document.createElement('div');
    introSection.id = 'app-intro';
    introSection.className = 'mb-8 bg-gradient-to-br from-indigo-50 to-white p-8 rounded-3xl border border-indigo-100 shadow-sm' + (introHidden ? ' hidden' : '');
    introSection.innerHTML = `
        <div class="flex items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-indigo-600">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                <h1 class="text-3xl font-black text-indigo-900">${t('auth.title')}</h1>
            </div>
            <div class="flex items-center gap-1 text-sm">
                <button type="button" onclick="window.setLang && window.setLang('cs')" class="px-2 py-1 rounded font-bold ${getCurrentLang() === 'cs' ? 'bg-indigo-100 text-indigo-700' : 'text-slate-500 hover:bg-slate-100'}">Česky</button>
                <span class="text-slate-300">|</span>
                <button type="button" onclick="window.setLang && window.setLang('en')" class="px-2 py-1 rounded font-bold ${getCurrentLang() === 'en' ? 'bg-indigo-100 text-indigo-700' : 'text-slate-500 hover:bg-slate-100'}">English</button>
            </div>
        </div>
        <h2 class="text-xl font-bold text-slate-800 mb-3">${t('auth.subtitle')}</h2>
        <p class="text-slate-600 mb-4">${t('auth.description')}</p>

        <div class="space-y-3 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div>
                    <span class="font-bold text-slate-800">${t('intro.feature1Title')}</span>
                    <span class="text-slate-600"> - ${t('intro.feature1Desc')}</span>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div>
                    <span class="font-bold text-slate-800">${t('intro.feature2Title')}</span>
                    <span class="text-slate-600"> - ${t('intro.feature2Desc')}</span>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div>
                    <span class="font-bold text-slate-800">${t('intro.feature3Title')}</span>
                    <span class="text-slate-600"> - ${t('intro.feature3Desc')}</span>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div>
                    <span class="font-bold text-slate-800">${t('intro.feature4Title')}</span>
                    <span class="text-slate-600"> - ${t('intro.feature4Desc')}</span>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <p class="text-sm text-slate-600 mb-2">
                <span class="font-bold text-indigo-600">${t('auth.developedBy')}</span>
                <a href="https://sensio.cz" target="_blank" class="font-bold text-slate-800 hover:text-indigo-600 transition-colors">Sensio.cz s.r.o.</a>
            </p>
            <p class="text-xs text-slate-500">
                ${t('auth.feedback')}
                <a href="mailto:podpora@sensio.cz" class="text-indigo-600 hover:underline">${t('auth.writeUs')}</a>
            </p>
        </div>

        ${state.authView === 'login' ? `
        <div class="mt-6 text-center lg:hidden">
            <button onclick="document.getElementById('login-form-section').scrollIntoView({behavior:'smooth'})" class="text-indigo-600 font-bold hover:underline flex items-center justify-center gap-2 mx-auto">
                <span>${t('auth.scrollToLogin')}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
        </div>
        ` : ''}
    `;

    v.appendChild(introSection);

    // Na mobilu odkaz Zobrazit/Skrýt úvod (na desktopu úvod vždy viditelný, není třeba skrývat)
    if (mobile && state.authView === 'login') {
        const toggleWrap = document.createElement('div');
        toggleWrap.className = 'mb-4 md:hidden';
        toggleWrap.innerHTML = `<button type="button" id="intro-toggle-link" class="text-indigo-600 font-bold hover:underline" aria-label="${introHidden ? 'Zobrazit úvodní text' : 'Skrýt úvodní text'}">${introHidden ? 'Zobrazit úvod' : 'Skrýt úvod'}</button>`;
        v.appendChild(toggleWrap);
        const link = toggleWrap.querySelector('#intro-toggle-link');
        if (link) link.addEventListener('click', toggleAuthIntro);
    }

    const container = document.createElement('div');
    container.id = 'login-form-section';
    container.className = 'auth-container bg-white rounded-3xl shadow-sm border border-slate-200';

    if (state.authView === 'forgotPassword') {
        container.innerHTML = `
            <h2 class="text-2xl font-black text-center mb-6 text-slate-800">${t('auth.forgotTitle')}</h2>
            <p class="text-sm text-slate-600 mb-4 text-center">${t('auth.forgotDescription')}</p>
            <form onsubmit="handleForgotPassword(event)" class="flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">${t('auth.email')}</label>
                    <input type="email" name="email" autocomplete="email" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="name@example.com">
                </div>
                <button type="submit" class="mt-2 w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 active:scale-95 transition-transform">
                    ${t('auth.sendLink')}
                </button>
            </form>
            <div class="mt-6 text-center text-sm">
                <span onclick="state.authView='login'; render();" class="text-indigo-600 font-bold cursor-pointer hover:underline">
                    ${t('auth.backToLogin')}
                </span>
            </div>
        `;
    } else if (state.authView === 'resetPassword') {
        container.innerHTML = `
            <h2 class="text-2xl font-black text-center mb-6 text-slate-800">${t('auth.resetTitle')}</h2>
            <p class="text-sm text-slate-600 mb-4 text-center">${t('auth.resetDescription')}</p>
            <form onsubmit="handleResetPassword(event)" class="flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">${t('auth.newPassword')}</label>
                    <input type="password" name="password" autocomplete="new-password" required minlength="6" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="Alespoň 6 znaků">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">${t('auth.confirmPassword')}</label>
                    <input type="password" name="password_confirm" autocomplete="new-password" required minlength="6" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="Zadejte znovu">
                </div>
                <button type="submit" class="mt-2 w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 active:scale-95 transition-transform">
                    ${t('auth.setPassword')}
                </button>
            </form>
        `;
    } else {
        const isLogin = state.authView === 'login';
        container.innerHTML = `
            <h2 class="text-2xl font-black text-center mb-6 text-slate-800">${isLogin ? t('auth.login') : t('auth.register')}</h2>
            <form onsubmit="handleAuthSubmit(event)" class="flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">${t('auth.email')}</label>
                    <input type="email" name="email" autocomplete="email" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="name@example.com">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">${t('auth.password')}</label>
                    <input type="password" name="password" autocomplete="${isLogin ? 'current-password' : 'new-password'}" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="********">
                </div>
                ${isLogin ? `
                <div class="text-right">
                    <span onclick="state.authView='forgotPassword'; render();" class="text-xs text-indigo-600 font-bold cursor-pointer hover:underline">
                        ${t('auth.forgotPassword')}
                    </span>
                </div>
                ` : ''}
                <button type="submit" class="mt-2 w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 active:scale-95 transition-transform">
                    ${isLogin ? t('auth.loginButton') : t('auth.registerButton')}
                </button>
            </form>
            ${isLogin ? `
            <button onclick="login('demo@efil.cz', 'demo1234')" class="mt-3 w-full py-3 bg-slate-100 text-slate-600 rounded-xl font-bold border border-slate-200">
                ${t('auth.demoButton')}
            </button>
            <button onclick="joinInventory()" class="mt-2 w-full py-3 bg-white text-indigo-600 rounded-xl font-bold border border-indigo-100">
                ${t('auth.inviteCodeButton')}
            </button>
            ` : ''}
            <div class="mt-6 text-center text-sm">
                ${isLogin ? t('auth.noAccount') : t('auth.hasAccount')}
                <span onclick="toggleAuthView()" class="text-indigo-600 font-bold cursor-pointer hover:underline">
                    ${isLogin ? t('auth.doRegister') : t('auth.doLogin')}
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
