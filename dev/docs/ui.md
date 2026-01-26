# UI/UX Dokumentace eFil

## 🎨 Design systém

### Barvy

- **Primární**: Indigo (`#4f46e5`) - hlavní akce, odkazy
- **Sekundární**: Purple (`#9333ea`) - gradienty, zvýraznění
- **Pozadí**: Bílé a světle šedé (`#f8fafc`, `#f1f5f9`)
- **Text**: Tmavě šedý (`#0f172a`, `#1e293b`)
- **Barvy filamentů**: Dynamické podle `color_hex` z databáze

### Typografie

- **Font**: System font stack (sans-serif)
- **Nadpisy**: Font-weight bold/black, uppercase s tracking
- **Text**: Font-weight normal/medium
- **Velikosti**: Relativní jednotky (rem, em)

### Komponenty

#### Karty (Cards)
- Bílé pozadí s jemným stínem
- Zaoblené rohy (`rounded-2xl`, `rounded-3xl`)
- Hover efekty (stín, barva pozadí)

#### Tlačítka
- Primární: Indigo pozadí, bílý text
- Sekundární: Bílé pozadí, indigo text
- Hover: Změna barvy pozadí, transition efekty

#### Inputy
- Bílé pozadí, šedý border
- Focus: Indigo border
- Velikost: `font-size: 16px` (prevence zoom na mobilu)

---

## 🧭 Navigace

### Třístupňová navigace (Wizard)

Aplikace používá wizard pro procházení filamentů:

1. **MAT (Materiál)** - `/wizard/mat`
   - Zobrazení všech materiálů s celkovou hmotností
   - Kliknutí → přechod na BAR nebo VÝR

2. **BAR (Barva)** - `/wizard/bar`
   - Zobrazení barev s celkovou hmotností
   - Barevné karty s kontrastním textem
   - Kliknutí → přechod na MAT nebo VÝR

3. **VÝR (Výrobce/Detail)** - `/wizard/vyr`
   - Detailní seznam filamentů
   - Groupování podle výrobce+materiál+barva
   - Možnost rozbalení skupin

### Header navigace

- **Logo** (levý horní roh) - Reset aplikace na wizard/mat
- **Wizard navigace** (střed) - MAT | BAR | VÝR tlačítka (zobrazí se pouze v wizard view)
- **Menu tlačítko** (pravý horní roh) - Otevření akčního menu

### Akční menu

Rozbalovací menu s hlavními akcemi:
- Přidat nový filament
- Přehled skladu
- Správa účtu
- Správa uživatelů
- Knihovna cívek
- Přepínání evidencí (pokud má uživatel více evidencí)
- Admin statistiky (pouze pro admin_efil)
- Nápověda
- Odhlásit se

### History API routování

Aplikace podporuje tlačítka Zpět/Vpřed v prohlížeči:
- `pushState()` při navigaci
- `popstate` event listener pro zpětnou navigaci
- URL se mění bez reloadu stránky

---

## 📱 Responzivní design

### Breakpointy

- **Mobile**: < 640px (default)
- **Tablet**: 768px+
- **Desktop**: 1024px+

### Mobile optimalizace

- **Touch targets**: Minimálně 44x44px (`touch-target` třída)
- **Hamburger menu**: V pravém horním rohu
- **Full-width tlačítka**: Na mobilu zabírají celou šířku
- **Card grid**: 2 sloupce na mobilu, více na desktopu

### Desktop vylepšení

- **Grid layout**: Více sloupců pro karty
- **Side-by-side**: Login stránka má intro a formulář vedle sebe
- **Hover efekty**: Více interaktivních hover stavů

---

## 🎯 Komponenty UI

### Wizard karty (MAT/BAR)

```html
<div class="aspect-square rounded-2xl p-3 flex items-center justify-center text-center shadow-sm relative cursor-pointer">
    <div class="text-[10px] font-bold text-slate-400 absolute top-2 right-2">
        {hmotnost}
    </div>
    <div class="text-base font-black uppercase tracking-tight">
        {název}
    </div>
</div>
```

- **Aspect ratio**: 1:1 (čtvercové karty)
- **Hover**: Stín se zvětší
- **Kliknutí**: Navigace na další krok

### Detailní karty (VÝR)

#### Jednotlivý filament
```html
<div class="bg-white p-4 rounded-2xl border border-slate-200 flex items-center justify-between shadow-sm cursor-pointer">
    <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-full border border-slate-100 shadow-inner" style="background-color: {hex}"></div>
        <div>
            <div class="font-bold text-slate-900">{výrobce}</div>
            <div class="text-xs text-slate-500 font-medium uppercase">{materiál} • {barva}</div>
            <div class="text-[10px] text-indigo-500 font-bold uppercase">{umístění} | #{číslo}</div>
        </div>
    </div>
    <div class="text-2xl font-black text-indigo-600">{hmotnost}g</div>
</div>
```

#### Skupina filamentů
```html
<div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-4 rounded-2xl border-2 border-indigo-200 flex items-center justify-between shadow-sm cursor-pointer">
    <div class="flex items-center gap-4">
        <div class="w-10 h-10 rounded-full border-2 border-indigo-300 shadow-inner" style="background-color: {hex}"></div>
        <div>
            <div class="font-bold text-slate-900">{výrobce}</div>
            <div class="text-xs text-slate-500 font-medium uppercase">{materiál} • {barva}</div>
            <div class="text-[10px] text-indigo-600 font-bold uppercase">{počet} cívek</div>
        </div>
    </div>
    <div class="text-2xl font-black text-indigo-600">{celková_hmotnost}g</div>
</div>
```

### Formuláře

#### Select/Input módy

Formuláře podporují přepínání mezi:
- **Select mode**: Rozbalovací seznam s optgroups
- **Input mode**: Textové pole pro vlastní hodnotu

Tlačítko "+" vedle selectu přepne do input módu.

#### Persistence hodnot

Hodnoty formuláře se ukládají při přepínání mezi módy:
- `state.formValues` - Uložené hodnoty
- `restoreFormValues()` - Obnovení při otevření formuláře

### Toast notifikace

Jednoduché toast zprávy v pravém dolním rohu:
- Zelená: Úspěch
- Červená: Chyba
- Modrá: Informace

```javascript
showToast('Zpráva', 'success|error|info');
```

---

## 🎨 Barevné schéma

### Kontrastní barvy

Pro zobrazení textu na barevném pozadí (karty barev) se počítá kontrast:

```javascript
function getContrast(hex) {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(7, 9), 16);
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
    return brightness > 128 ? '#000000' : '#ffffff';
}
```

### Gradienty

- **Skupiny filamentů**: `from-indigo-50 to-purple-50`
- **Hover stavy**: Jemný přechod barvy

---

## 📐 Layout

### Max-width kontejnery

- **Hlavní obsah**: `max-w-5xl mx-auto`
- **Padding**: `px-4` (mobile), více na desktopu

### Grid systémy

#### Card grid (MAT/BAR)
```css
.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
}
```

#### Flexbox layout
- **Header**: `flex items-center justify-between`
- **Karty**: `flex items-center justify-between`
- **Formuláře**: `flex flex-col gap-4`

---

## 🔄 Interakce

### Hover efekty

- **Karty**: `hover:shadow-md`, `hover:bg-indigo-100`
- **Tlačítka**: `hover:bg-indigo-200`
- **Transition**: `transition-colors`, `transition-shadow`

### Kliknutí

- **Karty**: Navigace nebo otevření detailu
- **Hmotnost na kartě**: Otevření formuláře pro čerpání
- **Stop propagation**: Kliknutí na hmotnost neotevře detail

### Loading stavy

- **Loading view**: Zobrazení při načítání dat
- **Skeleton screens**: (není implementováno, ale možné přidat)

---

## 🎭 Stavy aplikace

### View stavy

```javascript
state.view = 
    'loading' |           // Načítání
    'auth' |              // Autentizace
    'wizard' |           // Wizard navigace
    'form' |             // Formulář
    'consume' |          // Čerpání
    'stats' |            // Statistiky
    'help' |             // Nápověda
    'account' |          // Správa účtu
    'users' |            // Správa uživatelů
    'spools' |           // Knihovna cívek
    'adminStats' |       // Admin statistiky
    'inventorySwitch'    // Přepínání evidencí
```

### Auth sub-stavy

```javascript
state.authView = 
    'login' |            // Přihlášení
    'register' |         // Registrace
    'forgotPassword' |  // Zapomenuté heslo
    'resetPassword'     // Reset hesla
```

---

## 🎨 Speciální funkce UI

### Režim vážení

Formulář pro čerpání podporuje dva režimy:

1. **Bez cívky** (`consumeMode = 'used'`):
   - Zadání čisté hmotnosti
   - Zobrazení: "Zadejte spotřebovanou hmotnost"

2. **S cívkou** (`consumeMode = 'weight'`):
   - Zadání celkové hmotnosti (brutto)
   - Automatický výpočet netto = brutto - tára
   - Zobrazení: "Tára cívky: {tára}g - bude odečtena automaticky"

### Skrytí prázdných filamentů

Filamenty s nulovou nebo zápornou hmotností se automaticky skrývají:
```javascript
const activeFilaments = filaments.filter(i => parseInt(i.g) > 0);
```

### Rozbalení skupin

- Kliknutí na skupinu → rozbalení
- Tlačítko "Sbalit skupinu" → sbalení
- Stav se ukládá v `state.expandedGroups` (Set)

---

## 📱 Mobilní optimalizace

### Touch targets

Všechny interaktivní prvky mají minimálně 44x44px:
```html
<button class="touch-target w-10 h-10">...</button>
```

### Hamburger menu

- Ikona: Tři tečky (vertikálně)
- Pozice: Pravý horní roh
- Animace: Slide down menu

### Formuláře na mobilu

- Full-width inputy
- Velké tlačítka
- `font-size: 16px` pro inputy (prevence zoom)

---

## 🎯 Accessibility (a11y)

### Sémantické HTML

- `<header>`, `<nav>`, `<main>`
- Správné použití `<button>` vs `<div>`
- ARIA labely (částečně implementováno)

### Keyboard navigation

- Tab order
- Enter/Space pro aktivaci
- Escape pro zavření menu (částečně)

### Screen readers

- Textové alternativy pro ikony
- Popisné labely pro inputy
- (Možnost vylepšení: více ARIA atributů)

---

## 🎨 Dark mode

Aktuálně není implementován, ale je připraveno:
- CSS proměnné pro barvy
- `prefers-color-scheme` media query (není použito)
- Možnost přidat toggle v menu

---

## 📐 Spacing systém

Používá Tailwind spacing scale:
- `gap-2`, `gap-3`, `gap-4` - Mezery mezi prvky
- `p-3`, `p-4` - Padding
- `mb-1`, `mt-2` - Margin

---

## 🎭 Animace a transitions

### CSS transitions

```css
transition-colors      /* Barvy */
transition-shadow     /* Stíny */
transition-all        /* Vše */
```

### Doba trvání

- Default: 150ms
- Hover: Okamžitý feedback

---

## 🔍 UX Patterns

### Progressive disclosure

- Skupiny filamentů se zobrazují sbalené
- Rozbalení na požádání
- Detailní informace v modalu/formuláři

### Feedback

- Toast notifikace pro akce
- Loading stavy
- Hover efekty pro interaktivní prvky

### Error handling

- Validace formulářů
- Chybové zprávy v češtině
- Graceful degradation
