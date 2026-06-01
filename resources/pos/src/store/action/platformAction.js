import apiConfig from "../../config/apiConfig";
import { apiBaseURL, platformActionType, toastType } from "../../constants";
import { setLoading } from "./loadingAction";
import { setTotalRecord } from "./totalRecordAction";
import { addToast } from "./toastAction";

const get = (url, params = {}) => apiConfig.get(url, { params });

export const fetchPlatformDashboard = () => async (dispatch) => {
    dispatch(setLoading(true));
    try {
        const response = await get(apiBaseURL.PLATFORM_DASHBOARD);
        dispatch({
            type: platformActionType.FETCH_PLATFORM_DASHBOARD,
            payload: response.data.data,
        });
    } catch ({ response }) {
        dispatch(
            addToast({
                text:
                    response?.data?.message ||
                    "Failed to load platform dashboard",
                type: toastType.ERROR,
            })
        );
    } finally {
        dispatch(setLoading(false));
    }
};

export const fetchPlatformTenants =
    (filter = {}) =>
    async (dispatch) => {
        dispatch(setLoading(true));
        try {
            const response = await get(apiBaseURL.PLATFORM_TENANTS, filter);
            const payload = response.data?.data?.data || response.data?.data;
            dispatch({
                type: platformActionType.FETCH_PLATFORM_TENANTS,
                payload: payload || [],
            });
            dispatch(
                setTotalRecord(
                    response.data?.data?.total ||
                        response.data?.meta?.total ||
                        0
                )
            );
        } catch ({ response }) {
            dispatch(
                addToast({
                    text:
                        response?.data?.message || "Failed to load tenants",
                    type: toastType.ERROR,
                })
            );
        } finally {
            dispatch(setLoading(false));
        }
    };

export const changeTenantStatus = (tenantId, status) => async (dispatch) => {
    try {
        await apiConfig.patch(
            apiBaseURL.PLATFORM_TENANTS + "/" + tenantId + "/status",
            { status }
        );
        dispatch(addToast({ text: "Tenant status updated." }));
        dispatch(fetchPlatformTenants());
    } catch ({ response }) {
        dispatch(
            addToast({
                text:
                    response?.data?.message || "Failed to update tenant status",
                type: toastType.ERROR,
            })
        );
    }
};

export const createTenant = (data, onSuccess) => async (dispatch) => {
    try {
        const response = await apiConfig.post(apiBaseURL.PLATFORM_TENANTS, data);
        dispatch(addToast({ text: "Tenant created successfully." }));
        dispatch(fetchPlatformTenants());
        onSuccess && onSuccess(response.data?.data);
    } catch ({ response }) {
        dispatch(
            addToast({
                text: response?.data?.message || "Failed to create tenant",
                type: toastType.ERROR,
            })
        );
    }
};

export const assignTenantPlan = (tenantId, data) => async (dispatch) => {
    try {
        await apiConfig.post(
            apiBaseURL.PLATFORM_TENANTS + "/" + tenantId + "/subscription",
            data
        );
        dispatch(addToast({ text: "Subscription assigned." }));
        dispatch(fetchPlatformTenants());
    } catch ({ response }) {
        dispatch(
            addToast({
                text:
                    response?.data?.message ||
                    "Failed to assign subscription",
                type: toastType.ERROR,
            })
        );
    }
};

export const fetchPlatformPlans =
    (filter = {}) =>
    async (dispatch) => {
        try {
            const response = await get(apiBaseURL.PLATFORM_PLANS, filter);
            const payload = response.data?.data?.data || response.data?.data;
            dispatch({
                type: platformActionType.FETCH_PLATFORM_PLANS,
                payload: payload || [],
            });
        } catch ({ response }) {
            dispatch(
                addToast({
                    text: response?.data?.message || "Failed to load plans",
                    type: toastType.ERROR,
                })
            );
        }
    };

export const addPlan = (data, onSuccess) => async (dispatch) => {
    try {
        await apiConfig.post(apiBaseURL.PLATFORM_PLANS, data);
        dispatch(addToast({ text: "Plan created successfully." }));
        dispatch(fetchPlatformPlans());
        onSuccess && onSuccess();
    } catch ({ response }) {
        dispatch(
            addToast({
                text: response?.data?.message || "Failed to create plan",
                type: toastType.ERROR,
            })
        );
    }
};

export const editPlan = (id, data, onSuccess) => async (dispatch) => {
    try {
        await apiConfig.patch(apiBaseURL.PLATFORM_PLANS + "/" + id, data);
        dispatch(addToast({ text: "Plan updated successfully." }));
        dispatch(fetchPlatformPlans());
        onSuccess && onSuccess();
    } catch ({ response }) {
        dispatch(
            addToast({
                text: response?.data?.message || "Failed to update plan",
                type: toastType.ERROR,
            })
        );
    }
};

export const fetchPlatformSubscriptions =
    (filter = {}) =>
    async (dispatch) => {
        try {
            const response = await get(
                apiBaseURL.PLATFORM_SUBSCRIPTIONS,
                filter
            );
            const payload = response.data?.data?.data || response.data?.data;
            dispatch({
                type: platformActionType.FETCH_PLATFORM_SUBSCRIPTIONS,
                payload: payload || [],
            });
        } catch ({ response }) {
            dispatch(
                addToast({
                    text:
                        response?.data?.message ||
                        "Failed to load subscriptions",
                    type: toastType.ERROR,
                })
            );
        }
    };

export const fetchPlatformAuditLogs =
    (filter = {}) =>
    async (dispatch) => {
        try {
            const response = await get(apiBaseURL.PLATFORM_AUDIT_LOGS, filter);
            const payload = response.data?.data?.data || response.data?.data;
            dispatch({
                type: platformActionType.FETCH_PLATFORM_AUDIT_LOGS,
                payload: payload || [],
            });
        } catch ({ response }) {
            dispatch(
                addToast({
                    text:
                        response?.data?.message ||
                        "Failed to load audit logs",
                    type: toastType.ERROR,
                })
            );
        }
    };

export const fetchPlatformBackups =
    (filter = {}) =>
    async (dispatch) => {
        try {
            const response = await get(apiBaseURL.PLATFORM_BACKUPS, filter);
            const payload = response.data?.data?.data || response.data?.data;
            dispatch({
                type: platformActionType.FETCH_PLATFORM_BACKUPS,
                payload: payload || [],
            });
        } catch ({ response }) {
            dispatch(
                addToast({
                    text: response?.data?.message || "Failed to load backups",
                    type: toastType.ERROR,
                })
            );
        }
    };

export const requestTenantBackup =
    (tenantId, backupType = "full") =>
    async (dispatch) => {
        try {
            await apiConfig.post(
                apiBaseURL.PLATFORM_TENANTS + "/" + tenantId + "/backups",
                { backup_type: backupType }
            );
            dispatch(addToast({ text: "Backup requested." }));
            dispatch(fetchPlatformBackups());
        } catch ({ response }) {
            dispatch(
                addToast({
                    text:
                        response?.data?.message ||
                        "Failed to request backup",
                    type: toastType.ERROR,
                })
            );
        }
    };
