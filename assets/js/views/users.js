// Users view render function and handlers
import { state } from '../state.js';
import { API_BASE } from '../config.js';
import { showToast } from '../utils.js';

export async function renderUsers(v) {
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
                    <input type="email" name="email" autocomplete="email" required class="w-full bg-slate-50 border-none rounded-xl p-3 font-bold" placeholder="uzivatel@example.com">
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
export async function handleAddUser(e) {
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
            if (window.render) window.render();
        } else {
            showToast(data.error || 'Chyba při přidávání uživatele');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}

export async function handleChangeRole(userId, newRole) {
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
            if (window.render) window.render();
        }
    } catch (err) {
        showToast('Chyba sítě');
        if (window.render) window.render();
    }
}

export async function handleRemoveUser(userId, email) {
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
            if (window.render) window.render();
        } else {
            showToast(data.error || 'Chyba při odebírání uživatele');
        }
    } catch (err) {
        showToast('Chyba sítě');
    }
}
