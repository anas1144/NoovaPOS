import apiConfig from "../../config/apiConfig";
import { apiBaseURL, fbrProfileActionType, toastType } from "../../constants";
import { setLoading } from "./loadingAction";
import { addToast } from "./toastAction";

export const fetchFbrProfiles =
    (filter = {}, isLoading = true) =>
    async (dispatch) => {
        if (isLoading) dispatch(setLoading(true));
        try {
            const response = await apiConfig.get(apiBaseURL.FBR_PROFILES, {
                params: filter,
            });
            dispatch({
                type: fbrProfileActionType.FETCH_FBR_PROFILES,
                payload: response.data.data,
            });
        } catch ({ response }) {
            dispatch(
                addToast({
                    text:
                        response?.data?.message ||
                        "Failed to fetch FBR profiles",
                    type: toastType.ERROR,
                })
            );
        } finally {
            if (isLoading) dispatch(setLoading(false));
        }
    };

export const addFbrProfile = (data, onSuccess) => async (dispatch) => {
    try {
        const response = await apiConfig.post(apiBaseURL.FBR_PROFILES, data);
        dispatch({
            type: fbrProfileActionType.ADD_FBR_PROFILE,
            payload: response.data.data,
        });
        dispatch(addToast({ text: "FBR profile created successfully." }));
        dispatch(fetchFbrProfiles());
        onSuccess && onSuccess();
    } catch ({ response }) {
        dispatch(
            addToast({
                text:
                    response?.data?.message ||
                    "Failed to create FBR profile",
                type: toastType.ERROR,
            })
        );
    }
};

export const editFbrProfile = (id, data, onSuccess) => async (dispatch) => {
    try {
        const response = await apiConfig.post(
            apiBaseURL.FBR_PROFILES + "/" + id,
            data
        );
        dispatch({
            type: fbrProfileActionType.EDIT_FBR_PROFILE,
            payload: response.data.data,
        });
        dispatch(addToast({ text: "FBR profile updated successfully." }));
        dispatch(fetchFbrProfiles());
        onSuccess && onSuccess();
    } catch ({ response }) {
        dispatch(
            addToast({
                text:
                    response?.data?.message ||
                    "Failed to update FBR profile",
                type: toastType.ERROR,
            })
        );
    }
};

export const deleteFbrProfile = (id) => async (dispatch) => {
    try {
        await apiConfig.delete(apiBaseURL.FBR_PROFILES + "/" + id);
        dispatch({
            type: fbrProfileActionType.DELETE_FBR_PROFILE,
            payload: id,
        });
        dispatch(addToast({ text: "FBR profile deleted successfully." }));
        dispatch(fetchFbrProfiles());
    } catch ({ response }) {
        dispatch(
            addToast({
                text:
                    response?.data?.message ||
                    "Failed to delete FBR profile",
                type: toastType.ERROR,
            })
        );
    }
};
