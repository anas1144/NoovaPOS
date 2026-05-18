import apiConfig from "../../config/apiConfig";
import requestParam from "../../shared/requestParam";
import { apiBaseURL, stockMovementActionType } from "../../constants";
import { setLoading } from "./loadingAction";
import { setTotalRecord } from "./totalRecordAction";

export const fetchStockMovements =
    (filter = {}, isLoading = true) =>
    async (dispatch) => {
        if (isLoading) {
            dispatch(setLoading(true));
        }

        let url = apiBaseURL.STOCK_MOVEMENTS;
        if (!_.isEmpty(filter)) {
            url += requestParam(filter, false, true, null, url);
        }

        await apiConfig
            .get(url)
            .then((response) => {
                dispatch({
                    type: stockMovementActionType.FETCH_STOCK_MOVEMENTS,
                    payload: response.data.data,
                });
                dispatch(setTotalRecord(response?.data?.meta?.total || 0));
            })
            .finally(() => {
                if (isLoading) {
                    dispatch(setLoading(false));
                }
            });
    };

export const fetchStockMovementSummary =
    (filter = {}) =>
    async (dispatch) => {
        let url = apiBaseURL.STOCK_MOVEMENTS_SUMMARY;
        if (!_.isEmpty(filter)) {
            url += requestParam(filter, false, true, null, url);
        }

        await apiConfig.get(url).then((response) => {
            dispatch({
                type: stockMovementActionType.FETCH_STOCK_MOVEMENT_SUMMARY,
                payload: response.data.data,
            });
        });
    };
