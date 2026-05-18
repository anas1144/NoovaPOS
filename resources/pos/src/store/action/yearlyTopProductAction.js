import apiConfig from '../../config/apiConfig';
import {apiBaseURL, toastType, yearTopProductActionType} from '../../constants';
import {addToast} from './toastAction';
import {setLoading} from "./loadingAction";
import requestParam from '../../shared/requestParam';

export const yearlyTopProduct = (filter) => async (dispatch) => {
    dispatch(setLoading(true));

    let url = apiBaseURL.YEAR_TOP_PRODUCT;
    if (!_.isEmpty(filter)) {
        url += requestParam(filter, null, null, null, url);
    }

    apiConfig.get(url)
        .then((response) => {
            dispatch({type: yearTopProductActionType.YEAR_TOP_PRODUCT, payload: response.data.data})
            dispatch(setLoading(false));
        })
        .catch(({response}) => {
            dispatch(addToast(
                {text: response.data.message, type: toastType.ERROR}));
            dispatch(setLoading(false));
        });
}
