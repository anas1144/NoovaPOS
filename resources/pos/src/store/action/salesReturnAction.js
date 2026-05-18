import apiConfig from "../../config/apiConfig";
import { apiBaseURL, saleReturnActionType, toastType } from "../../constants";
import { addToast } from "./toastAction";
import {
    addInToTotalRecord,
    removeFromTotalRecord,
    setTotalRecord,
} from "./totalRecordAction";
import { setLoading } from "./loadingAction";
import requestParam from "../../shared/requestParam";
import { getFormattedMessage } from "../../shared/sharedMethod";
import { setSavingButton } from "./saveButtonAction";
import { callFetchDataApi } from "./updateBrand";

export const fetchSalesReturn =
    (filter = {}, isLoading = true) =>
    async (dispatch) => {
        if (isLoading) {
            dispatch(setLoading(true));
        }
        const admin = true;
        let url = apiBaseURL.SALE_RETURN;
        if (
            !_.isEmpty(filter) &&
            (filter.page ||
                filter.pageSize ||
                filter.search ||
                filter.order_By ||
                filter.created_at)
        ) {
            url += requestParam(filter, admin, null, null, url);
        }
        await apiConfig
            .get(url)
            .then((response) => {
                dispatch({
                    type: saleReturnActionType.FETCH_SALES_RETURN,
                    payload: response.data.data,
                });
                dispatch(
                    setTotalRecord(
                        response.data.meta.total !== undefined &&
                            response.data.meta.total >= 0
                            ? response.data.meta.total
                            : response.data.data.total
                    )
                );
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            })
            .catch((error) => {
                const errorMessage = error?.response?.data?.message || 'Failed to load sales returns';
                dispatch(
                    addToast({
                        text: errorMessage,
                        type: toastType.ERROR,
                    })
                );
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            });
    };

export const fetchSaleReturn =
    (saleId, isSaleReturnFromSale, isLoading = true) =>
    async (dispatch) => {
        if (isLoading) {
            dispatch(setLoading(true));
        }
        await apiConfig
            .get(
                !isSaleReturnFromSale
                    ? apiBaseURL.SALE_RETURN + "/" + saleId + "/edit"
                    : apiBaseURL.EDIT_SALE_FROM_SALE + "/" + saleId
            )
            .then((response) => {
                dispatch({
                    type: saleReturnActionType.FETCH_SALE_RETURN,
                    payload: response.data.data,
                });
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            })
            .catch((error) => {
                const errorMessage = error?.response?.data?.message || 'Failed to load sale return details';
                dispatch(
                    addToast({
                        text: errorMessage,
                        type: toastType.ERROR,
                    })
                );
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            });
    };

export const addSaleReturn = (sale, navigate) => async (dispatch) => {
    dispatch(setSavingButton(true));
    await apiConfig
        .post(apiBaseURL.SALE_RETURN, sale)
        .then((response) => {
            dispatch({
                type: saleReturnActionType.ADD_SALE_RETURN,
                payload: response.data.data,
            });
            dispatch(
                addToast({
                    text: getFormattedMessage(
                        "sale-return.success.create.message"
                    ),
                })
            );
            dispatch(addInToTotalRecord(1));
            navigate("/app/sale-return");
            dispatch(setSavingButton(false));
        })
        .catch((error) => {
            dispatch(setSavingButton(false));
            const errorMessage = error?.response?.data?.message || 'Failed to create sale return';
            dispatch(
                addToast({ text: errorMessage, type: toastType.ERROR })
            );
        });
};

export const editSaleReturn = (saleId, sale, navigate) => async (dispatch) => {
    dispatch(setSavingButton(true));
    await apiConfig
        .patch(apiBaseURL.SALE_RETURN + "/" + saleId, sale)
        .then((response) => {
            dispatch(
                addToast({
                    text: getFormattedMessage(
                        "sale-return.success.edit.message"
                    ),
                })
            );
            navigate("/app/sale-return");
            // dispatch({
            //     type: saleReturnActionType.EDIT_SALE_RETURN,
            //     payload: response.data.data,
            // });
            dispatch(setSavingButton(false));
        })
        .catch((error) => {
            dispatch(setSavingButton(false));
            const errorMessage = error?.response?.data?.message || 'Failed to update sale return';
            dispatch(
                addToast({ text: errorMessage, type: toastType.ERROR })
            );
        });
};

export const deleteSaleReturn = (userId) => async (dispatch) => {
    await apiConfig
        .delete(apiBaseURL.SALE_RETURN + "/" + userId)
        .then(() => {
            dispatch(removeFromTotalRecord(1));
            dispatch({
                type: saleReturnActionType.DELETE_SALE_RETURN,
                payload: userId,
            });
            dispatch(callFetchDataApi(true));
            dispatch(
                addToast({
                    text: getFormattedMessage(
                        "sale-return.success.delete.message"
                    ),
                })
            );
        })
        .catch((error) => {
            const errorMessage = error?.response?.data?.message || 'Failed to delete sale return';
            dispatch(
                addToast({ text: errorMessage, type: toastType.ERROR })
            );
        });
};

export const postSaleReturn = (saleReturnId, navigate, callback) => async (dispatch) => {
    try {
        await apiConfig
            .post(apiBaseURL.SALE_RETURN + "/" + saleReturnId + "/post")
            .then((response) => {
                dispatch({
                    type: saleReturnActionType.EDIT_SALE_RETURN,
                    payload: response.data.data,
                });
                dispatch(
                    addToast({
                        text: getFormattedMessage("sale-return.success.post.message"),
                        type: toastType.SUCCESS,
                    })
                );
                dispatch(callFetchDataApi(true));
                if (callback) {
                    callback();
                }
            })
            .catch((error) => {
                if (callback) {
                    callback();
                }
                dispatch(
                    addToast({ text: error?.response?.data?.message || 'Failed to post sale return', type: toastType.ERROR })
                );
            });
    } catch (error) {
        if (callback) {
            callback();
        }
        dispatch(
            addToast({ text: error.message, type: toastType.ERROR })
        );
    }
};

