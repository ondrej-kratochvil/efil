# Build Instructions - Tailwind CSS

## Problém
Aplikace aktuálně používá Tailwind CSS přes CDN (`https://cdn.tailwindcss.com`), což není vhodné pro produkci.

## Řešení
Přesunout na lokální build proces pomocí Tailwind CLI.

## Instalace

1. **Nainstalujte Node.js** (pokud ještě není nainstalováno)
   - Stáhněte z: https://nodejs.org/

2. **Vytvořte `package.json`** v rootu projektu:
   ```json
   {
     "name": "efil",
     "version": "1.0.0",
     "scripts": {
       "build:css": "tailwindcss -i ./assets/css/tailwind-input.css -o ./assets/css/tailwind.css --minify",
       "watch:css": "tailwindcss -i ./assets/css/tailwind-input.css -o ./assets/css/tailwind.css --watch"
     },
     "devDependencies": {
       "tailwindcss": "^3.4.0"
     }
   }
   ```

3. **Nainstalujte závislosti:**
   ```bash
   npm install
   ```

## Build proces

### Vývoj (watch mode - automaticky rebuild při změnách)
```bash
npm run watch:css
```

### Produkce (minifikovaný build)
```bash
npm run build:css
```

## Aktualizace index.php

Po buildu nahraďte v `index.php`:
```html
<!-- PŘED (CDN - vývoj) -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- PO (lokální build - produkce) -->
<link rel="stylesheet" href="assets/css/tailwind.css">
```

## Poznámky

- `tailwind-input.css` obsahuje Tailwind direktivy (`@tailwind base/components/utilities`)
- `tailwind.css` je builovaný výstup (generovaný, commitovat do gitu)
- `tailwind.config.js` definuje, které soubory se mají scanovat pro třídy
- Pro produkci vždy spusťte `npm run build:css` před nasazením

## Alternativa: Standalone Tailwind CLI

Pokud nechcete používat npm, můžete použít standalone Tailwind CLI:
1. Stáhněte z: https://github.com/tailwindlabs/tailwindcss/releases
2. Použijte: `./tailwindcss -i ./assets/css/tailwind-input.css -o ./assets/css/tailwind.css --minify`
