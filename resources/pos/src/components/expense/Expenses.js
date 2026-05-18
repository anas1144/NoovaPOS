import React, { useState } from "react";
import { connect } from "react-redux";
import moment from "moment";
import MasterLayout from "../MasterLayout";
import { useNavigate } from "react-router-dom";
import ReactDataTable from "../../shared/table/ReactDataTable";
import { fetchExpenses, postExpense } from "../../store/action/expenseAction";
import DeleteExpense from "./DeleteExpense";
import TabTitle from "../../shared/tab-title/TabTitle";
import {
    currencySymbolHandling,
    getFormattedDate,
    getFormattedMessage,
    getPermission,
    placeholderText,
} from "../../shared/sharedMethod";
import ActionButton from "../../shared/action-buttons/ActionButton";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import PostConfirmationModal from "../../shared/components/modals/PostConfirmationModal";
import { Permissions } from "../../constants";

const Expenses = (props) => {
    const {
        fetchExpenses,
        expenses,
        totalRecord,
        isLoading,
        frontSetting,
        allConfigData,
        isCallFetchDataApi,
        postExpense
    } = props;
    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const [postingId, setPostingId] = useState(null);
    const [showPostConfirm, setShowPostConfirm] = useState(false);
    const [itemToPost, setItemToPost] = useState(null);
    const navigate = useNavigate();

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    const onChange = (filter) => {
        fetchExpenses(filter, true);
    };

    const goToEditProduct = (item) => {
        navigate(`/app/expenses/edit/${item.id}`);
    };

    // post expense function
    const onClickPost = (item) => {
        setItemToPost(item);
        setShowPostConfirm(true);
    };

    const handleConfirmPost = () => {
        if (itemToPost) {
            setPostingId(itemToPost.id);
            postExpense(itemToPost.id, navigate, () => {
                setPostingId(null);
                setShowPostConfirm(false);
                setItemToPost(null);
            });
        }
    };

    const currencySymbol =
        frontSetting &&
        frontSetting.value &&
        frontSetting.value.currency_symbol;

    const itemsValue =
        currencySymbol &&
        expenses.length >= 0 &&
        expenses.map((expense) => {
            if (!expense || !expense.attributes) {
                return null;
            }
            return {
                date: getFormattedDate(
                    expense.attributes.date,
                    allConfigData && allConfigData
                ),
                time: moment(expense.attributes.created_at).format("LT"),
                reference_code: expense.attributes.reference_code,
                title: expense.attributes.title,
                warehouse_name: expense.attributes.warehouse_name,
                username: expense.attributes.user_name ? expense.attributes.user_name : "-",
                expense_category_name: expense.attributes.expense_category_name,
                amount: expense.attributes.amount,
                details: expense.attributes.details,
                posted_status: expense.attributes.posted_status,
                id: expense.id,
                currency: currencySymbol,
            };
        })?.filter(Boolean);

    const columns = [
        {
            name: getFormattedMessage("dashboard.recentSales.reference.label"),
            sortField: "reference_code",
            sortable: true,
            cell: (row) => {
                return (
                    <span className="badge bg-light-danger">
                        <span>{row.reference_code}</span>
                    </span>
                );
            },
        },
        {
            name: getFormattedMessage("users.table.user.column.title"),
            selector: (row) => row.username,
            sortField: "username",
            sortable: false,
        },
        {
            name: getFormattedMessage("expense.input.title.label"),
            selector: (row) => row.title,
            sortField: "title",
            sortable: false,
        },
        {
            name: getFormattedMessage("warehouse.title"),
            selector: (row) => row.warehouse_name,
            sortField: "warehouse_name",
            sortable: false,
        },
        {
            name: getFormattedMessage("expense-category.title"),
            selector: (row) => row.expense_category_name,
            sortField: "expense_category_name",
            sortable: false,
        },
        {
            name: getFormattedMessage("expense.input.amount.label"),
            selector: (row) =>
                currencySymbolHandling(allConfigData, row.currency, row.amount),
            sortField: "amount",
            sortable: true,
        },
        {
            name: getFormattedMessage("globally.status.label"),
            sortField: "posted_status",
            sortable: false,
            cell: (row) => {
                return (
                    <span className={`badge ${row.posted_status === 1 ? 'bg-light-success' : 'bg-light-warning'}`}>
                        <span>{row.posted_status === 1 ? 'Posted' : 'Draft'}</span>
                    </span>
                );
            },
        },
        {
            name: getFormattedMessage(
                "globally.react-table.column.created-date.label"
            ),
            selector: (row) => row.date,
            sortField: "created_at",
            sortable: true,
            cell: (row) => {
                return (
                    <span className="badge bg-light-info">
                        <div className="mb-1">{row.time}</div>
                        {row.date}
                    </span>
                );
            },
        },
        ...((
            getPermission(allConfigData?.permissions, Permissions.EDIT_EXPENSES) ||
            getPermission(allConfigData?.permissions, Permissions.DELETE_EXPENSES)
        ) ? [
            {
                name: getFormattedMessage("react-data-table.action.column.label"),
                right: true,
                ignoreRowClick: true,
                allowOverflow: true,
                button: true,
                cell: (row) => (
                    <ActionButton
                        item={row}
                        goToEditProduct={goToEditProduct}
                        isEditMode={getPermission(allConfigData?.permissions, Permissions.EDIT_EXPENSES) && row.posted_status === 0}
                        onClickDeleteModel={onClickDeleteModel}
                        isDeleteMode={getPermission(allConfigData?.permissions, Permissions.DELETE_EXPENSES) && row.posted_status === 0}
                        onClickPost={onClickPost}
                        isPostMode={row.posted_status === 0}
                        isPosting={postingId === row.id}
                    />
                ),
            }] : []),
    ];

    return (
        <MasterLayout>
            <TopProgressBar />
            <TabTitle title={placeholderText("expenses.title")} />
            <ReactDataTable
                columns={columns}
                items={itemsValue}
                onChange={onChange}
                isLoading={isLoading}
                totalRows={totalRecord}
                {...(getPermission(allConfigData?.permissions, Permissions.CREATE_EXPENSES) &&
                {
                    to: "#/app/expenses/create",
                    ButtonValue: getFormattedMessage("expense.create.title")
                }
                )}
                isCallFetchDataApi={isCallFetchDataApi}
            />
            <DeleteExpense
                onClickDeleteModel={onClickDeleteModel}
                deleteModel={deleteModel}
                onDelete={isDelete}
            />
            {showPostConfirm && (
                <PostConfirmationModal
                    onConfirm={handleConfirmPost}
                    onCancel={() => {
                        setShowPostConfirm(false);
                        setItemToPost(null);
                    }}
                    title={getFormattedMessage("globally.post.label") || "Post Expense"}
                />
            )}
        </MasterLayout>
    );
};

const mapStateToProps = (state) => {
    const { expenses, totalRecord, isLoading, frontSetting, allConfigData, isCallFetchDataApi } =
        state;
    return { expenses, totalRecord, isLoading, frontSetting, allConfigData, isCallFetchDataApi};
};

export default connect(mapStateToProps, { fetchExpenses, postExpense })(
    Expenses
);
