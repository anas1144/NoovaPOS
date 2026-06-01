import { recurringActionType } from "../../constants";

const initialState = {
    plans: [],
    subscriptions: [],
    deliveries: [],
    invoices: [],
};

export default (state = initialState, action) => {
    switch (action.type) {
        case recurringActionType.FETCH_RECURRING_PLANS:
            return { ...state, plans: action.payload || [] };
        case recurringActionType.FETCH_CUSTOMER_SUBSCRIPTIONS:
            return { ...state, subscriptions: action.payload || [] };
        case recurringActionType.FETCH_DELIVERY_SCHEDULES:
            return { ...state, deliveries: action.payload || [] };
        case recurringActionType.FETCH_DELIVERIES:
            return { ...state, invoices: action.payload || [] };
        default:
            return state;
    }
};
