// State management

export let filaments = [];
export let options = { materials: [], manufacturers: [], locations: [], sellers: [] };
export let spoolTemplates = [];
export let stats = null;
export let user = null;

export const state = {
    view: 'loading', // loading, auth, wizard, form, consume, stats, help, account, users, spools, adminStats, inventorySwitch
    authView: 'login', // login, register, forgotPassword, resetPassword
    currentStep: 1,
    filters: { mat: null, color: null },
    editingId: null,
    consumeId: null,
    consumeMode: 'used', // used (subtract), weight (calculate from gross)
    formFieldsStatus: { mat: 'select', man: 'select', loc: 'select', seller: 'select', spool: 'select' },
    expandedGroups: new Set(), // Track which filament groups are expanded
    lastUpdatedFilamentId: null, // For temporary highlight after save/consume
    formPreset: null // { mat?, color?, hex? } when opening form from wizard "+"
};

// State setters
export function setFilaments(data) {
    filaments = data;
}

export function setOptions(data) {
    options = data;
}

export function setSpoolTemplates(data) {
    spoolTemplates = data;
}

export function setStats(data) {
    stats = data;
}

export function setUser(data) {
    user = data;
}
