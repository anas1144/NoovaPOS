import React, { useEffect, useState } from "react";
import Form from "react-bootstrap/Form";
import { connect, useDispatch } from "react-redux";
import { useNavigate } from "react-router-dom";
import moment from "moment";
import { InputGroup } from "react-bootstrap-v5";
import {
    decimalValidate,
    getFormattedMessage,
    placeholderText,
} from "../../shared/sharedMethod";
import { editExpense, postExpense } from "../../store/action/expenseAction";
import { addToast } from "../../store/action/toastAction";
import { toastType } from "../../constants";
import ModelFooter from "../../shared/components/modelFooter";
import ReactSelect from "../../shared/select/reactSelect";
import ReactDatePicker from "../../shared/datepicker/ReactDatePicker";
import { fetchUsers } from "../../store/action/userAction";
import PostConfirmationModal from "../../shared/components/modals/PostConfirmationModal";

const ExpenseForm = (props) => {
    const {
        addExpenseData,
        id,
        editExpense,
        postExpense,
        singleExpense,
        warehouses,
        expenseCategories,
        frontSetting,
        users,
        fetchUsers,
    } = props;
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const [usersData, setUsersData] = useState([]);
    const [isPosting, setIsPosting] = useState(false);
    const [showPostConfirm, setShowPostConfirm] = useState(false);
    const [expenseValue, setExpenseValue] = useState({
        date: singleExpense
            ? moment(singleExpense[0].date).toDate()
            : new Date(),
        warehouse_id: singleExpense ? singleExpense[0].warehouse_id : "",
        expense_category_id: singleExpense
            ? singleExpense[0].expense_category_id
            : "",
        amount: singleExpense ? singleExpense[0].amount : "",
        details: singleExpense ? singleExpense[0].details : "",
        title: singleExpense ? singleExpense[0].title : "",
        user_id: singleExpense ? singleExpense[0].user_id : "",
        posted_status: singleExpense ? singleExpense[0].posted_status : 0,
    });

    const [errors, setErrors] = useState({
        date: "",
        title: "",
        warehouse_id: "",
        expense_category_id: "",
        amount: "",
        details: "",
    });
    const [selectedWarehouse] = useState(
        singleExpense
            ? [
                  {
                      label: singleExpense[0].warehouse_id.label,
                      value: singleExpense[0].warehouse_id.value,
                  },
              ]
            : null
    );
    const [selectExpenseCategory] = useState(
        singleExpense
            ? [
                  {
                      label: singleExpense[0].expense_category_id.label,
                      value: singleExpense[0].expense_category_id.value,
                  },
              ]
            : null
    );

    const [selectedUser] = useState(
        singleExpense && singleExpense[0].user_id?.value
            ? [
                  {
                      label: singleExpense[0].user_id?.label,
                      value: singleExpense[0].user_id?.value,
                  },
              ]
            : null
    );

    const disabled =
        singleExpense &&
        singleExpense[0].title === expenseValue.title &&
        singleExpense[0].expense_category_id.value ===
            expenseValue.expense_category_id.value &&
        singleExpense[0].warehouse_id.value ===
            expenseValue.warehouse_id.value &&
        singleExpense[0].amount === expenseValue.amount &&
        singleExpense[0].details === expenseValue.details && 
        singleExpense[0].user_id?.value === expenseValue.user_id?.value &&
        moment(singleExpense[0].date).toDate().toString() ===
            expenseValue.date.toString();

    const handleValidation = () => {
        let errorss = {};
        let isValid = false;
        if (!expenseValue["warehouse_id"]) {
            errorss["warehouse_id"] = getFormattedMessage(
                "expense.input.warehouse.validate.label"
            );
        } else if (!expenseValue["title"]) {
            errorss["title"] = getFormattedMessage(
                "expense.input.title.validate.label"
            );
        } else if (!expenseValue["expense_category_id"]) {
            errorss["expense_category_id"] = getFormattedMessage(
                "expense.input.expense-category.validate.label"
            );
        } else if (!expenseValue["amount"]) {
            errorss["amount"] = getFormattedMessage(
                "expense.input.amount.validate.label"
            );
        } else {
            isValid = true;
        }
        setErrors(errorss);
        return isValid;
    };

    useEffect(() => {
        fetchUsers({}, true, "?page[size]=0");
    }, []);

    useEffect(() => {
        if (users?.length > 0) {
            setUsersData(() =>
                users?.map((user) => ({
                    id: user?.id,
                    label: `${user?.attributes?.first_name} ${
                        user?.attributes?.last_name !== "" &&
                        user?.attributes?.last_name !== null &&
                        user?.attributes?.last_name !== undefined
                            ? user?.attributes?.last_name
                            : ""
                    }`,
                }))
            );
        }
    }, [users]);

    const onWarehouseChange = (obj) => {
        setExpenseValue((inputs) => ({ ...inputs, warehouse_id: obj }));
        setErrors("");
    };

    const onExpenseChange = (obj) => {
        setExpenseValue((inputs) => ({ ...inputs, expense_category_id: obj }));
        setErrors("");
    };

    const onUserChange = (obj) => {
        setExpenseValue((inputs) => ({ ...inputs, user_id: obj }));
        setErrors("");
    };

    const onChangeInput = (e) => {
        e.preventDefault();
        setExpenseValue((inputs) => ({
            ...inputs,
            [e.target.name]: e.target.value,
        }));
        setErrors("");
    };

    const handleCallback = (date) => {
        setExpenseValue((previousState) => {
            return { ...previousState, date: date };
        });
    };

    const prepareData = (prepareData) => {
        const formValue = {
            date: moment(prepareData.date).locale('en').toDate(),
            title: prepareData.title,
            warehouse_id: prepareData.warehouse_id.value,
            expense_category_id: prepareData.expense_category_id.value,
            user_id: prepareData.user_id ? prepareData.user_id.value : null,
            amount: prepareData.amount,
            details: prepareData.details,
        };
        return formValue;
    };

    const handlePostExpense = () => {
        if (expenseValue.posted_status === 1) {
            dispatch(
                addToast({
                    text: getFormattedMessage("globally.post.already_posted") || "This expense has already been posted.",
                    type: toastType.WARNING,
                })
            );
            return;
        }
        setShowPostConfirm(true);
    };

    const handleConfirmPost = () => {
        setIsPosting(true);
        postExpense(id, navigate, () => {
            setIsPosting(false);
            setShowPostConfirm(false);
        });
    };

    const onSubmit = (event) => {
        event.preventDefault();
        const valid = handleValidation();
        if (singleExpense && valid) {
            if (!disabled) {
                editExpense(id, prepareData(expenseValue), navigate);
            }
        } else {
            if (valid) {
                setExpenseValue(expenseValue);
                addExpenseData(prepareData(expenseValue));
            }
        }
    };

    return (
        <div className="card">
            <div className="card-body">
                <Form>
                    <div className="row">
                        <div className="col-md-6 mb-3">
                            <label className="form-label">
                                {getFormattedMessage(
                                    "react-data-table.date.column.label"
                                )}
                                :
                            </label>
                            <span className="required" />
                            <div className="position-relative">
                                <ReactDatePicker
                                    onChangeDate={handleCallback}
                                    newStartDate={expenseValue.date}
                                />
                            </div>
                            <span className="text-danger d-block fw-400 fs-small mt-2">
                                {errors["date"] ? errors["date"] : null}
                            </span>
                        </div>

                        <div className="col-md-6 mb-3">
                            <label className="form-label">
                                {getFormattedMessage(
                                    "expense.input.title.label"
                                )}
                                :
                            </label>
                            <span className="required" />
                            <input
                                type="type"
                                name="title"
                                className="form-control"
                                placeholder={placeholderText(
                                    "expense.input.title.placeholder.label"
                                )}
                                onChange={(e) => onChangeInput(e)}
                                value={expenseValue.title || ""}
                            />
                            <span className="text-danger d-block fw-400 fs-small mt-2">
                                {errors["title"] ? errors["title"] : null}
                            </span>
                        </div>
                        <div className="col-md-6 mb-3">
                            <ReactSelect
                                title={getFormattedMessage("warehouse.title")}
                                placeholder={placeholderText(
                                    "expense.input.warehouse.placeholder.label"
                                )}
                                defaultValue={selectedWarehouse}
                                errors={errors["warehouse_id"]}
                                data={warehouses}
                                onChange={onWarehouseChange}
                            />
                        </div>
                        <div className="col-md-6 mb-3">
                            <ReactSelect
                                title={getFormattedMessage(
                                    "expense-category.title"
                                )}
                                placeholder={placeholderText(
                                    "expense.input.expense-category.placeholder.label"
                                )}
                                defaultValue={selectExpenseCategory}
                                errors={errors["expense_category_id"]}
                                data={expenseCategories}
                                onChange={onExpenseChange}
                            />
                        </div>
                        <div className="col-md-6 mb-3">
                            <ReactSelect
                                title={getFormattedMessage(
                                    "users.table.user.column.title"
                                )}
                                placeholder={placeholderText(
                                    "select.user.label"
                                )}
                                defaultValue={selectedUser}
                                data={usersData}
                                onChange={onUserChange}
                                isRequired
                            />
                        </div>
                        <div className="col-md-6 mb-3">
                            <label className="form-label">
                                {getFormattedMessage(
                                    "expense.input.amount.label"
                                )}
                                :
                            </label>
                            <span className="required" />
                            <InputGroup>
                                <input
                                    type="text"
                                    name="amount"
                                    value={expenseValue.amount || ""}
                                    placeholder={placeholderText(
                                        "expense.input.amount.placeholder.label"
                                    )}
                                    pattern="[0-9]*"
                                    min={0}
                                    className="form-control"
                                    onKeyPress={(event) =>
                                        decimalValidate(event)
                                    }
                                    onChange={(e) => onChangeInput(e)}
                                />
                                <InputGroup.Text>
                                    {frontSetting.value &&
                                        frontSetting.value.currency_symbol}
                                </InputGroup.Text>
                            </InputGroup>
                            <span className="text-danger d-block fw-400 fs-small mt-2">
                                {errors["amount"] ? errors["amount"] : null}
                            </span>
                        </div>
                        <div className="col-md-6 mb-3">
                            <label className="form-label">
                                {getFormattedMessage(
                                    "expense.input.details.label"
                                )}
                                :{" "}
                            </label>
                            <textarea
                                name="details"
                                className="form-control"
                                rows="3"
                                placeholder={placeholderText(
                                    "expense.input.details.placeholder.label"
                                )}
                                onChange={(e) => onChangeInput(e)}
                                value={expenseValue.details || ""}
                            />
                            <span className="text-danger d-block fw-400 fs-small mt-2">
                                {errors["detail"] ? errors["detail"] : null}
                            </span>
                        </div>
                        {singleExpense && (
                            <div className="alert alert-info mb-3">
                                <strong>Status:</strong>{" "}
                                <span className={expenseValue.posted_status === 1 ? "text-success fw-bold" : "text-warning fw-bold"}>
                                    {expenseValue.posted_status === 1 ? getFormattedMessage("globally.status.posted") : getFormattedMessage("globally.status.draft")}
                                </span>
                            </div>
                        )}
                        <div className="d-flex gap-2 mb-3">
                            {singleExpense && expenseValue.posted_status === 0 && (
                                <button
                                    onClick={handlePostExpense}
                                    className="btn btn-success"
                                    type="button"
                                    disabled={isPosting}
                                >
                                    {isPosting ? getFormattedMessage("globally.post.posting") || "Posting..." : getFormattedMessage("globally.post.button") || "Post Expense"}
                                </button>
                            )}
                        </div>
                        <ModelFooter
                            onEditRecord={singleExpense}
                            onSubmit={onSubmit}
                            editDisabled={disabled}
                            link="/app/expenses"
                            addDisabled={
                                !expenseValue.warehouse_id ||
                                !expenseValue.expense_category_id ||
                                !expenseValue.amount
                            }
                        />
                    </div>
                </Form>
            </div>
            {showPostConfirm && (
                <PostConfirmationModal
                    onConfirm={handleConfirmPost}
                    onCancel={() => setShowPostConfirm(false)}
                    title={getFormattedMessage("globally.post.label") || "Post Expense"}
                />
            )}
        </div>
    );
};

const mapStateToProps = (state) => {
    const { frontSetting, users } = state;
    return { frontSetting, users };
};

export default connect(mapStateToProps, { editExpense, fetchUsers, postExpense })(
    ExpenseForm
);
