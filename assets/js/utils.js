// Utility functions

/**
 * Get base path (e.g., '/a/efil-github' or '')
 * This detects the base path by looking at the current pathname
 * Should match the logic in index.html
 */
export function getBasePath() {
    // First, try to use BASE_PATH from index.html if available
    if (window.__BASE_PATH__ !== undefined) {
        return window.__BASE_PATH__;
    }

    const path = window.location.pathname;
    // Remove trailing slash
    let cleanPath = path.replace(/\/$/, '');
    // Remove index.html if present
    if (cleanPath.endsWith('index.html')) {
        cleanPath = cleanPath.replace(/\/index\.html$/, '');
    }
    // If path contains known app routes, extract base path
    const segments = cleanPath.split('/').filter(s => s);
    const appRoutes = ['wizard', 'form', 'consume', 'stats', 'help', 'account', 'users', 'spools', 'manufacturers', 'admin-stats', 'inventory-switch', 'forgot-password', 'reset-password'];

    // Find first app route index
    let routeIndex = segments.length;
    for (let i = 0; i < segments.length; i++) {
        if (appRoutes.includes(segments[i])) {
            routeIndex = i;
            break;
        }
    }

    let basePath = '';
    if (routeIndex > 0) {
        basePath = '/' + segments.slice(0, routeIndex).join('/');
    } else if (routeIndex === 0) {
        basePath = '';
    } else if (segments.length > 0) {
        basePath = '/' + segments.join('/');
    }

    return basePath;
}

/**
 * Show toast notification
 */
export function showToast(msg, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 ${colors[type] || colors.info} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

/**
 * Get closest color name from hex
 */
export function getClosestColorName(hex) {
    const colorNames = [
        'Černá', 'Bílá', 'Šedá', 'Červená', 'Modrá', 'Zelená', 'Žlutá', 'Oranžová', 'Fialová', 'Růžová',
        'Hnědá', 'Tmavě modrá', 'Světle modrá', 'Tmavě zelená', 'Světle zelená', 'Tmavě červená', 'Světle červená',
        'Tmavě šedá', 'Světle šedá', 'Khaki', 'Béžová', 'Korálová', 'Limetková', 'Mátová', 'Tyrkysová',
        'Azurová', 'Lavendrová', 'Lila', 'Fuchsiová', 'Lososová', 'Broskvová', 'Zlatá', 'Stříbrná',
        'Bronzová', 'Měděná', 'Průhledná / Čirá', 'Barevná', 'Duha', 'Mramorovaná', 'Metalická', 'Perleťová'
    ];
    
    const colorPalette = [
        ['#000000', 'Černá'], ['#FFFFFF', 'Bílá'], ['#808080', 'Šedá'], ['#FF0000', 'Červená'],
        ['#0000FF', 'Modrá'], ['#008000', 'Zelená'], ['#FFFF00', 'Žlutá'], ['#FFA500', 'Oranžová'],
        ['#800080', 'Fialová'], ['#FFC0CB', 'Růžová'], ['#A52A2A', 'Hnědá'], ['#00008B', 'Tmavě modrá'],
        ['#ADD8E6', 'Světle modrá'], ['#006400', 'Tmavě zelená'], ['#90EE90', 'Světle zelená'],
        ['#8B0000', 'Tmavě červená'], ['#FFB6C1', 'Světle červená'], ['#696969', 'Tmavě šedá'],
        ['#D3D3D3', 'Světle šedá'], ['#F0E68C', 'Khaki'], ['#F5F5DC', 'Béžová'], ['#FF7F50', 'Korálová'],
        ['#32CD32', 'Limetková'], ['#98FB98', 'Mátová'], ['#40E0D0', 'Tyrkysová'], ['#F0FFFF', 'Azurová'],
        ['#E6E6FA', 'Lavendrová'], ['#C8A2C8', 'Lila'], ['#FF00FF', 'Fuchsiová'], ['#FA8072', 'Lososová'],
        ['#FFDAB9', 'Broskvová'], ['#FFD700', 'Zlatá'], ['#C0C0C0', 'Stříbrná'], ['#CD7F32', 'Bronzová'],
        ['#B87333', 'Měděná'], ['#E8E8E8', 'Průhledná / Čirá']
    ];
    
    if (!hex) return 'Neznámá';
    
    const hexUpper = hex.toUpperCase();
    const exact = colorPalette.find(([h]) => h === hexUpper);
    if (exact) return exact[1];
    
    // Convert hex to RGB
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    
    // Find closest color
    let minDist = Infinity;
    let closest = 'Neznámá';
    for (const [h, name] of colorPalette) {
        const hr = parseInt(h.slice(1, 3), 16);
        const hg = parseInt(h.slice(3, 5), 16);
        const hb = parseInt(h.slice(5, 7), 16);
        const dist = Math.sqrt((r - hr) ** 2 + (g - hg) ** 2 + (b - hb) ** 2);
        if (dist < minDist) {
            minDist = dist;
            closest = name;
        }
    }
    return closest;
}

/**
 * Format weight in kg
 */
export function formatKg(grams) {
    if (grams >= 1000) {
        return (grams / 1000).toFixed(1).replace('.', ',') + ' kg';
    }
    return grams + 'g';
}

/**
 * Průměrná cena za kg (Kč/kg) z pole filamentů. Započítávají se jen položky s vyplněnou cenou (price > 0).
 * Vrací zaokrouhlené číslo nebo null, pokud nelze spočítat.
 * @param {Array<{price?: *, initial_weight_grams?: *}>} items
 * @returns {number|null}
 */
export function getAvgCzkPerKg(items) {
    if (!Array.isArray(items) || items.length === 0) return null;
    const withPrice = items.filter(i => (parseFloat(i.price) || 0) > 0);
    if (withPrice.length === 0) return null;
    const totalPrice = withPrice.reduce((s, i) => s + (parseFloat(i.price) || 0), 0);
    const totalInitialG = withPrice.reduce((s, i) => s + (parseInt(i.initial_weight_grams) || 0), 0);
    if (totalInitialG <= 0) return null;
    return Math.round(totalPrice / (totalInitialG / 1000));
}

/**
 * Get contrast color (black or white) for background
 */
export function getContrast(hex) {
    if (!hex || hex.length !== 7) return '#000000';
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
    return brightness > 128 ? '#000000' : '#ffffff';
}
