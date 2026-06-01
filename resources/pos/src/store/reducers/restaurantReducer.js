import { restaurantActionType } from "../../constants";

const initialState = {
    halls: [],
    tables: [],
    kots: [],
};

export default (state = initialState, action) => {
    switch (action.type) {
        case restaurantActionType.FETCH_HALLS:
            return { ...state, halls: action.payload || [] };
        case restaurantActionType.FETCH_TABLES:
            return { ...state, tables: action.payload || [] };
        case restaurantActionType.FETCH_KOTS:
            return { ...state, kots: action.payload || [] };
        default:
            return state;
    }
};
