import { platformActionType } from "../../constants";

const initialState = {
    dashboard: null,
    tenants: [],
    plans: [],
    subscriptions: [],
    auditLogs: [],
    backups: [],
    roleTemplates: [],
    permissionCatalog: { modules: [], shop_types: [] },
    payments: [],
    bankAccounts: [],
};

export default (state = initialState, action) => {
    switch (action.type) {
        case platformActionType.FETCH_PLATFORM_DASHBOARD:
            return { ...state, dashboard: action.payload };
        case platformActionType.FETCH_PLATFORM_TENANTS:
            return { ...state, tenants: action.payload || [] };
        case platformActionType.FETCH_PLATFORM_PLANS:
            return { ...state, plans: action.payload || [] };
        case platformActionType.FETCH_PLATFORM_SUBSCRIPTIONS:
            return { ...state, subscriptions: action.payload || [] };
        case platformActionType.FETCH_PLATFORM_AUDIT_LOGS:
            return { ...state, auditLogs: action.payload || [] };
        case platformActionType.FETCH_PLATFORM_BACKUPS:
            return { ...state, backups: action.payload || [] };
        case platformActionType.FETCH_ROLE_TEMPLATES:
            return { ...state, roleTemplates: action.payload || [] };
        case platformActionType.FETCH_PERMISSION_CATALOG:
            return {
                ...state,
                permissionCatalog: action.payload || {
                    modules: [],
                    shop_types: [],
                },
            };
        case platformActionType.FETCH_PLATFORM_PAYMENTS:
            return { ...state, payments: action.payload || [] };
        case platformActionType.FETCH_BANK_ACCOUNTS:
            return { ...state, bankAccounts: action.payload || [] };
        default:
            return state;
    }
};
