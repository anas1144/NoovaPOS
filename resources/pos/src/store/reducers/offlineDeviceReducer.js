import { offlineDeviceActionType } from "../../constants";

const initialState = {
    devices: [],
    queue: [],
};

export default (state = initialState, action) => {
    switch (action.type) {
        case offlineDeviceActionType.FETCH_OFFLINE_DEVICES:
            return { ...state, devices: action.payload || [] };
        case offlineDeviceActionType.FETCH_SYNC_QUEUE:
            return { ...state, queue: action.payload || [] };
        default:
            return state;
    }
};
