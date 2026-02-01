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
            <p class="text-slate-600 mt-4 mb-2 font-bold">Cena a průměr za kg</p>
            <p class="text-slate-600 text-sm">Na kartách MAT, BAR a VÝR se zobrazuje <strong>průměrná cena za kilogram</strong> (Kč/kg). Do výpočtu se započítávají jen filamenty, u kterých máte vyplněnou cenu; používá se původní hmotnost cívky. U skupin se zobrazuje symbol <strong>x̄</strong> (průměr), u jednotlivého filamentu jen hodnota v Kč/kg.</p>
            <p class="text-slate-600 mt-4 mb-2 font-bold">Přidání filamentu z wizardu</p>
            <p class="text-slate-600 text-sm">Na každé obrazovce (MAT, BAR, VÝR) je za kartami položka <strong>+</strong>. Kliknutím otevřete formulář pro nový filament:</p>
            <ul class="list-disc list-inside space-y-1 text-slate-700 text-sm mt-2 ml-2">
                <li><strong>MAT</strong> – nic se nepředvyplní</li>
                <li><strong>BAR</strong> – předvyplní se materiál (pokud jste ho vyfiltrovali)</li>
                <li><strong>VÝR</strong> – předvyplní se materiál i barva podle aktuálních filtrů</li>
            </ul>
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

        <!-- Statistiky -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">📊 Statistiky evidence</h2>
            <p class="text-slate-600 mb-3">V menu zvolte <strong>Statistiky</strong>. Zobrazí se přehled vaší <strong>aktivně vybrané evidence</strong> (při více evidencích záleží na té, kterou máte právě zvolenou).</p>
            <ul class="list-disc list-inside space-y-1 text-slate-700 mb-3">
                <li><strong>Celkem na skladě</strong> – součet zbývající hmotnosti všech filamentů (g)</li>
                <li><strong>Odhad hodnoty</strong> – odhad v Kč podle ceny a zbývající hmotnosti</li>
                <li><strong>Počet cívek</strong> – počet filamentů v evidenci</li>
                <li><strong>Spotřeba (30 dní)</strong> – celková spotřeba za posledních 30 dní (kg)</li>
            </ul>
            <p class="text-slate-600 mb-2 font-bold">Rozložení materiálů</p>
            <p class="text-slate-600 text-sm mb-3">Koláčový graf zobrazuje podíl zbývající hmotnosti podle materiálu (PLA, PETG, atd.).</p>
            <p class="text-slate-600 mb-2 font-bold">Historie čerpání</p>
            <p class="text-slate-600 text-sm">Sloupcový graf ukazuje spotřebu po dnech (posledních 30 dní). Pod ním je tabulka jednotlivých záznamů čerpání s možností úpravy nebo smazání.</p>
        </div>

        <!-- Výrobci -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">🏭 Výrobci filamentů</h2>
            <p class="text-slate-600 mb-3">Při přidávání nebo úpravě filamentu můžete výrobce vybrat ze seznamu nebo zadat nového.</p>
            <ol class="list-decimal list-inside space-y-2 text-slate-700">
                <li><strong>Výběr ze seznamu</strong> – v poli Výrobce zvolte existujícího výrobce (řazení: nejčastější v evidenci, ostatní abecedně)</li>
                <li><strong>Nový výrobce</strong> – klikněte na <strong>+</strong> vedle pole a zadejte název; nový výrobce se vytvoří jako váš soukromý a bude dostupný v dalších formulářích</li>
                <li>U <strong>typů cívek</strong> můžete přiřadit více výrobců k jednomu typu (multiselect)</li>
            </ol>
            <p class="text-slate-500 text-sm mt-3">💡 Veřejní výrobci jsou sdílení; vlastní výrobce vidíte jen vy. Smazat lze pouze výrobce, který není použit u žádného filamentu ani typu cívky.</p>
        </div>

        <!-- Správa cívek -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">🎯 Typy cívek (Tára)</h2>
            <ol class="list-decimal list-inside space-y-2 text-slate-700">
                <li>Při přidávání filamentu vyberte typ cívky ze seznamu</li>
                <li>Pokud váš typ není v seznamu, klikněte na <strong>+</strong> – rozbalí se pole pro nový typ, ostatní údaje formuláře (materiál, barva, výrobce…) zůstanou</li>
                <li>Barva a materiál typu cívky jsou <strong>povinné</strong>; při rozbalení se předvyplní z už zadané barvy a materiálu filamentu</li>
                <li>Zadejte další charakteristiky (průměr, šířka). <strong>Hmotnost</strong> prázdné cívky zadejte až když ji máte prázdnou</li>
                <li>Při uložení filamentu se výrobce filamentu automaticky propisuje i do nového typu cívky</li>
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

        <!-- Klávesové zkratky -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">⌨️ Klávesové zkratky</h2>
            <p class="text-slate-600 mb-3">Nejpoužívanější operace lze spustit z klávesnice:</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border border-slate-200 rounded-xl overflow-hidden">
                    <thead class="bg-slate-50 text-slate-700 font-bold">
                        <tr><th class="px-4 py-2 border-b border-slate-200">Zkratka</th><th class="px-4 py-2 border-b border-slate-200">Akce</th></tr>
                    </thead>
                    <tbody class="text-slate-700">
                        <tr class="border-b border-slate-100"><td class="px-4 py-2"><kbd class="px-2 py-1 bg-slate-100 rounded font-mono">F1</kbd></td><td class="px-4 py-2">Otevřít nápovědu</td></tr>
                        <tr class="border-b border-slate-100"><td class="px-4 py-2"><kbd class="px-2 py-1 bg-slate-100 rounded font-mono">Ctrl</kbd>+<kbd class="px-2 py-1 bg-slate-100 rounded font-mono">N</kbd></td><td class="px-4 py-2">Přidat nový filament</td></tr>
                        <tr class="border-b border-slate-100"><td class="px-4 py-2"><kbd class="px-2 py-1 bg-slate-100 rounded font-mono">Ctrl</kbd>+<kbd class="px-2 py-1 bg-slate-100 rounded font-mono">S</kbd></td><td class="px-4 py-2">Přehled skladu</td></tr>
                        <tr class="border-b border-slate-100"><td class="px-4 py-2"><kbd class="px-2 py-1 bg-slate-100 rounded font-mono">Escape</kbd></td><td class="px-4 py-2">Zavřít menu nebo dialog</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Prohlášení o přístupnosti -->
        <div data-section="accessibility" class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
            <h2 class="text-xl font-black text-indigo-600 mb-3">♿ Prohlášení o přístupnosti</h2>
            <p class="text-slate-600 mb-3">Aplikace eFil usiluje o přístupnost v souladu s požadavky na přístupnost webového obsahu (WCAG 2.1) na úrovni AA v rozsahu, který umožňuje použité technologie.</p>
            <ul class="list-disc list-inside space-y-1 text-slate-700 mb-3">
                <li>Používáme sémantické HTML, popisky formulářů a vhodné kontrasty.</li>
                <li>Klávesové zkratky jsou uvedeny v sekci Klávesové zkratky výše a v tomto prohlášení.</li>
                <li>Responzivní rozvržení a podpora zoomu v prohlížeči.</li>
            </ul>
            <p class="text-slate-600 mb-2"><strong>Kontakt pro připomínky k přístupnosti:</strong> <a href="mailto:podpora@sensio.cz" class="text-indigo-600 hover:underline">podpora@sensio.cz</a></p>
            <p class="text-slate-500 text-sm">Poslední revize prohlášení: leden 2026.</p>
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
                <li>• Nový filament můžete přidat z kterékoliv obrazovky wizardu pomocí <strong>+</strong> (materiál a barva se předvyplní podle filtrů)</li>
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
