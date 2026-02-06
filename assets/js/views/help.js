// Help view render function
import { BASE_PATH } from '../config.js';
import { router } from '../router.js';
import { t } from '../i18n.js';

export function renderHelp(v) {
    const container = document.createElement('div');
    container.className = "max-w-4xl mx-auto space-y-6";

    // Mapa webu: běžné sekce + Statistiky eFil s označením (pouze administrátor)
    const mapItems = [
        { nav: 'wizard', key: 'map.evidence' },
        { nav: 'stats', key: 'map.stats' },
        { nav: 'inventory-switch', key: 'map.inventorySwitch' },
        { nav: 'account', key: 'map.account' },
        { nav: 'users', key: 'map.users' },
        { nav: 'spools', key: 'map.spools' },
        { nav: 'manufacturers', key: 'map.manufacturers' },
        { nav: 'help', key: 'map.help' },
        { nav: 'admin-stats', key: 'map.adminStats', adminOnly: true }
    ];
    const mapListHtml = mapItems.map(({ nav, key, adminOnly }) => {
        const label = t(key) + (adminOnly ? ` <span class="text-slate-500 text-sm font-normal">(${t('map.adminOnly')})</span>` : '');
        return `<li><a href="#" data-nav="${nav}" class="text-indigo-600 font-bold hover:underline">${label}</a></li>`;
    }).join('');

    container.innerHTML = `
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h1 class="text-3xl font-black text-slate-800 mb-2">${t('help.title')}</h1>
            <p class="text-slate-600">${t('help.subtitle')}</p>
        </div>

        <!-- Mapa webu -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">🗺️ ${t('map.title')}</h2>
            <p class="text-slate-600 mb-3">${t('map.intro')}</p>
            <ul class="space-y-2 text-slate-700">
                ${mapListHtml}
            </ul>
        </div>

        <!-- Začínáme -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">🚀 ${t('help.gettingStartedTitle')}</h2>
            <ol class="list-decimal list-inside space-y-2 text-slate-700">
                <li>${t('help.gettingStarted1')}</li>
                <li>${t('help.gettingStarted2')}</li>
                <li>${t('help.gettingStarted3')}</li>
                <li>${t('help.gettingStarted4')}</li>
                <li>${t('help.gettingStarted5')}</li>
            </ol>
        </div>

        <!-- Navigace -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">🧭 ${t('help.navTitle')}</h2>
            <p class="text-slate-600 mb-3">${t('help.navIntro')}</p>
            <ol class="list-decimal list-inside space-y-2 text-slate-700">
                <li>${t('help.nav1')}</li>
                <li>${t('help.nav2')}</li>
                <li>${t('help.nav3')}</li>
            </ol>
            <p class="text-slate-500 text-sm mt-3">💡 ${t('help.navFilterTip')}</p>
            <p class="text-slate-600 mt-4 mb-2 font-bold">${t('help.navPriceHeading')}</p>
            <p class="text-slate-600 text-sm">${t('help.navPriceDesc')}</p>
            <p class="text-slate-600 mt-4 mb-2 font-bold">${t('help.navAddWizardHeading')}</p>
            <p class="text-slate-600 text-sm">${t('help.navAddWizardDesc')}</p>
            <ul class="list-disc list-inside space-y-1 text-slate-700 text-sm mt-2 ml-2">
                <li>${t('help.navAddMat')}</li>
                <li>${t('help.navAddBar')}</li>
                <li>${t('help.navAddVyr')}</li>
            </ul>
        </div>

        <!-- Zápis čerpání -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">⚖️ ${t('help.consumeTitle')}</h2>
            <p class="text-slate-600 mb-3">${t('help.consumeIntro')}</p>
            <div class="space-y-4">
                <div>
                    <h3 class="font-bold text-slate-800 mb-2">${t('help.consumeExactTitle')}</h3>
                    <ol class="list-decimal list-inside space-y-1 text-slate-700 ml-4">
                        <li>${t('help.consumeExact1')}</li>
                        <li>${t('help.consumeExact2')}</li>
                        <li>${t('help.consumeExact3')}</li>
                        <li>${t('help.consumeExact4')}</li>
                    </ol>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 mb-2">${t('help.consumeWeighTitle')}</h3>
                    <ol class="list-decimal list-inside space-y-1 text-slate-700 ml-4">
                        <li>${t('help.consumeWeigh1')}</li>
                        <li>${t('help.consumeWeigh2')}</li>
                        <li>${t('help.consumeWeigh3')}</li>
                        <li>${t('help.consumeWeigh4')}</li>
                    </ol>
                </div>
            </div>
            <p class="text-slate-500 text-sm mt-3">💡 ${t('help.consumeTip')}</p>
        </div>

        <!-- Statistiky -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">📊 ${t('help.statsTitle')}</h2>
            <p class="text-slate-600 mb-3">${t('help.statsIntro')}</p>
            <ul class="list-disc list-inside space-y-1 text-slate-700 mb-3">
                <li>${t('help.statsList1')}</li>
                <li>${t('help.statsList2')}</li>
                <li>${t('help.statsList3')}</li>
                <li>${t('help.statsList4')}</li>
            </ul>
            <p class="text-slate-600 mb-2 font-bold">${t('help.statsMaterialHeading')}</p>
            <p class="text-slate-600 text-sm mb-3">${t('help.statsMaterialDesc')}</p>
            <p class="text-slate-600 mb-2 font-bold">${t('help.statsHistoryHeading')}</p>
            <p class="text-slate-600 text-sm">${t('help.statsHistoryDesc')}</p>
        </div>

        <!-- Výrobci -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">🏭 ${t('help.manufacturersTitle')}</h2>
            <p class="text-slate-600 mb-3">${t('help.manufacturersIntro')}</p>
            <ol class="list-decimal list-inside space-y-2 text-slate-700">
                <li>${t('help.manufacturers1')}</li>
                <li>${t('help.manufacturers2')}</li>
                <li>${t('help.manufacturers3')}</li>
            </ol>
            <p class="text-slate-500 text-sm mt-3">💡 ${t('help.manufacturersTip')}</p>
        </div>

        <!-- Typy cívek -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">🎯 ${t('help.spoolsTitle')}</h2>
            <ol class="list-decimal list-inside space-y-2 text-slate-700">
                <li>${t('help.spools1')}</li>
                <li>${t('help.spools2')}</li>
                <li>${t('help.spools3')}</li>
                <li>${t('help.spools4')}</li>
                <li>${t('help.spools5')}</li>
                <li>${t('help.spools6')}</li>
            </ol>
        </div>

        <!-- Sdílení -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">👥 ${t('help.sharingTitle')}</h2>
            <ol class="list-decimal list-inside space-y-2 text-slate-700">
                <li>${t('help.sharing1')}</li>
                <li>${t('help.sharing2')}</li>
                <li>${t('help.sharing3')}</li>
                <li>${t('help.sharing4')}</li>
                <li>${t('help.sharing5')}</li>
            </ol>
            <p class="text-slate-500 text-sm mt-3">${t('help.sharingPermissionTip')}</p>
        </div>

        <!-- Správa uživatelů -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">🔐 ${t('help.usersTitle')}</h2>
            <p class="text-slate-600 mb-3">${t('help.usersIntro')}</p>
            <ul class="space-y-2 ml-6">
                <li class="text-slate-700">${t('help.usersRoleRead')}</li>
                <li class="text-slate-700">${t('help.usersRoleWrite')}</li>
                <li class="text-slate-700">${t('help.usersRoleManage')}</li>
            </ul>
            <p class="text-slate-600 mt-3 font-bold">${t('help.usersAddHeading')}</p>
            <ol class="list-decimal list-inside space-y-1 text-slate-700 ml-4 mt-2">
                <li>${t('help.usersAdd1')}</li>
                <li>${t('help.usersAdd2')}</li>
                <li>${t('help.usersAdd3')}</li>
                <li>${t('help.usersAdd4')}</li>
            </ol>
        </div>

        <!-- Klávesové zkratky -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">⌨️ ${t('help.shortcutsTitle')}</h2>
            <p class="text-slate-600 mb-3">${t('help.shortcutsIntro')}</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border border-slate-200 rounded-xl overflow-hidden">
                    <thead class="bg-slate-50 text-slate-700 font-bold">
                        <tr><th class="px-4 py-2 border-b border-slate-200">${t('help.shortcutsTableShortcut')}</th><th class="px-4 py-2 border-b border-slate-200">${t('help.shortcutsTableAction')}</th></tr>
                    </thead>
                    <tbody class="text-slate-700">
                        <tr class="border-b border-slate-100"><td class="px-4 py-2"><kbd class="px-2 py-1 bg-slate-100 rounded font-mono">F1</kbd></td><td class="px-4 py-2">${t('shortcuts.help')}</td></tr>
                        <tr class="border-b border-slate-100"><td class="px-4 py-2"><kbd class="px-2 py-1 bg-slate-100 rounded font-mono">Alt</kbd>+<kbd class="px-2 py-1 bg-slate-100 rounded font-mono">N</kbd></td><td class="px-4 py-2">${t('shortcuts.newFilament')}</td></tr>
                        <tr class="border-b border-slate-100"><td class="px-4 py-2"><kbd class="px-2 py-1 bg-slate-100 rounded font-mono">Ctrl</kbd>+<kbd class="px-2 py-1 bg-slate-100 rounded font-mono">S</kbd></td><td class="px-4 py-2">${t('shortcuts.stats')}</td></tr>
                        <tr class="border-b border-slate-100"><td class="px-4 py-2"><kbd class="px-2 py-1 bg-slate-100 rounded font-mono">Ctrl</kbd>+<kbd class="px-2 py-1 bg-slate-100 rounded font-mono">E</kbd></td><td class="px-4 py-2">${t('shortcuts.inventorySwitch')}</td></tr>
                        <tr class="border-b border-slate-100"><td class="px-4 py-2"><kbd class="px-2 py-1 bg-slate-100 rounded font-mono">Escape</kbd></td><td class="px-4 py-2">${t('shortcuts.closeMenu')}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Prohlášení o přístupnosti -->
        <div data-section="accessibility" class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">♿ ${t('help.accessibilityTitle')}</h2>
            <p class="text-slate-600 mb-3">${t('help.accessibilityIntro')}</p>
            <ul class="list-disc list-inside space-y-1 text-slate-700 mb-3">
                <li>${t('help.accessibilityList1')}</li>
                <li>${t('help.accessibilityList2')}</li>
                <li>${t('help.accessibilityList3')}</li>
            </ul>
            <p class="text-slate-600 mb-2"><strong>${t('help.accessibilityContact')}</strong> <a href="mailto:podpora@sensio.cz" class="text-indigo-600 hover:underline">podpora@sensio.cz</a></p>
            <p class="text-slate-500 text-sm">${t('help.accessibilityRevised')}</p>
        </div>

        <!-- Správa účtu -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">⚙️ ${t('help.accountSectionTitle')}</h2>
            <p class="text-slate-600 mb-2">${t('help.accountSectionIntro')}</p>
            <ul class="space-y-2 ml-6 text-slate-700">
                <li>• ${t('help.accountSection1')}</li>
                <li>• ${t('help.accountSection2')}</li>
                <li>• ${t('help.accountSection3')}</li>
            </ul>
        </div>

        <!-- Tipy -->
        <div class="bg-indigo-50 p-6 rounded-3xl border border-indigo-200">
            <h2 class="text-xl font-black text-indigo-900 mb-3">💡 ${t('help.tipsTitle')}</h2>
            <ul class="space-y-2 text-indigo-900">
                <li>• ${t('help.tips1')}</li>
                <li>• ${t('help.tips2')}</li>
                <li>• ${t('help.tips3')}</li>
                <li>• ${t('help.tips4')}</li>
                <li>• ${t('help.tips5')}</li>
                <li>• ${t('help.tips6')}</li>
            </ul>
        </div>

        <!-- Podpora -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 text-center">
            <h2 class="text-xl font-black text-slate-800 mb-2">📧 ${t('help.supportTitle')}</h2>
            <p class="text-slate-600 mb-4">${t('help.supportContact')} <a href="mailto:podpora@sensio.cz" class="text-indigo-600 font-bold hover:underline">podpora@sensio.cz</a></p>
            <p class="text-sm text-slate-500">${t('help.supportDeveloped')} <a href="https://sensio.cz" target="_blank" class="text-indigo-600 hover:underline">Sensio.cz s.r.o.</a></p>
        </div>

        <button onclick="window.resetApp()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold shadow-sm">${t('help.backToStock')}</button>
    `;
    v.appendChild(container);
    container.querySelectorAll('[data-nav]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const nav = link.getAttribute('data-nav');
            if (!nav) return;
            const path = nav === 'wizard' ? '/wizard/mat' : '/' + nav;
            router.push(BASE_PATH + path);
        });
    });
}
