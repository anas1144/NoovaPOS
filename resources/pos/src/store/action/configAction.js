import { apiBaseURL, configActionType, toastType } from '../../constants';
import apiConfig from '../../config/apiConfig';
import { addToast } from './toastAction';

export const fetchConfig = (navigate) => async (dispatch) => {
    apiConfig.get(apiBaseURL.CONFIG)
        .then((response) => {
            dispatch({ type: configActionType.FETCH_CONFIG, payload: response.data.data.permissions });
            dispatch({ type: configActionType.FETCH_ALL_CONFIG, payload: response.data.data });
            // Guard: only navigate if navigate is still a valid function and the
            // component that created it hasn't unmounted (stale navigate throws
            // "Cannot read properties of undefined (reading 'pathname')").
            if (typeof navigate === 'function') {
                try {
                    navigate('/app/pos');
                } catch (_) {
                    // Stale navigate — component unmounted, ignore.
                }
            }
        })
        .catch((response) => {
            dispatch(addToast(
                { text: response?.response?.data?.message, type: toastType.ERROR }));
        });
};
