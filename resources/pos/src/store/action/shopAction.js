import apiConfig from "../../config/apiConfig";
import { apiBaseURL, shopActionType, toastType } from "../../constants";
import { getFormattedMessage } from "../../shared/sharedMethod";
import { setLoading } from "./loadingAction";
import { addToast } from "./toastAction";
import { setTotalRecord, removeFromTotalRecord } from "./totalRecordAction";
import { callFetchDataApi } from "./updateBrand";
import requestParam from "../../shared/requestParam";

export const fetchShops =
    (filter = {}, isLoading = true) =>
    async (dispatch) => {
        if (isLoading) {
            dispatch(setLoading(true));
        }
        let url = apiBaseURL.SHOPS;
        if (!_.isEmpty(filter)) {
            url += requestParam(filter, false, true, null, url);
        }
        await apiConfig
            .get(url)
            .then((response) => {
                dispatch({
                    type: shopActionType.FETCH_SHOPS,
                    payload: response.data.data,
                });
                dispatch(setTotalRecord(response?.data?.meta?.total || 0));
            })
            .catch(({ response }) => {
                dispatch(
                    addToast({
                        text: response?.data?.message || "Failed to fetch shops",
                        type: toastType.ERROR,
                    })
                );
            })
            .finally(() => {
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            });
    };

export const addShop = (data, handleClose, clearData) => (dispatch) => {
    apiConfig
        .post(apiBaseURL.SHOPS, data)
        .then((response) => {
            dispatch(
                addToast({
                    text: getFormattedMessage("shop.success.create.message"),
                })
            );
            dispatch({
                type: shopActionType.ADD_SHOP,
                payload: response.data.data,
            });
            dispatch(fetchShops());
            handleClose && handleClose();
            clearData && clearData();
        })
        .catch(({ response }) => {
            dispatch(
                addToast({
                    text: response?.data?.message || "Failed to create shop",
                    type: toastType.ERROR,
                })
            );
        });
};

export const editShop = (shopId, data, handleClose) => async (dispatch) => {
    apiConfig
        .post(apiBaseURL.SHOPS + "/" + shopId, data)
        .then(() => {
            dispatch(callFetchDataApi(true));
            handleClose && handleClose(false);
            dispatch(
                addToast({
                    text: getFormattedMessage("shop.success.edit.message"),
                })
            );
            dispatch(fetchShops());
        })
        .catch(({ response }) => {
            dispatch(
                addToast({
                    text: response?.data?.message || "Failed to update shop",
                    type: toastType.ERROR,
                })
            );
        });
};

export const deleteShop = (shopId) => async (dispatch) => {
    apiConfig
        .delete(apiBaseURL.SHOPS + "/" + shopId)
        .then(() => {
            dispatch(removeFromTotalRecord(1));
            dispatch({
                type: shopActionType.DELETE_SHOP,
                payload: shopId,
            });
            dispatch(callFetchDataApi(true));
            dispatch(
                addToast({
                    text: getFormattedMessage("shop.success.delete.message"),
                })
            );
            dispatch(fetchShops());
        })
        .catch(({ response }) => {
            dispatch(
                addToast({
                    text: response?.data?.message || "Failed to delete shop",
                    type: toastType.ERROR,
                })
            );
        });
};

export const changeShopStatus = (shopId) => async (dispatch) => {
    try {
        const response = await apiConfig.get(
            apiBaseURL.SHOPS + "/" + shopId + "/status-change"
        );
        dispatch(fetchShops());
        dispatch(
            addToast({
                text: response.data.message,
            })
        );
        return { success: true };
    } catch ({ response }) {
        dispatch(
            addToast({
                text: response?.data?.message || "Failed to change shop status",
                type: toastType.ERROR,
            })
        );
        return { success: false };
    }
};
