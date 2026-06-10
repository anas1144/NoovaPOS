import apiConfig from "../../config/apiConfig";
import { apiBaseURL, saleActionType, toastType } from "../../constants";
import { addToast } from "./toastAction";
import {
    addInToTotalRecord,
    removeFromTotalRecord,
    setTotalRecord,
} from "./totalRecordAction";
import { setLoading } from "./loadingAction";
import requestParam from "../../shared/requestParam";
import { getFormattedMessage } from "../../shared/sharedMethod";
import { callSaleApi } from "./saleApiAction";
import { setSavingButton } from "./saveButtonAction";
import { callFetchDataApi } from "./updateBrand";

export const fetchSales =
    (filter = {}, isLoading = true, shouldAppend = false) =>
    async (dispatch) => {
        if (isLoading) {
            dispatch(setLoading(true));
        }
        const admin = true;
        let url = apiBaseURL.SALES;
        if (
            (filter && Object.keys(filter).length > 0) &&
            (filter.page ||
                filter.pageSize ||
                filter.search ||
                filter.order_By ||
                filter.created_at ||
                filter.customer_id)
        ) {
            url += requestParam(filter, admin, null, null, url);
        }
        await apiConfig
            .get(url)
            .then((response) => {
                dispatch({
                    type: saleActionType.FETCH_SALES,
                    payload: response.data.data,
                    meta: { append: shouldAppend },
                });
                dispatch(
                    setTotalRecord(
                        response.data.meta.total !== undefined &&
                            response.data.meta.total >= 0
                            ? response.data.meta.total
                            : response.data.data.total
                    )
                );
                dispatch(callSaleApi(false));
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            })
            .catch(({ response }) => {
                dispatch(
                    addToast({
                        text: response.data.message,
                        type: toastType.ERROR,
                    })
                );
            });
    };

export const fetchSale =
    (saleId, singleSale, isLoading = true) =>
    async (dispatch) => {
        if (isLoading) {
            dispatch(setLoading(true));
        }
        await apiConfig
            .get(apiBaseURL.SALES + "/" + saleId + "/edit", singleSale)
            .then((response) => {
                dispatch({
                    type: saleActionType.FETCH_SALE,
                    payload: response.data.data,
                });
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            })
            .catch(({ response }) => {
                dispatch(
                    addToast({
                        text: response.data.message,
                        type: toastType.ERROR,
                    })
                );
            });
    };

export const addSale = (sale, navigate) => async (dispatch) => {
    dispatch(setSavingButton(true));
    await apiConfig
        .post(apiBaseURL.SALES, sale)
        .then((response) => {
            dispatch({
                type: saleActionType.ADD_SALE,
                payload: response.data.data,
            });
            dispatch(
                addToast({
                    text: getFormattedMessage("sale.success.create.message"),
                })
            );
            dispatch(addInToTotalRecord(1));
            navigate("/app/sales");
            dispatch(setSavingButton(false));
        })
        .catch(({ response }) => {
            dispatch(setSavingButton(false));
            dispatch(
                addToast({ text: response.data.message, type: toastType.ERROR })
            );
        });
};

export const editSale = (saleId, sale, navigate) => async (dispatch) => {
    dispatch(setSavingButton(true));
    await apiConfig
        .patch(apiBaseURL.SALES + "/" + saleId, sale)
        .then((response) => {
            dispatch(
                addToast({
                    text: getFormattedMessage("sale.success.edit.message"),
                })
            );
            navigate("/app/sales");
            // dispatch({
            //     type: saleActionType.EDIT_SALE,
            //     payload: response.data.data,
            // });
            dispatch(setSavingButton(false));
        })
        .catch(({ response }) => {
            dispatch(setSavingButton(false));
            dispatch(
                addToast({ text: response.data.message, type: toastType.ERROR })
            );
        });
};

export const deleteSale = (userId) => async (dispatch) => {
    await apiConfig
        .delete(apiBaseURL.SALES + "/" + userId)
        .then(() => {
            dispatch(callFetchDataApi(true));
            dispatch(removeFromTotalRecord(1));
            dispatch({ type: saleActionType.DELETE_SALE, payload: userId });
            dispatch(
                addToast({
                    text: getFormattedMessage("sale.success.delete.message"),
                })
            );
        })
        .catch(({ response }) => {
            response &&
                dispatch(
                    addToast({
                        text: response.data.message,
                        type: toastType.ERROR,
                    })
                );
        });
};
export const postSale = (saleId, navigate, setIsPosting) => async (dispatch) => {
    try {
        await apiConfig
            .post(apiBaseURL.SALES + "/" + saleId + "/post")
            .then((response) => {
                dispatch({
                    type: saleActionType.EDIT_SALE,
                    payload: response.data.data,
                });
                dispatch(
                    addToast({
                        text: "Sale posted successfully!",
                        type: toastType.SUCCESS,
                    })
                );
                dispatch(callFetchDataApi(true));
                if (setIsPosting) {
                    setIsPosting(false);
                }
                navigate("/app/sales");
            })
            .catch(({ response }) => {
                if (setIsPosting) {
                    setIsPosting(false);
                }
                dispatch(
                    addToast({ text: response.data.message, type: toastType.ERROR })
                );
            });
    } catch (error) {
        if (setIsPosting) {
            setIsPosting(false);
        }
        dispatch(
            addToast({ text: error.message, type: toastType.ERROR })
        );
    }
};