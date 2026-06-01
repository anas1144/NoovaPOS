import React, { useEffect, useState } from "react";
import MasterLayout from "../MasterLayout";
import { connect } from "react-redux";
import { useNavigate } from "react-router-dom";
import moment from "moment";
import ReactDataTable from "../../shared/table/ReactDataTable";
import TabTitle from "../../shared/tab-title/TabTitle";
import { fetchTransfers, postTransfer } from "../../store/action/transfersAction";
import {
    currencySymbolHandling,
    getFormattedDate,
    getPermission,
    placeholderText,
} from "../../shared/sharedMethod";
import { getFormattedMessage } from "../../shared/sharedMethod";
import ActionButton from "../../shared/action-buttons/ActionButton";
import DeleteTransfer from "./DeleteTransfer";
import TransferDetails from "./TransferDetails";
import PostConfirmationModal from "../../shared/components/modals/PostConfirmationModal";
import { Permissions } from "../../constants";

const Transfers = (props) => {
    const {
        fetchTransfers,
        tansfers,
        totalRecord,
        isLoading,
        frontSetting,
        allConfigData,
        isCallFetchDataApi,
        postTransfer
    } = props;
    const navigate = useNavigate();
    const [deleteModel, setDeleteModel] = useState(false);
    const [isDelete, setIsDelete] = useState(null);
    const [detailsModel, setDetailsModel] = useState(false);
    const [isDetails, setIsDetails] = useState(null);
    const [lgShow, setLgShow] = useState(false);
    const [postingId, setPostingId] = useState(null);
    const [showPostConfirm, setShowPostConfirm] = useState(false);
    const [itemToPost, setItemToPost] = useState(null);
    const currencySymbol =
        frontSetting &&
        frontSetting.value &&
        frontSetting.value.currency_symbol;

    const onClickDeleteModel = (isDelete = null) => {
        setDeleteModel(!deleteModel);
        setIsDelete(isDelete);
    };

    //onClick edit function
    const goToEdit = (item) => {
        const id = item.id;
        navigate('/app/transfers/' + id);
    };

    // post transfer function
    const onClickPost = (item) => {
        setItemToPost(item);
        setShowPostConfirm(true);
    };

    const handleConfirmPost = () => {
        if (itemToPost) {
            setPostingId(itemToPost.id);
            postTransfer(itemToPost.id, navigate, () => {
                setPostingId(null);
                setShowPostConfirm(false);
                setItemToPost(null);
            });
        }
    };

    const onChange = (filter) => {
        fetchTransfers(filter, true);
    };

    const onClickDetailsModel = (isDetails = null) => {
        setLgShow(true);
        setIsDetails(isDetails);
    };

    const itemsValue =
        currencySymbol &&
        tansfers.length >= 0 &&
        tansfers.map((tansfer) => {
            return {
                date: getFormattedDate(
                    tansfer.attributes.date,
                    allConfigData && allConfigData
                ),
                time: moment(tansfer.attributes.created_at).format("LT"),
                reference_code: tansfer.attributes.reference_code,
                from_warehouse_id: tansfer.attributes.from_warehouse.name,
                to_warehouse_id: tansfer.attributes.to_warehouse.name,
                items: tansfer.attributes.transfer_items.length,
                grand_total: tansfer.attributes.grand_total,
                status: tansfer.attributes.status,
                posted_status: tansfer.attributes.posted_status,
                id: tansfer.id,
                currency: currencySymbol,
            };
        });

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
            name: getFormattedMessage("transfer.from-warehouse.title"),
            selector: (row) => row.from_warehouse_id,
            sortField: "from_warehouse_id",
            sortable: false,
        },
        {
            name: getFormattedMessage("transfer.to-warehouse.title"),
            selector: (row) => row.to_warehouse_id,
            sortField: "to_warehouse_id",
            sortable: false,
        },
        {
            name: getFormattedMessage("product-items.label"),
            selector: (row) => row.items,
            sortField: "items",
            sortable: false,
        },
        {
            name: getFormattedMessage("purchase.grant-total.label"),
            selector: (row) =>
                currencySymbolHandling(
                    allConfigData,
                    row.currency,
                    row.grand_total
                ),
            sortField: "grand_total",
            sortable: true,
        },
        {
            name: getFormattedMessage("purchase.select.status.label"),
            sortField: "status",
            sortable: false,
            cell: (row) => {
                return (
                    (row.status == 1 && (
                        <span className="badge bg-light-success">
                            <span>
                                {getFormattedMessage(
                                    "status.filter.complated.label"
                                )}
                            </span>
                        </span>
                    )) ||
                    (row.status == 2 && (
                        <span className="badge bg-light-primary">
                            <span>
                                {getFormattedMessage(
                                    "status.filter.sent.label"
                                )}
                            </span>
                        </span>
                    )) ||
                    (row.status == 3 && (
                        <span className="badge bg-light-warning">
                            <span>
                                {getFormattedMessage(
                                    "status.filter.pending.label"
                                )}
                            </span>
                        </span>
                    ))
                );
            },
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
            sortField: "date",
            sortable: true,
            cell: (row) => {
                return (
                    <span className="badge bg-light-info">
                        <div className="mb-1">{row.time}</div>
                        <div>{row.date}</div>
                    </span>
                );
            },
        },
        {
            name: getFormattedMessage("react-data-table.action.column.label"),
            right: true,
            ignoreRowClick: true,
            allowOverflow: true,
            button: true,
            cell: (row) => (
                <ActionButton
                    isViewIcon={getPermission(allConfigData?.permissions, Permissions.VIEW_TRANSFERS)}
                    goToDetailScreen={onClickDetailsModel}
                    item={row}
                    goToEditProduct={goToEdit}
                    onClickDeleteModel={onClickDeleteModel}
                    isEditMode={getPermission(allConfigData?.permissions, Permissions.EDIT_TRANSFERS) && row.posted_status === 0}
                    isDeleteMode={getPermission(allConfigData?.permissions, Permissions.DELETE_TRANSFERS) && row.posted_status === 0}
                    onClickPost={onClickPost}
                    isPostMode={row.posted_status === 0}
                    isPosting={postingId === row.id}
                />
            ),
        },
    ];

    return (
        <MasterLayout>
            <TabTitle title={placeholderText("transfers.title")} />
            <ReactDataTable
                columns={columns}
                items={itemsValue}
                onChange={onChange}
                isLoading={isLoading}
                {...(getPermission(allConfigData?.permissions, Permissions.CREATE_TRANSFERS) &&
                {
                    to: "#/app/transfers/create",
                    ButtonValue: getFormattedMessage("transfer.create.title")
                }
                )}
                totalRows={totalRecord}
                isShowFilterField
                isTransferStatus
                isCallFetchDataApi={isCallFetchDataApi}
            />
            <DeleteTransfer
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
                    title={getFormattedMessage("globally.post.label") || "Post Transfer"}
                />
            )}
            {lgShow && (
                <TransferDetails
                    onClickDetailsModel={onClickDetailsModel}
                    detailsModel={detailsModel}
                    onDetails={isDetails}
                    setLgShow={setLgShow}
                    lgShow={lgShow}
                />
            )}
        </MasterLayout>
    );
};

const mapStateToProps = (state) => {
    const {
        tansfers,
        totalRecord,
        isLoading,
        frontSetting,
        allConfigData,
        isCallFetchDataApi
    } = state;
    return {
        tansfers,
        totalRecord,
        isLoading,
        frontSetting,
        allConfigData,
        isCallFetchDataApi
    };
};

export default connect(mapStateToProps, {
    fetchTransfers,
    postTransfer
})(Transfers);
