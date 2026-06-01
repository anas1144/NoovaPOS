import apiConfig from "../../config/apiConfig";
import {
    apiBaseURL,
    offlineDeviceActionType,
    toastType,
} from "../../constants";
import { setLoading } from "./loadingAction";
import { setTotalRecord } from "./totalRecordAction";
import { addToast } from "./toastAction";

export const fetchOfflineDevices =
    (filter = {}, isLoading = true) =>
    async (dispatch) => {
        if (isLoading) dispatch(setLoading(true));
        try {
            const response = await apiConfig.get(apiBaseURL.OFFLINE_DEVICES, {
                params: filter,
            });
            const payload = response.data?.data?.data || response.data?.data;
            dispatch({
                type: offlineDeviceActionType.FETCH_OFFLINE_DEVICES,
                payload: payload || [],
            });
            dispatch(
                setTotalRecord(
                    response.data?.data?.total || response.data?.meta?.total || 0
                )
            );
        } catch ({ response }) {
            dispatch(
                addToast({
                    text:
                        response?.data?.message ||
                        "Failed to fetch offline devices",
                    type: toastType.ERROR,
                })
            );
        } finally {
            if (isLoading) dispatch(setLoading(false));
        }
    };

export const fetchSyncQueue =
    (filter = {}, isLoading = true) =>
    async (dispatch) => {
        if (isLoading) dispatch(setLoading(true));
        try {
            const response = await apiConfig.get(apiBaseURL.SYNC_QUEUE, {
                params: filter,
            });
            const payload = response.data?.data?.data || response.data?.data;
            dispatch({
                type: offlineDeviceActionType.FETCH_SYNC_QUEUE,
                payload: payload || [],
            });
            dispatch(
                setTotalRecord(
                    response.data?.data?.total || response.data?.meta?.total || 0
                )
            );
        } catch ({ response }) {
            dispatch(
                addToast({
                    text:
                        response?.data?.message ||
                        "Failed to fetch sync queue",
                    type: toastType.ERROR,
                })
            );
        } finally {
            if (isLoading) dispatch(setLoading(false));
        }
    };

export const updateSyncQueueStatus =
    (id, payload) => async (dispatch) => {
        try {
            await apiConfig.patch(
                apiBaseURL.SYNC_QUEUE + "/" + id + "/status",
                payload
            );
            dispatch(addToast({ text: "Sync queue item updated." }));
            dispatch(fetchSyncQueue());
        } catch ({ response }) {
            dispatch(
                addToast({
                    text:
                        response?.data?.message ||
                        "Failed to update sync queue",
                    type: toastType.ERROR,
                })
            );
        }
    };
