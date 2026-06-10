import {Tokens, errorMessage} from '../constants';
import Cookies from 'js-cookie';

// Public paths that never need an auth token.
// The app uses HashRouter so the actual route is in window.location.hash
// (e.g. "http://noovapos.local/#/login") — window.location.pathname is always "/".
// We check BOTH pathname and hash to cover both routing modes.
const PUBLIC_PATHS = ['/login', '/forgot-password', '/reset-password', '/register-tenant', '/pricing'];
const isPublicPath = () => {
    const hash     = window.location.hash || '';       // e.g. "#/login"
    const pathname = window.location.pathname || '';   // e.g. "/login" (BrowserRouter)
    const check    = hash + pathname;
    return PUBLIC_PATHS.some(p => check.includes(p));
};

export default {
    setupInterceptors: (axios, addToken = false, isFormData = false) => {
        // ── Request interceptor ──────────────────────────────────────────────
        axios.interceptors.request.use(
            (config) => {
                const token = Cookies.get('authToken');

                if (addToken && token) {
                    // Authenticated instance — always attach Bearer token
                    config.headers['Authorization'] = `Bearer ${token}`;
                } else if (!addToken) {
                    // Unauthenticated instance (login, public routes) — no token needed
                    // Guard: if cookie is gone and we're on a protected page, redirect
                    if (!token && !isPublicPath()) {
                        window.location.href = '/login';
                        // Return a cancelled request so nothing fires after redirect
                        return Promise.reject(new Error('Redirecting to login'));
                    }
                }

                if (isFormData) {
                    config.headers['Content-Type'] = 'multipart/form-data';
                }

                return config;
            },
            (error) => Promise.reject(error)
        );

        // ── Response interceptor ─────────────────────────────────────────────
        axios.interceptors.response.use(
            (response) => response,
            (error) => {
                // Network error / timeout — no response object at all
                if (!error.response) {
                    return Promise.reject(error);
                }

                const status  = error.response.status;
                const message = error.response?.data?.message ?? '';

                // Session expired or invalid token
                const isAuthError =
                    status === 401 ||
                    message === errorMessage.TOKEN_NOT_PROVIDED ||
                    message === errorMessage.TOKEN_INVALID ||
                    message === errorMessage.TOKEN_INVALID_SIGNATURE ||
                    message === errorMessage.TOKEN_EXPIRED;

                if (isAuthError) {
                    localStorage.removeItem(Tokens.ADMIN);
                    localStorage.removeItem(Tokens.USER);
                    localStorage.removeItem(Tokens.GET_PERMISSIONS);
                    Cookies.remove('authToken');
                    if (!isPublicPath()) {
                        window.location.href = '/login';
                    }
                    // Still reject so callers can handle it (e.g. login page shows error)
                    return Promise.reject(error);
                }

                // Subscription/trial lock — hard lock everywhere except the
                // Billing page so the tenant can renew. The lock message comes
                // from EnsureTenantSubscriptionActive (mentions "subscription"
                // or "trial").
                const lc = (window.location.hash + window.location.pathname);
                const isSubscriptionLock =
                    status === 403 &&
                    /subscription|trial/i.test(message);
                if (isSubscriptionLock && !lc.includes('/app/billing')) {
                    window.location.href = '/app/billing';
                    return Promise.reject(error);
                }

                // 403 / 404 on protected pages — bounce to dashboard
                if ((status === 403 || status === 404) && !isPublicPath() && !lc.includes('/app/billing')) {
                    window.location.href = '/app/dashboard';
                    return Promise.reject(error);
                }

                // All other errors (422 validation, 429 throttle, 500, etc.)
                // — reject with the original error so action catch blocks can read
                //   error.response.data.message correctly
                return Promise.reject(error);
            }
        );
    }
};
