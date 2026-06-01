import apiConfig from "../../config/apiConfig";
import { apiBaseURL, accountingActionType, toastType } from "../../constants";
import { setLoading } from "./loadingAction";
import { addToast } from "./toastAction";

const handleError = (dispatch, response, fallback) =>
    dispatch(
        addToast({
            text: response?.data?.message || fallback,
            type: toastType.ERROR,
        })
    );

export const fetchAccounts =
    (filter = {}) =>
    async (dispatch) => {
        try {
            const res = await apiConfig.get(apiBaseURL.ACCOUNTS, {
                params: filter,
            });
            dispatch({
                type: accountingActionType.FETCH_ACCOUNTS,
                payload: res.data?.data || [],
            });
        } catch ({ response }) {
            handleError(dispatch, response, "Failed to fetch accounts");
        }
    };

export const saveAccount = (data, id, onSuccess) => async (dispatch) => {
    try {
        if (id) {
            await apiConfig.patch(apiBaseURL.ACCOUNTS + "/" + id, data);
        } else {
            await apiConfig.post(apiBaseURL.ACCOUNTS, data);
        }
        dispatch(addToast({ text: "Account saved." }));
        dispatch(fetchAccounts());
        onSuccess && onSuccess();
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to save account");
    }
};

export const deleteAccount = (id) => async (dispatch) => {
    try {
        await apiConfig.delete(apiBaseURL.ACCOUNTS + "/" + id);
        dispatch(addToast({ text: "Account deleted." }));
        dispatch(fetchAccounts());
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to delete account");
    }
};

export const fetchJournalEntries =
    (filter = {}) =>
    async (dispatch) => {
        dispatch(setLoading(true));
        try {
            const res = await apiConfig.get(apiBaseURL.JOURNAL_ENTRIES, {
                params: filter,
            });
            const payload = res.data?.data?.data || res.data?.data;
            dispatch({
                type: accountingActionType.FETCH_JOURNAL_ENTRIES,
                payload: payload || [],
            });
        } catch ({ response }) {
            handleError(dispatch, response, "Failed to fetch journal entries");
        } finally {
            dispatch(setLoading(false));
        }
    };

export const saveJournalEntry = (data, onSuccess) => async (dispatch) => {
    try {
        await apiConfig.post(apiBaseURL.JOURNAL_ENTRIES, data);
        dispatch(addToast({ text: "Journal entry created." }));
        dispatch(fetchJournalEntries());
        onSuccess && onSuccess();
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to save journal entry");
    }
};

export const postJournalEntry = (id) => async (dispatch) => {
    try {
        await apiConfig.patch(apiBaseURL.JOURNAL_ENTRIES + "/" + id + "/post");
        dispatch(addToast({ text: "Journal entry posted." }));
        dispatch(fetchJournalEntries());
    } catch ({ response }) {
        handleError(dispatch, response, "Failed to post journal entry");
    }
};

export const fetchTrialBalance =
    (filter = {}) =>
    async (dispatch) => {
        try {
            const res = await apiConfig.get(apiBaseURL.TRIAL_BALANCE, {
                params: filter,
            });
            dispatch({
                type: accountingActionType.FETCH_TRIAL_BALANCE,
                payload: res.data?.data || { rows: [], totals: {} },
            });
        } catch ({ response }) {
            handleError(dispatch, response, "Failed to load trial balance");
        }
    };

export const fetchProfitAndLoss =
    (filter = {}) =>
    async (dispatch) => {
        try {
            const res = await apiConfig.get(apiBaseURL.PROFIT_LOSS, {
                params: filter,
            });
            return res.data?.data;
        } catch ({ response }) {
            handleError(dispatch, response, "Failed to load profit & loss");
            return null;
        }
    };

export const fetchBalanceSheet =
    (filter = {}) =>
    async (dispatch) => {
        try {
            const res = await apiConfig.get(apiBaseURL.BALANCE_SHEET, {
                params: filter,
            });
            return res.data?.data;
        } catch ({ response }) {
            handleError(dispatch, response, "Failed to load balance sheet");
            return null;
        }
    };
