import apiConfig from "../../config/apiConfig";
import { authActionType, Tokens, toastType, apiBaseURL } from "../../constants";
import { fetchPermissions } from "./permissionAction";
import { addToast } from "./toastAction";
import { fetchFrontSetting } from "./frontSettingAction";
import { setLanguage } from "./changeLanguageAction";
import { getFormattedMessage } from "../../shared/sharedMethod";
import { fetchLanguageData } from '../../store/action/updateLanguageAction';
import { fetchConfig } from "./configAction";
import Cookies from 'js-cookie';

const permissionMappings = {
    // Platform-level (superadmin) — checked first in redirect logic
    manage_platform: "/app/platform/dashboard",
    manage_tenants:  "/app/platform/tenants",
    // Tenant-level
    manage_dashboard: "/app/dashboard",
    manage_roles: "/app/roles",
    manage_brands: "/app/brands",
    manage_warehouses: "/app/warehouses",
    manage_units: "/app/units",
    manage_product_categories: "/app/product-categories",
    manage_products: "/app/products",
    manage_suppliers: "/app/suppliers",
    manage_customers: "/app/customers",
    manage_users: "/app/users",
    manage_purchase: "/app/purchases",
    manage_pos_screen: "/app/pos",
    manage_sale: "/app/sales",
    manage_print_barcode: "/app/print/barcode",
    manage_adjustments: "/app/adjustments",
    manage_quotations: "/app/quotations",
    manage_transfers: "/app/transfers",
    manage_expenses: "/app/expenses",
    manage_currency: "/app/currencies",
    manage_variations: "/app/variations",
    manage_expense_categories: "/app/expense-categories",
    manage_setting: "/app/settings",
    manage_purchase_return: "/app/purchase-return",
    manage_sale_return: "/app/sale-return",
    manage_report: "/app/report/report-warehouse",
    manage_language: "/app/languages",
};

const mapPermissionToRoute = (permission) => {
    const permissionKey = permission.toLowerCase();
    if (permissionMappings.hasOwnProperty(permissionKey)) {
        return permissionMappings[permissionKey];
    } else {
        const entity = permissionKey.split("_").slice(1).join("-");
        return `/app/${entity}`;
    }
};

export const loginAction = (user, navigate, setLoading) => async (dispatch) => {
    try {
        const response = await apiConfig.post("login", user);
        localStorage.setItem(Tokens.ADMIN, response.data.data.token);
        localStorage.setItem(
            Tokens.GET_PERMISSIONS,
            response.data.data.permissions
        );
        localStorage.setItem(Tokens.USER, response.data.data.user.email);
        localStorage.setItem(
            Tokens.IMAGE,
            response.data.data.user.image_url
        );
        localStorage.setItem(
            Tokens.FIRST_NAME,
            response.data.data.user.first_name
        );
        localStorage.setItem(
            Tokens.LANGUAGE,
            response.data.data.user.language
        );
        localStorage.setItem(
            Tokens.LAST_NAME,
            response.data.data.user.last_name
        );
        localStorage.setItem(
            "loginUserArray",
            JSON.stringify(response.data.data.user)
        );
        localStorage.setItem(
            "user_time",
            Date.now() + response.data.data.expires_at * 60 * 1000
        );
        Cookies.set('authToken', response.data.data.token, { expires: new Date(new Date().getTime() + response.data.data.expires_at * 60 * 1000) });
        dispatch({
            type: authActionType.LOGIN_USER,
            payload: response.data.data,
        });
        dispatch(setLanguage(response.data.data.user.language));
        localStorage.setItem(
            Tokens.UPDATED_LANGUAGE,
            response.data.data.user.language
        );

        const userPermissions = response.data.data.permissions;
        // roles is returned at response.data.data.roles (string e.g. "platform_super_admin")
        const userRole        = response.data.data.roles ?? '';
        const mappedRoutes    = userPermissions.map(mapPermissionToRoute);
        const isPosOnlyUser   = mappedRoutes.length === 1 && userPermissions.includes("manage_pos_screen");

        if (isPosOnlyUser) {
            localStorage.setItem('showRegisterModalOnPosMount', 'true');
        }

        // If a tenant user logs in from the central domain, redirect them to
        // their tenant subdomain so all API calls are scoped correctly.
        const tenantDomain  = response.data.data.tenant_domain;
        const currentHost   = window.location.hostname;
        const centralDomain = currentHost; // noovapos.local or 127.0.0.1

        const isCentralDomain = !tenantDomain ||
            currentHost === tenantDomain ||
            currentHost.endsWith('.' + (tenantDomain?.split('.').slice(1).join('.') || ''));

        const isSuperAdmin = userRole === 'platform_super_admin';

        if (!isSuperAdmin && tenantDomain && currentHost !== tenantDomain) {
            // Tenant user on wrong domain → redirect to their subdomain with token in URL
            // The token is already in the cookie, so just redirect to their login page.
            // They'll be auto-logged in via persisted Redux state on the subdomain.
            const protocol = window.location.protocol;
            const port = window.location.port ? `:${window.location.port}` : '';
            window.location.href = `${protocol}//${tenantDomain}${port}/app/dashboard`;
            return;
        }

        if (mappedRoutes && mappedRoutes.length > 0) {
            // Platform super-admin → go directly to platform dashboard
            if (
                userRole === 'platform_super_admin' ||
                userPermissions.includes("manage_platform") ||
                userPermissions.includes("manage_tenants")
            ) {
                navigate("/app/platform/dashboard");
            } else if (userPermissions.includes("manage_dashboard")) {
                navigate("/app/dashboard");
            } else if (isPosOnlyUser) {
                navigate("/app/pos");
            } else {
                navigate(mappedRoutes[0]);
            }
        } else {
            navigate("/app/dashboard");
        }

        dispatch(fetchPermissions());
        dispatch(fetchFrontSetting());
        dispatch(fetchConfig());
        dispatch(
            addToast({ text: getFormattedMessage("login.success.message") })
        );
        const isLanguageDataFetched = await dispatch(fetchLanguageData(response.data.data.user.language_id));
    } catch (error) {
        const msg =
            error?.response?.data?.message ||
            error?.response?.data?.error ||
            'Login failed. Please check your credentials.';
        dispatch(addToast({ text: msg, type: toastType.ERROR }));
        setLoading(false);
    }
};

export const logoutAction = (token, navigate) => async (dispatch) => {
    await apiConfig
        .post("logout", token)
        .then(() => {
            localStorage.removeItem(Tokens.ADMIN);
            localStorage.removeItem(Tokens.USER);
            localStorage.removeItem(Tokens.IMAGE);
            localStorage.removeItem(Tokens.FIRST_NAME);
            localStorage.removeItem(Tokens.LAST_NAME);
            localStorage.removeItem("loginUserArray");
            localStorage.removeItem(Tokens.UPDATED_EMAIL);
            localStorage.removeItem(Tokens.UPDATED_FIRST_NAME);
            localStorage.removeItem(Tokens.UPDATED_LAST_NAME);
            localStorage.removeItem(Tokens.USER_IMAGE_URL);
            Cookies.remove('authToken');
            navigate("/login");
            dispatch(
                addToast({
                    text: getFormattedMessage("logout.success.message"),
                })
            );
        })
        .catch(({ response }) => {
            dispatch(
                addToast({ text: response.data.message, type: toastType.ERROR })
            );
        });
};

export const forgotPassword = (user) => async (dispatch) => {
    await apiConfig
        .post(apiBaseURL.ADMIN_FORGOT_PASSWORD, user)
        .then((response) => {
            dispatch({
                type: authActionType.ADMIN_FORGOT_PASSWORD,
                payload: response.data.message,
            });
            dispatch(
                addToast({
                    text: getFormattedMessage(
                        "forgot-password-form.success.reset-link.label"
                    ),
                })
            );
        })
        .catch(({ response }) => {
            dispatch({ type: toastType.ERROR, payload: response.data.message });
            dispatch(
                addToast({ text: response.data.message, type: toastType.ERROR })
            );
        });
};

export const resetPassword = (user, navigate) => async (dispatch) => {
    await apiConfig
        .post(apiBaseURL.ADMIN_RESET_PASSWORD, user)
        .then((response) => {
            dispatch({
                type: authActionType.ADMIN_RESET_PASSWORD,
                payload: user,
            });
            dispatch(
                addToast({
                    text: getFormattedMessage(
                        "reset-password.success.update.message"
                    ),
                })
            );
            navigate("/login");
        })
        .catch(({ response }) => {
            dispatch(
                addToast({ text: response.data.message, type: toastType.ERROR })
            );
        });
};
