import { fbrProfileActionType } from "../../constants";

export default (state = [], action) => {
    switch (action.type) {
        case fbrProfileActionType.FETCH_FBR_PROFILES:
            return action.payload || [];
        case fbrProfileActionType.DELETE_FBR_PROFILE:
            return state.filter((item) => item.id !== action.payload);
        default:
            return state;
    }
};
