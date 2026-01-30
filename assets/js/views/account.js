// Account view render function and handlers
import { user } from '../state.js';
import { API_BASE, BASE_PATH } from '../config.js';
import { showToast } from '../utils.js';
import { router } from '../router.js';

export function renderAccount(v) {
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
                    <input type="password" name="current_password" autocomplete="current-password" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Nové heslo</label>
                    <input type="password" name="new_password" autocomplete="new-password" required minlength="6" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
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
                    <input type="email" name="new_email" autocomplete="email" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Heslo pro potvrzení</label>
                    <input type="password" name="password" autocomplete="current-password" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
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
                    <input type="password" name="password" autocomplete="current-password" required class="w-full bg-white border-2 border-red-300 rounded-xl p-3 font-bold">
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
export async function handleChangePassword(e) {
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
}

export async function handleChangeEmail(e) {
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
            if (window.user) {
                window.user.email = fd.get('new_email');
            }
            e.target.reset();
            if (window.render) window.render();
        } else {
            showToast(data.error || 'Chyba při změně emailu');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

export function showDeleteAccountForm() {
    const form = document.getElementById('delete-account-form');
    if (form) form.classList.remove('hidden');
}

export function hideDeleteAccountForm() {
    const form = document.getElementById('delete-account-form');
    if (form) form.classList.add('hidden');
}

export async function handleDeleteAccount(e) {
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
            if (window.user) window.user = null;
            router.push(BASE_PATH + '/');
        } else {
            showToast(data.error || 'Chyba při mazání účtu');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}
