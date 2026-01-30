// Help view render function
export function renderHelp(v) {
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
