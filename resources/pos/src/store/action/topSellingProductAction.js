import apiConfig from '../../config/apiConfig';
import {apiBaseURL, toastType, topSellingActionType} from '../../constants';
import {addToast} from './toastAction';
import {setLoading} from "./loadingAction";
import requestParam from '../../shared/requestParam';

export const topSellingProduct = (filter) => async (dispatch) => {
    dispatch(setLoading(true));

    let url = apiBaseURL.TOP_SELLING_PRODUCTS;
    if (filter && Object.keys(filter).length > 0) {
        url += requestParam(filter, null, null, null, url);
    }

    apiConfig.get(url)
        .then((response) => {
            dispatch({type: topSellingActionType.TOP_SELLING, payload: response.data.data})
            dispatch(setLoading(false));
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
            dispatch(setLoading(false));
        });
}
