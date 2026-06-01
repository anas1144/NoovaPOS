import { accountingActionType } from "../../constants";

const initialState = {
    accounts: [],
    journalEntries: [],
    trialBalance: { rows: [], totals: { debit: 0, credit: 0 } },
};

export default (state = initialState, action) => {
    switch (action.type) {
        case accountingActionType.FETCH_ACCOUNTS:
            return { ...state, accounts: action.payload || [] };
        case accountingActionType.FETCH_JOURNAL_ENTRIES:
            return { ...state, journalEntries: action.payload || [] };
        case accountingActionType.FETCH_TRIAL_BALANCE:
            return { ...state, trialBalance: action.payload || initialState.trialBalance };
        default:
            return state;
    }
};
