/**
 * i18n – překlady a výběr jazyka (cs/en).
 * Překlady v assets/i18n/{lang}.json, volba v localStorage (efil-lang).
 */

const STORAGE_KEY = 'efil-lang';
const DEFAULT_LANG = 'cs';

let translations = {};
let currentLang = DEFAULT_LANG;

function getI18nBase() {
    return (typeof window !== 'undefined' && window.__BASE_PATH__) ? window.__BASE_PATH__ : '';
}

/**
 * Načte překlady pro daný jazyk.
 * @param {string} lang
 * @returns {Promise<void>}
 */
export async function loadLang(lang) {
    const base = getI18nBase();
    const url = base ? `${base}/assets/i18n/${lang}.json` : `/assets/i18n/${lang}.json`;
    try {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`i18n ${lang} failed: ${res.status}`);
        translations = await res.json();
        currentLang = lang;
        if (typeof localStorage !== 'undefined') localStorage.setItem(STORAGE_KEY, lang);
    } catch (e) {
        if (lang !== DEFAULT_LANG) return loadLang(DEFAULT_LANG);
        translations = {};
    }
}

/**
 * Vrátí překlad pro klíč (např. "auth.login"). Podpora interpolace {{var}}.
 * @param {string} key
 * @param {Record<string, string|number>} [vars]
 * @returns {string}
 */
export function t(key, vars = {}) {
    const keys = key.split('.');
    let value = translations;
    for (const k of keys) {
        value = value?.[k];
    }
    let str = typeof value === 'string' ? value : key;
    for (const [k, v] of Object.entries(vars)) {
        str = str.replace(new RegExp(`\\{\\{${k}\\}\\}`, 'g'), String(v));
    }
    return str;
}

/**
 * Aktuální jazyk (cs / en).
 */
export function getCurrentLang() {
    return currentLang;
}

/**
 * Jednotka měny podle aktuálního jazyka (cs: Kč, en: CZK). Bez konverze, jen překlad labelu.
 * @returns {string}
 */
export function getCurrencyUnit() {
    return t('common.currencyUnit');
}

/**
 * Jednotka ceny za kg podle jazyka (Kč/kg / CZK/kg).
 * @returns {string}
 */
export function getCurrencyPerKg() {
    return t('common.currencyPerKg');
}

/**
 * Přepne jazyk, načte překlady, aktualizuje DOM a případně překreslí view.
 * @param {string} lang
 */
export async function setLang(lang) {
    await loadLang(lang);
    applyTranslations();
    refreshLangSwitcher();
    if (typeof window !== 'undefined' && window.render) await window.render();
    refreshLangSwitcher();
    if (typeof window !== 'undefined' && window.updateThemeToggleLabel) window.updateThemeToggleLabel();
    if (typeof window !== 'undefined' && window.updateHeaderFromLang) window.updateHeaderFromLang();
}

function refreshLangSwitcher() {
    if (typeof document === 'undefined') return;
    const themeLabel = document.getElementById('theme-toggle-label');
    if (themeLabel) {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const value = isDark ? t('nav.themeLight') : t('nav.themeDark');
        themeLabel.textContent = value;
    }
    const langCs = document.getElementById('lang-cs');
    const langEn = document.getElementById('lang-en');
    if (langCs) { langCs.setAttribute('aria-pressed', currentLang === 'cs' ? 'true' : 'false'); langCs.classList.toggle('bg-indigo-100', currentLang === 'cs'); langCs.classList.toggle('text-indigo-700', currentLang === 'cs'); langCs.classList.toggle('text-slate-500', currentLang !== 'cs'); }
    if (langEn) { langEn.setAttribute('aria-pressed', currentLang === 'en' ? 'true' : 'false'); langEn.classList.toggle('bg-indigo-100', currentLang === 'en'); langEn.classList.toggle('text-indigo-700', currentLang === 'en'); langEn.classList.toggle('text-slate-500', currentLang !== 'en'); }
}

/**
 * Inicializace i18n – načte uložený nebo výchozí jazyk. Volat před první render.
 * @returns {Promise<void>}
 */
export async function init() {
    const saved = (typeof localStorage !== 'undefined' && localStorage.getItem(STORAGE_KEY)) || DEFAULT_LANG;
    await loadLang(saved === 'en' ? 'en' : 'cs');
    refreshLangSwitcher();
}

/**
 * Projde DOM a nastaví texty u prvků s data-i18n, data-i18n-html, data-i18n-aria-label.
 */
export function applyTranslations() {
    if (typeof document === 'undefined') return;
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (key) el.textContent = t(key);
    });
    document.querySelectorAll('[data-i18n-html]').forEach(el => {
        const key = el.getAttribute('data-i18n-html');
        if (key) el.innerHTML = t(key);
    });
    document.querySelectorAll('[data-i18n-aria-label]').forEach(el => {
        const key = el.getAttribute('data-i18n-aria-label');
        if (key) el.setAttribute('aria-label', t(key));
    });
    const themeLabel = document.getElementById('theme-toggle-label');
    if (themeLabel) {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const value = isDark ? t('nav.themeLight') : t('nav.themeDark');
        themeLabel.textContent = value;
    }
}
