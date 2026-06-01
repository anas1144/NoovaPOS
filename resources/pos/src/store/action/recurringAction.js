import apiConfig from "../../config/apiConfig";
import { apiBaseURL, recurringActionType, toastType } from "../../constants";
import { setLoading } from "./loadingAction";
import { addToast } from "./toastAction";

const handleError = (dispatch, response, fallback) =>
    dispatch(
        addToast({
            text: response?.data?.message || fallback,
            type: toastType.ERROR,
        })
    );

// Plans
export const fetchRecurringPlans = () => async (dispatch) => {
    try {
        const res = await apiConfig.get(apiBaseURL.RECURRING_PLANS);
        dispatch({
            type: recurringActionType.FETCH_RECURRING_PLANS,
            payload: res.data?.data || [],
        });
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to fetch recurring plans");
    }
};

export const saveRecurringPlan = (data, id, onSuccess) => async (dispatch) => {
    try {
        if (id) {
            await apiConfig.patch(apiBaseURL.RECURRING_PLANS + "/" + id, data);
        } else {
            await apiConfig.post(apiBaseURL.RECURRING_PLANS, data);
        }
        dispatch(addToast({ text: "Recurring plan saved." }));
        dispatch(fetchRecurringPlans());
        onSuccess && onSuccess();
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to save plan");
    }
};

export const deleteRecurringPlan = (id) => async (dispatch) => {
    try {
        await apiConfig.delete(apiBaseURL.RECURRING_PLANS + "/" + id);
        dispatch(addToast({ text: "Recurring plan deleted." }));
        dispatch(fetchRecurringPlans());
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to delete plan");
    }
};

// Subscriptions
export const fetchCustomerSubscriptions =
    (filter = {}) =>
    async (dispatch) => {
        dispatch(setLoading(true));
        try {
            const res = await apiConfig.get(apiBaseURL.CUSTOMER_SUBSCRIPTIONS, {
                params: filter,
            });
            const payload = res.data?.data?.data || res.data?.data;
            dispatch({
                type: recurringActionType.FETCH_CUSTOMER_SUBSCRIPTIONS,
                payload: payload || [],
            });
        } catch ({ response }) {
            handleError(dispatch, response, "Failed to fetch subscriptions");
        } finally {
            dispatch(setLoading(false));
        }
    };

export const saveCustomerSubscription =
    (data, id, onSuccess) => async (dispatch) => {
        try {
            if (id) {
                await apiConfig.patch(
                    apiBaseURL.CUSTOMER_SUBSCRIPTIONS + "/" + id,
                    data
                );
            } else {
                await apiConfig.post(apiBaseURL.CUSTOMER_SUBSCRIPTIONS, data);
            }
            dispatch(addToast({ text: "Subscription saved." }));
            dispatch(fetchCustomerSubscriptions());
            onSuccess && onSuccess();
        } catch ({ response }) {
            handleError(dispatch, response, "Failed to save subscription");
        }
    };

export const pauseSubscription = (id) => async (dispatch) => {
    try {
        await apiConfig.post(
            apiBaseURL.CUSTOMER_SUBSCRIPTIONS + "/" + id + "/pause"
        );
        dispatch(addToast({ text: "Subscription paused." }));
        dispatch(fetchCustomerSubscriptions());
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to pause subscription");
    }
};

export const resumeSubscription = (id) => async (dispatch) => {
    try {
        await apiConfig.post(
            apiBaseURL.CUSTOMER_SUBSCRIPTIONS + "/" + id + "/resume"
        );
        dispatch(addToast({ text: "Subscription resumed." }));
        dispatch(fetchCustomerSubscriptions());
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to resume subscription");
    }
};

export const generateInvoiceForSubscription =
    (subId, period_start, period_end) => async (dispatch) => {
        try {
            await apiConfig.post(
                apiBaseURL.CUSTOMER_SUBSCRIPTIONS +
                    "/" +
                    subId +
                    "/generate-invoice",
                { period_start, period_end }
            );
            dispatch(addToast({ text: "Invoice generated." }));
        } catch ({ response }) {
            handleError(dispatch, response, "Failed to generate invoice");
        }
    };

// Deliveries
export const fetchDeliveries =
    (filter = {}) =>
    async (dispatch) => {
        dispatch(setLoading(true));
        try {
            const res = await apiConfig.get(apiBaseURL.DELIVERY_SCHEDULES, {
                params: filter,
            });
            const payload = res.data?.data?.data || res.data?.data;
            dispatch({
                type: recurringActionType.FETCH_DELIVERY_SCHEDULES,
                payload: payload || [],
            });
        } catch ({ response }) {
            handleError(dispatch, response, "Failed to fetch deliveries");
        } finally {
            dispatch(setLoading(false));
        }
    };

export const updateDelivery = (id, data) => async (dispatch) => {
    try {
        await apiConfig.patch(apiBaseURL.DELIVERY_SCHEDULES + "/" + id, data);
        dispatch(addToast({ text: "Delivery updated." }));
        dispatch(fetchDeliveries());
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to update delivery");
    }
};
