// Router - History API support
import { BASE_PATH } from './config.js';
import { state, user } from './state.js';
// Note: render function will be imported from app.js for now (circular dependency issue)

export const router = {
    // Navigate to a new route
    push(path, stateData = {}) {
        window.history.pushState({ ...stateData, path }, '', path);
        this.handleRoute(path, stateData);
    },

    // Replace current route
    replace(path, stateData = {}) {
        window.history.replaceState({ ...stateData, path }, '', path);
        this.handleRoute(path, stateData);
    },

    // Handle route changes
    handleRoute(path, stateData = {}) {
        // Parse path and filter out empty segments
        const segments = path.split('/').filter(s => s);

        // Remove base path if present (e.g., 'a', 'efil-github')
        // We only care about routes that start with our app routes
        const appRoutes = ['wizard', 'form', 'consume', 'stats', 'help', 'account', 'users', 'spools', 'admin-stats', 'inventory-switch', 'forgot-password', 'reset-password'];
        let routeStartIndex = 0;
        for (let i = 0; i < segments.length; i++) {
            if (appRoutes.includes(segments[i])) {
                routeStartIndex = i;
                break;
            }
        }
        const appSegments = segments.slice(routeStartIndex);

        if (!appSegments.length) {
            // Root - show auth or wizard based on login state
            if (user) {
                state.view = 'wizard';
                state.currentStep = 1;
                state.filters = { mat: null, color: null };
            } else {
                state.view = 'auth';
                state.authView = 'login';
            }
        } else if (appSegments[0] === 'wizard') {
            state.view = 'wizard';
            if (appSegments[1] === 'mat') state.currentStep = 1;
            else if (appSegments[1] === 'bar') state.currentStep = 2;
            else if (appSegments[1] === 'vyr') state.currentStep = 3;
            else state.currentStep = 1;
        } else if (appSegments[0] === 'form') {
            state.view = 'form';
            state.editingId = appSegments[1] ? parseInt(appSegments[1]) : null;
        } else if (appSegments[0] === 'consume') {
            state.view = 'consume';
            state.consumeId = appSegments[1] ? parseInt(appSegments[1]) : null;
        } else if (appSegments[0] === 'stats') {
            state.view = 'stats';
        } else if (appSegments[0] === 'help') {
            state.view = 'help';
        } else if (appSegments[0] === 'account') {
            state.view = 'account';
        } else if (appSegments[0] === 'users') {
            state.view = 'users';
        } else if (appSegments[0] === 'spools') {
            state.view = 'spools';
        } else if (appSegments[0] === 'admin-stats') {
            state.view = 'adminStats';
        } else if (appSegments[0] === 'inventory-switch') {
            state.view = 'inventorySwitch';
        } else if (appSegments[0] === 'forgot-password') {
            state.view = 'auth';
            state.authView = 'forgotPassword';
        } else if (appSegments[0] === 'reset-password') {
            state.view = 'auth';
            state.authView = 'resetPassword';
            state.resetToken = new URLSearchParams(window.location.search).get('token');
        } else {
            // Unknown route - default to root
            if (user) {
                state.view = 'wizard';
                state.currentStep = 1;
                state.filters = { mat: null, color: null };
            } else {
                state.view = 'auth';
                state.authView = 'login';
            }
        }

        // Import render dynamically to avoid circular dependency
        import('./app.js').then(module => {
            if (module.render) {
                module.render();
            } else {
                // Fallback: trigger render via window
                if (window.render) window.render();
            }
        });
    },

    // Get current route path based on state
    getPath() {
        let path = '';
        if (state.view === 'auth') {
            if (state.authView === 'forgotPassword') path = '/forgot-password';
            else if (state.authView === 'resetPassword') path = '/reset-password';
            else path = '/';
        } else if (state.view === 'wizard') {
            if (state.currentStep === 1) path = '/wizard/mat';
            else if (state.currentStep === 2) path = '/wizard/bar';
            else if (state.currentStep === 3) path = '/wizard/vyr';
            else path = '/wizard/mat';
        } else if (state.view === 'form') {
            path = state.editingId ? `/form/${state.editingId}` : '/form';
        } else if (state.view === 'consume') {
            path = `/consume/${state.consumeId}`;
        } else if (state.view === 'stats') {
            path = '/stats';
        } else if (state.view === 'help') {
            path = '/help';
        } else if (state.view === 'account') {
            path = '/account';
        } else if (state.view === 'users') {
            path = '/users';
        } else if (state.view === 'spools') {
            path = '/spools';
        } else if (state.view === 'adminStats') {
            path = '/admin-stats';
        } else if (state.view === 'inventorySwitch') {
            path = '/inventory-switch';
        } else {
            path = '/';
        }
        return BASE_PATH + path;
    }
};

// Listen to browser back/forward buttons
window.addEventListener('popstate', (e) => {
    if (e.state && e.state.path) {
        router.handleRoute(e.state.path, e.state);
    } else {
        router.handleRoute(window.location.pathname);
    }
});
