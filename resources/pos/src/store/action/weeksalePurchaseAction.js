import apiConfig from '../../config/apiConfig';
import {apiBaseURL, toastType, weekSalePurchasesActionType} from '../../constants';
import {addToast} from './toastAction';
import {setLoading} from "./loadingAction";
import requestParam from '../../shared/requestParam';

export const weekSalePurchases = (filter) => async (dispatch) => {
    dispatch(setLoading(true));

    let url = apiBaseURL.WEEK_SALE_PURCHASES_API;
    if (!_.isEmpty(filter)) {
        url += requestParam(filter, null, null, null, url);
    }

    apiConfig.get(url)
        .then((response) => {
            dispatch({type: weekSalePurchasesActionType.WEEK_SALE_PURCHASES, payload: response.data.data})
            dispatch(setLoading(false));
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
            dispatch(setLoading(false));
        });
}
