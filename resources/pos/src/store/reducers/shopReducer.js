import { shopActionType } from "../../constants";

export default (state = [], action) => {
    switch (action.type) {
        case shopActionType.FETCH_SHOPS:
            return action.payload;
        case shopActionType.DELETE_SHOP:
            return state.filter((item) => item.id !== action.payload);
        default:
            return state;
    }
};
