import { stockMovementActionType } from "../../constants";

const initialState = {
    movements: [],
    summary: {
        totals: {
            total_in: 0,
            total_out: 0,
            net_quantity: 0,
        },
        by_type: [],
    },
};

export default (state = initialState, action) => {
    switch (action.type) {
        case stockMovementActionType.FETCH_STOCK_MOVEMENTS:
            return {
                ...state,
                movements: action.payload || [],
            };
        case stockMovementActionType.FETCH_STOCK_MOVEMENT_SUMMARY:
            return {
                ...state,
                summary: action.payload || initialState.summary,
            };
        default:
            return state;
    }
};
