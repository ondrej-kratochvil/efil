// Account view render function and handlers
import { user } from '../state.js';
import { API_BASE, BASE_PATH } from '../config.js';
import { showToast } from '../utils.js';
import { router } from '../router.js';
import { t } from '../i18n.js';

export function renderAccount(v) {
    const container = document.createElement('div');
    container.className = "max-w-2xl mx-auto space-y-4";
    container.innerHTML = `
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-slate-800 mb-4">${t('account.title')}</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">${t('account.email')}</span>
                    <span class="font-bold">${user?.email || t('account.notLoaded')}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">${t('account.role')}</span>
                    <span class="font-bold">${user?.role === 'admin_efil' ? t('account.adminRole') : t('account.userRole')}</span>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h3 class="text-lg font-black text-slate-800 mb-4">${t('account.changePassword')}</h3>
            <form onsubmit="handleChangePassword(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">${t('account.currentPassword')}</label>
                    <input type="password" name="current_password" autocomplete="current-password" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">${t('account.newPassword')}</label>
                    <input type="password" name="new_password" autocomplete="new-password" required minlength="6" class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                </div>
                <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200">${t('account.changePasswordButton')}</button>
            </form>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h3 class="text-lg font-black text-slate-800 mb-4">${t('account.changeEmail')}</h3>
            <form onsubmit="handleChangeEmail(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">${t('account.newEmail')}</label>
                    <input type="email" name="new_email" autocomplete="email" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">${t('account.passwordConfirm')}</label>
                    <input type="password" name="password" autocomplete="current-password" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold">
                </div>
                <button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-200">${t('account.changeEmailButton')}</button>
            </form>
        </div>

        <div class="bg-red-50 p-6 rounded-3xl shadow-sm border border-red-200">
            <h3 class="text-lg font-black text-red-600 mb-2">${t('account.dangerZone')}</h3>
            <p class="text-sm text-red-600 mb-4">${t('account.dangerZoneDesc')}</p>
            <button onclick="showDeleteAccountForm()" class="w-full py-3 bg-red-500 text-white rounded-xl font-bold hover:bg-red-600 transition-colors">${t('account.deleteAccount')}</button>
        </div>

        <div id="delete-account-form" class="hidden bg-red-50 p-6 rounded-3xl shadow-sm border-2 border-red-300">
            <h3 class="text-lg font-black text-red-600 mb-4">⚠️ ${t('account.deleteConfirmTitle')}</h3>
            <form onsubmit="handleDeleteAccount(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-red-600 uppercase mb-1">${t('account.passwordLabel')}</label>
                    <input type="password" name="password" autocomplete="current-password" required class="w-full bg-white border-2 border-red-300 rounded-xl p-3 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-red-600 uppercase mb-1">${t('account.confirmTypeDelete')}</label>
                    <input type="text" name="confirmation" required class="w-full bg-white border-2 border-red-300 rounded-xl p-3 font-bold" placeholder="${t('account.confirmPlaceholder')}">
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="hideDeleteAccountForm()" class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold">${t('common.cancel')}</button>
                    <button type="submit" class="flex-1 py-3 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 transition-colors">${t('account.deleteForever')}</button>
                </div>
            </form>
        </div>

        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">${t('inventorySwitch.backToStock')}</button>
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
            showToast(t('account.emailChanged'));
            if (window.user) window.user.email = fd.get('new_email');
            e.target.reset();
            if (window.render) window.render();
        } else {
            showToast(data.error || t('account.errorChangeEmail'));
        }
    } catch (err) {
        showToast(t('common.errorNetwork'));
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
            showToast(t('account.accountDeleted'));
            if (window.user) window.user = null;
            router.push(BASE_PATH + '/');
        } else {
            showToast(data.error || t('account.errorDeleteAccount'));
        }
    } catch (err) {
        showToast(t('common.errorNetwork'));
    }
}
