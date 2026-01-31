<?php
declare(strict_types=1);

$cssFile = __DIR__ . '/assets/css/main.css';
$jsFile  = __DIR__ . '/assets/js/app.js';

$cssVersion = is_file($cssFile) ? (string)filemtime($cssFile) : '1';
$jsVersion  = is_file($jsFile) ? (string)filemtime($jsFile) : '1';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filament Management Cloud</title>
    <link rel="icon" type="image/svg+xml" href="" id="favicon-link">
    <!-- Dynamicky nastavit base path a cesty k assetům - MUSÍ být před načtením CSS a JS -->
    <script>
        // Detekce base path z aktuální URL - musí být synchronní před načtením CSS/JS
        (function() {
            const path = window.location.pathname;
            let cleanPath = path.replace(/\/$/, '');
            if (cleanPath.endsWith('index.html') || cleanPath.endsWith('index.php')) {
                cleanPath = cleanPath.replace(/\/index\.(html|php)$/, '');
            }
            const segments = cleanPath.split('/').filter(s => s);
            const appRoutes = ['wizard', 'form', 'consume', 'stats', 'help', 'account', 'users', 'spools', 'manufacturers', 'admin-stats', 'inventory-switch', 'forgot-password', 'reset-password'];
            let routeIndex = segments.length;
            for (let i = 0; i < segments.length; i++) {
                if (appRoutes.includes(segments[i])) {
                    routeIndex = i;
                    break;
                }
            }
            let basePath = '';
            if (routeIndex > 0) {
                // App route je na pozici > 0, takže před ním je base path
                basePath = '/' + segments.slice(0, routeIndex).join('/');
            } else if (routeIndex === 0) {
                // App route je na pozici 0, aplikace běží v rootu
                basePath = '';
            } else if (segments.length > 0) {
                // Žádná app route nebyla nalezena, ale jsou segmenty - použij všechny jako base path
                // (např. pro /a/efil-github/ bez app route)
                basePath = '/' + segments.join('/');
            }

            // Vždy nastavit base tag, i když je basePath prázdný (pro root instalaci)
            const base = document.createElement('base');
            base.href = basePath ? basePath + '/' : '/';
            document.head.insertBefore(base, document.head.firstChild);

            // Uložit basePath do window pro pozdější použití
            window.__BASE_PATH__ = basePath || '';
        })();
    </script>
    <script>
        // Nastavit absolutní cestu k favicon pomocí BASE_PATH
        (function() {
            const faviconLink = document.getElementById('favicon-link');
            if (faviconLink) {
                const basePath = window.__BASE_PATH__ || '';
                const correctPath = basePath ? basePath + '/assets/img/favicon.svg' : '/assets/img/favicon.svg';
                faviconLink.href = correctPath;
            }
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="" id="main-css">
    <script>
        // Nastavit absolutní cestu k CSS pomocí BASE_PATH + cache-busting verzí
        (function() {
            const cssLink = document.getElementById('main-css');
            if (cssLink) {
                const basePath = window.__BASE_PATH__ || '';
                // Vždy použít absolutní cestu začínající /
                const correctPath = basePath ? basePath + '/assets/css/main.css?v=<?= htmlspecialchars($cssVersion, ENT_QUOTES) ?>' : '/assets/css/main.css?v=<?= htmlspecialchars($cssVersion, ENT_QUOTES) ?>';
                cssLink.href = correctPath;
            }
        })();
    </script>
    <script>
        // Light/Dark mode: init z localStorage, jinak prefers-color-scheme
        (function() {
            const stored = localStorage.getItem('efil-theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = stored === 'dark' || stored === 'light' ? stored : (prefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body class="text-slate-900">

    <!-- Header & Navigation -->
    <header class="sticky top-0 z-50 bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-5xl mx-auto flex items-center justify-between px-4 h-16">
            <div onclick="resetApp()" class="cursor-pointer text-indigo-600 flex items-center">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            </div>

            <nav id="wizard-nav" class="flex h-full hidden">
                <button onclick="setStep(1)" id="nav-mat" class="flex flex-col items-center justify-center px-3 h-full text-xs font-medium border-b-2 transition-all uppercase tracking-wider">
                    <span class="text-[13px]">MAT</span>
                </button>
                <button onclick="setStep(2)" id="nav-bar" class="flex flex-col items-center justify-center px-3 h-full text-xs font-medium border-b-2 transition-all uppercase tracking-wider">
                    <span class="text-[13px]">BAR</span>
                </button>
                <button onclick="setStep(3)" id="nav-vyr" class="flex flex-col items-center justify-center px-3 h-full text-xs font-medium border-b-2 transition-all uppercase tracking-wider">
                    <span class="text-[13px]">VÝR</span>
                </button>
            </nav>

            <div id="form-title" class="hidden font-bold text-slate-500 uppercase tracking-widest text-sm">Editor</div>

            <button onclick="toggleActionMenu()" id="menu-trigger" class="touch-target w-10 h-10 flex items-center justify-center text-slate-600 hover:bg-slate-100 rounded-full transition-colors hidden">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="1"/><circle cx="12" cy="5" r="1"/><circle cx="12" cy="19" r="1"/></svg>
            </button>
        </div>

        <div id="action-menu" class="absolute left-0 right-0 bg-white border-b border-slate-200 shadow-xl z-40 hidden max-h-[calc(100vh-4rem)] overflow-y-auto">
            <div class="max-w-5xl mx-auto p-4 space-y-2">
                <details class="group menu-expandable" data-menu-section="evidence">
                    <summary class="list-none flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target cursor-pointer text-slate-700">
                        <div class="bg-indigo-100 text-indigo-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                        <span class="flex-1">Evidence</span>
                        <span class="menu-chevron flex-shrink-0 text-slate-400" aria-hidden="true"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></span>
                    </summary>
                    <div class="pl-4 pb-2 space-y-1">
                        <button onclick="openForm()" class="w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left">
                            <div class="bg-indigo-600 text-white p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path></svg></div>
                            Přidat nový filament
                        </button>
                        <button onclick="openStats()" class="w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left">
                            <div class="bg-indigo-100 text-indigo-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20V10"></path><path d="M18 20V4"></path><path d="M6 20v-4"></path></svg></div>
                            Přehled skladu
                        </button>
                        <div id="menu-slot-inventory-switch"></div>
                    </div>
                </details>
                <details class="group menu-expandable" data-menu-section="settings">
                    <summary class="list-none flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target cursor-pointer text-slate-700">
                        <div class="bg-slate-100 text-slate-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></div>
                        <span class="flex-1">Nastavení</span>
                        <span class="menu-chevron flex-shrink-0 text-slate-400" aria-hidden="true"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg></span>
                    </summary>
                    <div class="pl-4 pb-2 space-y-1">
                        <button onclick="openAccount()" class="w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left">
                            <div class="bg-slate-100 text-slate-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                            Můj účet
                        </button>
                        <button onclick="openUsers()" class="w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left">
                            <div class="bg-purple-100 text-purple-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
                            Správa uživatelů
                        </button>
                        <button onclick="openSpools()" class="w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left">
                            <div class="bg-teal-100 text-teal-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg></div>
                            Správa typů cívek
                        </button>
                        <button onclick="openManufacturers()" class="w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left">
                            <div class="bg-sky-100 text-sky-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5m-4 0h4"></path></svg></div>
                            Správa výrobců
                        </button>
                        <button onclick="openHelp()" class="w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left">
                            <div class="bg-amber-100 text-amber-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></div>
                            Nápověda
                        </button>
                        <div id="menu-slot-admin-stats"></div>
                    </div>
                </details>
                <button type="button" onclick="window.toggleTheme()" class="w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left" aria-label="Přepnout světlý / tmavý režim">
                    <div class="bg-slate-100 text-slate-600 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg></div>
                    <span id="theme-toggle-label">Tmavý režim</span>
                </button>
                <button onclick="logout()" class="w-full flex items-center gap-4 p-4 hover:bg-slate-50 rounded-xl font-bold touch-target text-left text-red-500">
                    <div class="bg-red-100 text-red-500 p-2 rounded-lg"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div>
                    Odhlásit se
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto p-4 pb-24">
        <div id="loading-screen" class="py-20 text-center">
            <div class="inline-block w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
            <p class="mt-4 text-slate-400 font-medium">Načítání...</p>
        </div>
        <div id="app-view" class="hidden"></div>
    </main>

    <div id="toast" class="fixed bottom-8 left-1/2 -translate-x-1/2 bg-slate-900 text-white px-6 py-3 rounded-full text-sm font-bold shadow-2xl opacity-0 transition-opacity pointer-events-none z-[60]">Uloženo</div>

    <script type="module">
        // Nastavit absolutní cestu k JS pomocí BASE_PATH + cache-busting verzí
        (function() {
            const jsScript = document.createElement('script');
            jsScript.type = 'module';
            jsScript.id = 'app-js';
            const basePath = window.__BASE_PATH__ || '';
            // Vždy použít absolutní cestu začínající /
            const correctPath = basePath
                ? basePath + '/assets/js/app.js?v=<?= htmlspecialchars($jsVersion, ENT_QUOTES) ?>'
                : '/assets/js/app.js?v=<?= htmlspecialchars($jsVersion, ENT_QUOTES) ?>';
            jsScript.src = correctPath;
            document.body.appendChild(jsScript);
        })();
    </script>

    <!-- Footer -->
    <footer class="footer-theme text-center py-8 text-slate-500 border-t border-slate-200 mt-12">
        <p>© <?= (int) date('Y') ?> <a href="https://sensio.cz" target="_blank" rel="noopener noreferrer" class="text-slate-600 hover:text-indigo-600 transition-colors">Sensio.cz s.r.o.</a></p>
        <p class="mt-1 text-sm"><a href="#" id="footer-accessibility-link" class="text-slate-500 hover:text-indigo-600 transition-colors">Prohlášení o přístupnosti</a></p>
    </footer>
</body>
</html>

