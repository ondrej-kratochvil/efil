// Import modules
import { BASE_PATH, API_BASE } from './config.js';
import { state, filaments, options, spoolTemplates, stats, user, setFilaments, setOptions, setSpoolTemplates, setStats, setUser } from './state.js';
import { router } from './router.js';
import { checkAuth, login, register, logout, loadData, saveFilament, consumeFilament, updateAdminMenu } from './api.js';
import { showToast, formatKg, getContrast, getClosestColorName } from './utils.js';
import { colorNames, colorPalette } from './colors.js';
import { renderMaterials, renderColors, renderDetails } from './views/wizard.js';
import { renderAuth, handleForgotPassword, handleResetPassword } from './views/auth.js';
import { renderStats } from './views/stats.js';
import { renderConsume, editConsumption, saveConsumptionEdit, deleteConsumption } from './views/consume.js';
import { renderHelp } from './views/help.js';
import { renderAccount, handleChangePassword, handleChangeEmail, showDeleteAccountForm, hideDeleteAccountForm, handleDeleteAccount } from './views/account.js';
import { renderUsers, handleAddUser, handleChangeRole, handleRemoveUser } from './views/users.js';
import { renderSpools, handleSpoolSubmit, editSpool, cancelSpoolEdit, deleteSpool } from './views/spools.js';
import { renderAdminStats } from './views/admin-stats.js';
import { renderInventorySwitch, handleSwitchInventory } from './views/inventory-switch.js';
import { renderFormAsync, renderForm, openForm } from './views/form-view.js';
import { renderFieldInput, renderSpoolInput, toggleField, toggleSpoolField, saveFormValues, restoreFormValues, selectColor, handleFormSubmit, deleteFilament, updateWeightInfo } from './views/form.js';

// Export to window for global access (needed for inline handlers in HTML)
window.BASE_PATH = BASE_PATH;
window.API_BASE = API_BASE;
window.state = state;
window.filaments = filaments;
window.options = options;
window.spoolTemplates = spoolTemplates;
window.stats = stats;
window.user = user;
window.router = router;
window.showToast = showToast;
window.formatKg = formatKg;
window.getContrast = getContrast;
window.getClosestColorName = getClosestColorName;
window.checkAuth = checkAuth;
window.login = login;
window.register = register;
window.logout = logout;
window.loadData = loadData;
window.saveFilament = saveFilament;
window.consumeFilament = consumeFilament;
window.updateAdminMenu = updateAdminMenu;
window.render = render;
window.handleForgotPassword = handleForgotPassword;
window.handleResetPassword = handleResetPassword;
window.handleChangePassword = handleChangePassword;
window.handleChangeEmail = handleChangeEmail;
window.showDeleteAccountForm = showDeleteAccountForm;
window.hideDeleteAccountForm = hideDeleteAccountForm;
window.handleDeleteAccount = handleDeleteAccount;
window.handleAddUser = handleAddUser;
window.handleChangeRole = handleChangeRole;
window.handleRemoveUser = handleRemoveUser;
window.handleSpoolSubmit = handleSpoolSubmit;
window.editSpool = editSpool;
window.cancelSpoolEdit = cancelSpoolEdit;
window.deleteSpool = deleteSpool;
window.handleSwitchInventory = handleSwitchInventory;
window.editConsumption = editConsumption;
window.saveConsumptionEdit = saveConsumptionEdit;
window.deleteConsumption = deleteConsumption;
window.openForm = openForm;
window.renderFieldInput = renderFieldInput;
window.renderSpoolInput = renderSpoolInput;
window.toggleField = toggleField;
window.toggleSpoolField = toggleSpoolField;
window.saveFormValues = saveFormValues;
window.restoreFormValues = restoreFormValues;
window.selectColor = selectColor;
window.handleFormSubmit = handleFormSubmit;
window.deleteFilament = deleteFilament;
window.updateWeightInfo = updateWeightInfo;

// Color palettes imported from colors.js

// Note: Auth functions (checkAuth, login, register, logout) are imported from api.js
// Note: Data functions (loadData, updateAdminMenu) are imported from api.js
// Note: saveFilament and consumeFilament are imported from api.js

// Note: showToast and getClosestColorName are imported from utils.js

// --- RENDER ---
function render() {
    const appView = document.getElementById('app-view');
    const loadingScreen = document.getElementById('loading-screen');

    if (!appView || !loadingScreen) {
        return;
    }

    if (state.view === 'loading') {
        loadingScreen.classList.remove('hidden');
        appView.classList.add('hidden');
        return;
    } else {
        loadingScreen.classList.add('hidden');
        appView.classList.remove('hidden');
    }

    updateHeader();

    // Pokud nejsme v režimu vážení, odstraň případné bloky historie čerpání
    if (state.view !== 'consume') {
        const strayHistoryBlocks = document.querySelectorAll('[data-consumption-history]');
        if (strayHistoryBlocks.length > 0) {
            strayHistoryBlocks.forEach(el => el.remove());
        }
    }

    appView.innerHTML = '';

    if (state.view === 'auth') {
        renderAuth(appView);
    } else if (state.view === 'form') {
        renderFormAsync(appView);
    } else if (state.view === 'consume') {
        renderConsume(appView);
    } else if (state.view === 'stats') {
        renderStats(appView);
    } else if (state.view === 'help') {
        renderHelp(appView);
        if (state.scrollToAccessibility) {
            state.scrollToAccessibility = false;
            requestAnimationFrame(() => {
                const section = document.querySelector('[data-section="accessibility"]');
                if (section) section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    } else if (state.view === 'account') {
        renderAccount(appView);
    } else if (state.view === 'users') {
        renderUsers(appView);
    } else if (state.view === 'spools') {
        renderSpools(appView);
    } else if (state.view === 'adminStats') {
        renderAdminStats(appView);
    } else if (state.view === 'inventorySwitch') {
        renderInventorySwitch(appView);
    } else {
        if (state.currentStep === 1) {
            renderMaterials(appView);
        } else if (state.currentStep === 2) {
            renderColors(appView);
        } else if (state.currentStep === 3) {
            renderDetails(appView);
        }
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
    updateThemeToggleLabel();

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

// Auth render function moved to views/auth.js

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

// Stats render function moved to views/stats.js

window.openStats = () => {
    document.getElementById('action-menu').classList.add('hidden');
    router.push(BASE_PATH + '/stats');
};

window.openAccount = () => {
    document.getElementById('action-menu').classList.add('hidden');
    router.push(BASE_PATH + '/account');
};

window.openUsers = () => {
    document.getElementById('action-menu').classList.add('hidden');
    router.push(BASE_PATH + '/users');
};

window.openSpools = () => {
    document.getElementById('action-menu').classList.add('hidden');
    router.push(BASE_PATH + '/spools');
};

window.openHelp = () => {
    document.getElementById('action-menu').classList.add('hidden');
    router.push(BASE_PATH + '/help');
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
    const inputVal = document.getElementById('c-val').value;

    if (!inputVal || inputVal.trim() === '') {
        showToast('Zadejte hodnotu');
        return;
    }

    if(state.consumeMode === 'used') {
        const consumed = parseInt(inputVal);
        if (isNaN(consumed) || consumed <= 0) {
            showToast('Zadejte kladné číslo');
            return;
        }
        grams = -consumed;
    } else {
        // Weight mode: NewNetto = MeasuredGross - SpoolWeight
        // Diff = NewNetto - OldNetto
        const measuredGross = parseInt(inputVal);
        if (isNaN(measuredGross) || measuredGross <= 0) {
            showToast('Zadejte kladné číslo');
            return;
        }
        const spoolWeight = item.spool_weight || 0;
        const currentNetto = item.g;

        const newNetto = measuredGross - spoolWeight;
        grams = newNetto - currentNetto;
    }

    if (grams === 0) {
        showToast('Rozdíl hmotnosti je nulový');
        return;
    }

    consumeFilament(item.id, grams, desc, date);
}

// Consume render function moved to views/consume.js

// --- FORM LOGIC ---

// Form render functions and handlers moved to views/form.js

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
    // If we're in a form view, go back to previous page
    // Otherwise reset to wizard/mat
    if (state.view === 'form' || state.view === 'consume') {
        // Clear form state before going back
        state.editingId = null;
        state.consumeId = null;
        state.formValues = null;
        
        // Try to go back in history, fallback to wizard/mat if no history
        if (window.history.length > 1) {
            window.history.back();
        } else {
            state.filters = { mat: null, color: null };
            state.currentStep = 1;
            router.push(BASE_PATH + '/wizard/mat');
        }
    } else {
        // For other views, reset to wizard/mat
        state.filters = { mat: null, color: null };
        state.currentStep = 1;
        router.push(BASE_PATH + '/wizard/mat');
    }
};

// Note: formatKg and getContrast are imported from utils.js

// Wizard render functions moved to views/wizard.js

window.setStep = (s) => {
    state.currentStep = s;
    const path = s === 1 ? '/wizard/mat' : (s === 2 ? '/wizard/bar' : '/wizard/vyr');
    router.push(BASE_PATH + path);
};
window.toggleActionMenu = () => {
    const menu = document.getElementById('action-menu');
    menu.classList.toggle('hidden');
    updateThemeToggleLabel();
};
window.toggleTheme = () => {
    const root = document.documentElement;
    const current = root.getAttribute('data-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('efil-theme', next); } catch (_) {}
    updateThemeToggleLabel();
};
function updateThemeToggleLabel() {
    const label = document.getElementById('theme-toggle-label');
    if (!label) return;
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    label.textContent = current === 'dark' ? 'Světlý režim' : 'Tmavý režim';
}
// updateWeightInfo and openForm moved to views/form.js

window.openConsume = (id) => {
    state.consumeId = id;
    state.consumeMode = 'used';
    router.push(BASE_PATH + `/consume/${id}`);
}

// Help render function moved to views/help.js
// Account render function and handlers moved to views/account.js

// Users render function and handlers moved to views/users.js

// Spools render function and handlers moved to views/spools.js

// Admin stats render function moved to views/admin-stats.js
// Inventory switch render function and handler moved to views/inventory-switch.js

// Consumption edit/delete handlers moved to views/consume.js

// Spool management handlers moved to views/spools.js

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
        if (menu && !menu.classList.contains('hidden')) {
            menu.classList.add('hidden');
        }
        return;
    }
    if (e.key === 'F1') {
        e.preventDefault();
        if (user) {
            document.getElementById('action-menu').classList.add('hidden');
            router.push(BASE_PATH + '/help');
        }
        return;
    }
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        if (user) {
            document.getElementById('action-menu').classList.add('hidden');
            openForm();
        }
        return;
    }
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        if (user) {
            document.getElementById('action-menu').classList.add('hidden');
            openStats();
        }
        return;
    }
});

// Footer: odkaz na prohlášení o přístupnosti → Nápověda + scroll
(function() {
    const footerLink = document.getElementById('footer-accessibility-link');
    if (footerLink) {
        footerLink.addEventListener('click', (e) => {
            e.preventDefault();
            state.scrollToAccessibility = true;
            router.push(BASE_PATH + '/help');
        });
    }
})();

checkAuth();
