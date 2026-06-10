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

export const toggleTenantSeparateDb = (tenantId, value) => async (dispatch) => {
    try {
        await apiConfig.patch(
            apiBaseURL.PLATFORM_TENANTS + "/" + tenantId + "/separate-db",
            { uses_separate_db: value }
        );
        dispatch(addToast({ text: "Tenant database mode updated." }));
        dispatch(fetchPlatformTenants());
    } catch ({ response }) {
        dispatch(
            addToast({
                text: response?.data?.message || "Failed to update database mode",
                type: toastType.ERROR,
            })
        );
    }
};

export const grantTenantAddons = (tenantId, data, onSuccess) => async (dispatch) => {
    try {
        await apiConfig.post(
            apiBaseURL.PLATFORM_TENANTS + "/" + tenantId + "/addons",
            data
        );
        dispatch(addToast({ text: "Add-on allowance granted." }));
        dispatch(fetchPlatformTenants());
        onSuccess && onSuccess();
    } catch ({ response }) {
        dispatch(
            addToast({
                text: response?.data?.message || "Failed to grant add-ons",
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

export const fetchRoleTemplates =
    (filter = {}) =>
    async (dispatch) => {
        dispatch(setLoading(true));
        try {
            const response = await get(
                apiBaseURL.PLATFORM_ROLE_TEMPLATES,
                filter
            );
            dispatch({
                type: platformActionType.FETCH_ROLE_TEMPLATES,
                payload: response.data?.data || [],
            });
        } catch ({ response }) {
            dispatch(
                addToast({
                    text:
                        response?.data?.message ||
                        "Failed to load role templates",
                    type: toastType.ERROR,
                })
            );
        } finally {
            dispatch(setLoading(false));
        }
    };

export const fetchPermissionCatalog = () => async (dispatch) => {
    try {
        const response = await get(apiBaseURL.PLATFORM_PERMISSION_CATALOG);
        dispatch({
            type: platformActionType.FETCH_PERMISSION_CATALOG,
            payload: response.data?.data || { modules: [], shop_types: [] },
        });
    } catch ({ response }) {
        dispatch(
            addToast({
                text:
                    response?.data?.message ||
                    "Failed to load permission catalog",
                type: toastType.ERROR,
            })
        );
    }
};

export const addRoleTemplate = (data, onSuccess) => async (dispatch) => {
    try {
        await apiConfig.post(apiBaseURL.PLATFORM_ROLE_TEMPLATES, data);
        dispatch(addToast({ text: "Role template created successfully." }));
        dispatch(fetchRoleTemplates());
        onSuccess && onSuccess();
    } catch ({ response }) {
        dispatch(
            addToast({
                text:
                    response?.data?.message ||
                    "Failed to create role template",
                type: toastType.ERROR,
            })
        );
    }
};

export const editRoleTemplate = (id, data, onSuccess) => async (dispatch) => {
    try {
        await apiConfig.patch(
            apiBaseURL.PLATFORM_ROLE_TEMPLATES + "/" + id,
            data
        );
        dispatch(addToast({ text: "Role template updated successfully." }));
        dispatch(fetchRoleTemplates());
        onSuccess && onSuccess();
    } catch ({ response }) {
        dispatch(
            addToast({
                text:
                    response?.data?.message ||
                    "Failed to update role template",
                type: toastType.ERROR,
            })
        );
    }
};

export const deleteRoleTemplate = (id) => async (dispatch) => {
    try {
        await apiConfig.delete(apiBaseURL.PLATFORM_ROLE_TEMPLATES + "/" + id);
        dispatch(addToast({ text: "Role template deleted." }));
        dispatch(fetchRoleTemplates());
    } catch ({ response }) {
        dispatch(
            addToast({
                text:
                    response?.data?.message ||
                    "Failed to delete role template",
                type: toastType.ERROR,
            })
        );
    }
};

export const fetchPlatformPayments =
    (filter = {}) =>
    async (dispatch) => {
        dispatch(setLoading(true));
        try {
            const response = await get(apiBaseURL.PLATFORM_PAYMENTS, filter);
            dispatch({
                type: platformActionType.FETCH_PLATFORM_PAYMENTS,
                payload: response.data?.data || [],
            });
        } catch ({ response }) {
            dispatch(
                addToast({
                    text: response?.data?.message || "Failed to load payments",
                    type: toastType.ERROR,
                })
            );
        } finally {
            dispatch(setLoading(false));
        }
    };

export const confirmPayment = (id) => async (dispatch) => {
    try {
        await apiConfig.post(apiBaseURL.PLATFORM_PAYMENTS + "/" + id + "/confirm");
        dispatch(addToast({ text: "Payment confirmed; subscription activated." }));
        dispatch(fetchPlatformPayments());
    } catch ({ response }) {
        dispatch(
            addToast({
                text: response?.data?.message || "Failed to confirm payment",
                type: toastType.ERROR,
            })
        );
    }
};

export const rejectPayment = (id, note) => async (dispatch) => {
    try {
        await apiConfig.post(apiBaseURL.PLATFORM_PAYMENTS + "/" + id + "/reject", {
            note,
        });
        dispatch(addToast({ text: "Payment rejected." }));
        dispatch(fetchPlatformPayments());
    } catch ({ response }) {
        dispatch(
            addToast({
                text: response?.data?.message || "Failed to reject payment",
                type: toastType.ERROR,
            })
        );
    }
};

export const fetchBankAccounts =
    (filter = {}) =>
    async (dispatch) => {
        try {
            const response = await get(
                apiBaseURL.PLATFORM_BANK_ACCOUNTS,
                filter
            );
            dispatch({
                type: platformActionType.FETCH_BANK_ACCOUNTS,
                payload: response.data?.data || [],
            });
        } catch ({ response }) {
            dispatch(
                addToast({
                    text:
                        response?.data?.message ||
                        "Failed to load bank accounts",
                    type: toastType.ERROR,
                })
            );
        }
    };

export const addBankAccount = (data, onSuccess) => async (dispatch) => {
    try {
        await apiConfig.post(apiBaseURL.PLATFORM_BANK_ACCOUNTS, data);
        dispatch(addToast({ text: "Bank account saved." }));
        dispatch(fetchBankAccounts());
        onSuccess && onSuccess();
    } catch ({ response }) {
        dispatch(
            addToast({
                text: response?.data?.message || "Failed to save bank account",
                type: toastType.ERROR,
            })
        );
    }
};

export const editBankAccount = (id, data, onSuccess) => async (dispatch) => {
    try {
        await apiConfig.patch(
            apiBaseURL.PLATFORM_BANK_ACCOUNTS + "/" + id,
            data
        );
        dispatch(addToast({ text: "Bank account updated." }));
        dispatch(fetchBankAccounts());
        onSuccess && onSuccess();
    } catch ({ response }) {
        dispatch(
            addToast({
                text: response?.data?.message || "Failed to update bank account",
                type: toastType.ERROR,
            })
        );
    }
};

export const deleteBankAccount = (id) => async (dispatch) => {
    try {
        await apiConfig.delete(apiBaseURL.PLATFORM_BANK_ACCOUNTS + "/" + id);
        dispatch(addToast({ text: "Bank account deleted." }));
        dispatch(fetchBankAccounts());
    } catch ({ response }) {
        dispatch(
            addToast({
                text: response?.data?.message || "Failed to delete bank account",
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
