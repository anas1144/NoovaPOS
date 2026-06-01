import apiConfig from "../../config/apiConfig";
import { apiBaseURL, restaurantActionType, toastType } from "../../constants";
import { setLoading } from "./loadingAction";
import { addToast } from "./toastAction";

const handleError = (dispatch, response, fallback) =>
    dispatch(
        addToast({
            text: response?.data?.message || fallback,
            type: toastType.ERROR,
        })
    );

// Halls
export const fetchHalls =
    (filter = {}) =>
    async (dispatch) => {
        dispatch(setLoading(true));
        try {
            const res = await apiConfig.get(apiBaseURL.RESTAURANT_HALLS, {
                params: filter,
            });
            dispatch({
                type: restaurantActionType.FETCH_HALLS,
                payload: res.data?.data || [],
            });
        } catch ({ response }) {
            handleError(dispatch, response, "Failed to fetch halls");
        } finally {
            dispatch(setLoading(false));
        }
    };

export const saveHall = (data, id, onSuccess) => async (dispatch) => {
    try {
        if (id) {
            await apiConfig.patch(apiBaseURL.RESTAURANT_HALLS + "/" + id, data);
        } else {
            await apiConfig.post(apiBaseURL.RESTAURANT_HALLS, data);
        }
        dispatch(addToast({ text: id ? "Hall updated." : "Hall created." }));
        dispatch(fetchHalls());
        onSuccess && onSuccess();
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to save hall");
    }
};

export const deleteHall = (id) => async (dispatch) => {
    try {
        await apiConfig.delete(apiBaseURL.RESTAURANT_HALLS + "/" + id);
        dispatch(addToast({ text: "Hall deleted." }));
        dispatch(fetchHalls());
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to delete hall");
    }
};

// Tables
export const fetchTables =
    (filter = {}) =>
    async (dispatch) => {
        dispatch(setLoading(true));
        try {
            const res = await apiConfig.get(apiBaseURL.RESTAURANT_TABLES, {
                params: filter,
            });
            dispatch({
                type: restaurantActionType.FETCH_TABLES,
                payload: res.data?.data || [],
            });
        } catch ({ response }) {
            handleError(dispatch, response, "Failed to fetch tables");
        } finally {
            dispatch(setLoading(false));
        }
    };

export const saveTable = (data, id, onSuccess) => async (dispatch) => {
    try {
        if (id) {
            await apiConfig.patch(apiBaseURL.RESTAURANT_TABLES + "/" + id, data);
        } else {
            await apiConfig.post(apiBaseURL.RESTAURANT_TABLES, data);
        }
        dispatch(addToast({ text: id ? "Table updated." : "Table created." }));
        dispatch(fetchTables());
        onSuccess && onSuccess();
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to save table");
    }
};

export const deleteTable = (id) => async (dispatch) => {
    try {
        await apiConfig.delete(apiBaseURL.RESTAURANT_TABLES + "/" + id);
        dispatch(addToast({ text: "Table deleted." }));
        dispatch(fetchTables());
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to delete table");
    }
};

export const changeTableState = (id, state) => async (dispatch) => {
    try {
        await apiConfig.patch(
            apiBaseURL.RESTAURANT_TABLES + "/" + id + "/state",
            { state }
        );
        dispatch(fetchTables());
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to update table state");
    }
};

// KOT
export const fetchKots =
    (filter = {}) =>
    async (dispatch) => {
        dispatch(setLoading(true));
        try {
            const res = await apiConfig.get(apiBaseURL.RESTAURANT_KOTS, {
                params: filter,
            });
            const payload = res.data?.data?.data || res.data?.data;
            dispatch({
                type: restaurantActionType.FETCH_KOTS,
                payload: payload || [],
            });
        } catch ({ response }) {
            handleError(dispatch, response, "Failed to fetch KOTs");
        } finally {
            dispatch(setLoading(false));
        }
    };

export const createKot = (data, onSuccess) => async (dispatch) => {
    try {
        await apiConfig.post(apiBaseURL.RESTAURANT_KOTS, data);
        dispatch(addToast({ text: "KOT created." }));
        dispatch(fetchKots());
        onSuccess && onSuccess();
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to create KOT");
    }
};

export const updateKotStatus = (id, status) => async (dispatch) => {
    try {
        await apiConfig.patch(apiBaseURL.RESTAURANT_KOTS + "/" + id + "/status", {
            status,
        });
        dispatch(addToast({ text: "KOT status updated." }));
        dispatch(fetchKots());
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to update KOT status");
    }
};
